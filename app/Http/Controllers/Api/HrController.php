<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClockInOut;
use App\Models\Department;
use App\Models\User;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class HrController extends Controller
{
    use ResponseTrait;

    public function employeesPerMonth(Request $request)
    {
        // Check if 'year' is provided in the request, otherwise default to current year
        $year = $request->has('year') ? $request->input('year') : date('Y');

        // Validate the year input if provided in the request
        if (!preg_match('/^\d{4}$/', $year)) {
            return $this->returnError("Invalid year parameter");
        }

        // Get the current year and date
        $currentYear = date('Y');
        $currentDate = Carbon::now();

        // Initialize the employee counts
        $employeeCounts = collect();
        $cumulativeCount = 0;

        // Handle future years by returning 0 for all months
        if ($year > $currentYear) {
            for ($month = 1; $month <= 12; $month++) {
                $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
                $employeeCounts[$startOfMonth->format('Y-M')] = [
                    'employee_count' => 0,
                    'custom_month' => $startOfMonth->format('Y-M'),
                ];
            }
            return $this->returnData('employeeCount', $employeeCounts->values()->all(), 'Employees count for future year ' . $year);
        }

        // Loop through each month of the year
        for ($month = 1; $month <= 12; $month++) {
            // Set the start and end of the month
            $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
            $endOfMonth = $startOfMonth->copy()->endOfMonth();

            // If the month is after the current date, set the count to 0 and continue
            if ($startOfMonth->isAfter($currentDate)) {
                $employeeCounts[$startOfMonth->format('Y-M')] = [
                    'employee_count' => 0,
                    'custom_month' => $startOfMonth->format('Y-M'),
                ];
                continue;
            }

            $customMonth = $startOfMonth->format('Y-M');

            // Count employees hired up to and including the end of the current month
            $employeeCount = User::whereHas('user_detail', function ($query) use ($endOfMonth) {
                $query->where('hiring_date', '<=', $endOfMonth);
            })->count();

            $cumulativeCount = $employeeCount;

            $employeeCounts[$customMonth] = [
                'employee_count' => $cumulativeCount,
                'custom_month' => $customMonth,
            ];
        }

        // Reset count for future months within the current year
        if ($year == $currentYear) {
            for ($month = $currentDate->month + 1; $month <= 12; $month++) {
                $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
                $employeeCounts[$startOfMonth->format('Y-M')] = [
                    'employee_count' => 0,
                    'custom_month' => $startOfMonth->format('Y-M'),
                ];
            }
        }

        // Sort the employee counts by month
        $formattedCounts = $employeeCounts->sortBy(function ($value, $key) {
            return Carbon::parse($key)->month;
        });

        return $this->returnData('employeeCount', $formattedCounts->values()->all(), 'Employees count up to the year ' . $year);
    }

    public function getEmployeesWorkTypesPercentage(Request $request)
    {
        $data = [
            'site' => 0,
            'home' => 0,
        ];

        // Check if 'year' is provided in the request, otherwise default to current year
        $year = $request->has('year') ? $request->input('year') : date('Y');

        // Validate the year input if provided in the request
        if (!$year || !preg_match('/^\d{4}$/', $year)) {
            return $this->returnError("Invalid year provided");
        }

        // Define the end of the specified year
        $endOfYear = Carbon::create($year, 12, 31)->endOfDay();

        // Filter employees based on hiring_date up to the end of the specified year
        $employees = User::join('user_details', 'users.id', '=', 'user_details.user_id')
            ->where('user_details.hiring_date', '<=', $endOfYear)
            ->with('work_types')
            ->get();

        if ($employees->isEmpty()) {
            return $this->returnError("There are no employees found up to the year {$year}");
        }

        foreach ($employees as $employee) {
            foreach ($employee->work_types as $work_type) {
                if ($work_type->pivot->work_type_id == 1) {
                    $data['site']++;
                } elseif ($work_type->pivot->work_type_id == 2) {
                    $data['home']++;
                }
            }
        }

        $totalWorkTypes = $data['site'] + $data['home'];

        $percentages = [
            'site' => $totalWorkTypes > 0 ? ($data['site'] / $totalWorkTypes) * 100 : 0,
            'home' => $totalWorkTypes > 0 ? ($data['home'] / $totalWorkTypes) * 100 : 0,
        ];

        return $this->returnData('userWorkTypes', $percentages, 'Percentage of employee work types up to the year ' . $year);
    }

    public function getDepartmentEmployees(Request $request)
    {
        $data = [];

        // Get all department names and initialize their employee count to 0
        $departments = Department::pluck('name')->toArray();
        foreach ($departments as $department) {
            $data[$department] = 0;
        }

        // Initialize the count for users with no department
        $data['No Department'] = 0;

        // Check if 'year' is provided in the request, otherwise default to current year
        $year = $request->has('year') ? $request->input('year') : date('Y');

        // Validate the year input if provided in the request
        if (!$year || !preg_match('/^\d{4}$/', $year)) {
            return $this->returnError("Invalid year provided");
        }

        // Get the current year
        $currentYear = date('Y');

        // If the provided year is greater than the current year, return zero counts
        if ($year > $currentYear) {
            return $this->returnData('departmentEmployeesCounts', $data, 'Count of Employee Departments for future year ' . $year);
        }

        // Define the end of the specified year
        $endOfYear = Carbon::create($year, 12, 31)->endOfDay();

        // Filter employees based on hiring_date up to the end of the specified year
        $employees = User::join('user_details', 'users.id', '=', 'user_details.user_id')
            ->where('user_details.hiring_date', '<=', $endOfYear)
            ->get();
        // If no employees are found up to the specified year, return an error
        if ($employees->isEmpty()) {
            return $this->returnError("There are no employees found up to the year {$year}");
        }

        // Count employees for each department
        foreach ($employees as $employee) {
            $departmentName = Department::find($employee->department_id)->name ?? 'No Department';
            if (array_key_exists($departmentName, $data)) {
                $data[$departmentName]++;
            }
        }

        return $this->returnData('departmentEmployeesCounts', $data, 'Count of Employee Departments for the year ' . $year);
    }

    public function getWorkTypeAssignedToUser()
    {
        $users = User::with('work_types')->get();
        $data = [];
        foreach ($users as $user) {
            foreach ($user->work_types as $work_type) {
                $pivotData = $work_type->pivot->toArray();
                $data[] = ['user_work_type' => $pivotData];
            }
        }
        return $this->returnData('user_work_types', $data, 'User WorkType Data');
    }
    public function hrClockIn(Request $request, User $user)
    {
        $authUser = Auth::user();

        if (!$authUser->hasRole('Hr')) {
            return $this->returnError('You are not authorized to clock in for this user', 403);
        }
        $existingClockInWithoutClockOut = ClockInOut::where('user_id', $user->id)
            ->whereNull('clock_out')
            ->exists();

        if ($existingClockInWithoutClockOut) {
            return $this->returnError('You already have an existing clock-in without clocking out.');
        }

        $request->validate([
            'location_type' => "required|string|exists:work_types,name",
            'clock_in' => ['required', 'date_format:Y-m-d H:i:s'],
            'location_id' => 'required_if:location_type,site|exists:locations,id',
        ]);

        $location_type = $request->location_type;

        if ($location_type == "home") {
            $existingHomeClockIn = ClockInOut::where('user_id', $user->id)
                ->whereDate('clock_in', Carbon::today())
                ->where('location_type', "home")
                ->whereNull('clock_out')
                ->orderBy('clock_in', 'desc')
                ->exists();

            if ($existingHomeClockIn) {
                return $this->returnError('The user has already clocked in from home.');
            }

            $clockIn = Carbon::parse($request->clock_in);
            $durationInterval = $clockIn->diffAsCarbonInterval(Carbon::now());
            $durationFormatted = $durationInterval->format('%H:%I:%S');

            $clock = ClockInOut::create([
                'clock_in' => $clockIn,
                'clock_out' => null,
                'duration' => $durationFormatted,
                'user_id' => $user->id,
                'location_id' => null,
                'location_type' => $location_type,
            ]);

            return $this->returnData("clock", $clock, "Clock In Done");
        }

        if ($location_type == "site") {
            $location_id = $request->location_id;

            $userLocation = $user->user_locations()->where('location_id', $location_id)->exists();

            if (!$userLocation) {
                return $this->returnError('The specified location is not assigned to the user.');
            }

            $existingSiteClockIn = ClockInOut::where('user_id', $user->id)
                ->where('location_id', $location_id)
                ->where('location_type', 'site')
                ->whereDate('clock_in', Carbon::today())
                ->whereNull('clock_out')
                ->orderBy('clock_in', 'desc')
                ->exists();

            if ($existingSiteClockIn) {
                return $this->returnError('The user has already clocked in from the site.');
            }

            $clockIn = Carbon::parse($request->clock_in);
            $durationInterval = $clockIn->diffAsCarbonInterval(Carbon::now());
            $durationFormatted = $durationInterval->format('%H:%I:%S');

            $clock = ClockInOut::create([
                'clock_in' => $clockIn,
                'clock_out' => null,
                'duration' => $durationFormatted,
                'user_id' => $user->id,
                'location_id' => $location_id,
                'location_type' => $location_type,
            ]);

            return $this->returnData("clock", $clock, "Clock In Done");
        }

        return $this->returnError('Invalid location type provided.');
    }
    public function getLocationAssignedToUser(User $user)
    {
        $users = User::with('user_locations')->where('id', $user->id)->get();
        $data = [];
        foreach ($users as $user) {
            foreach ($user->user_locations as $user_location) {
                $data[] = [
                    'id' => $user_location->id,
                    'name' => $user_location->name,
                ];

            }
        }
        return $this->returnData('user_locations', $data, 'User Locations Data');
    }
}
