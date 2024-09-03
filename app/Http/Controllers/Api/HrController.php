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
        $year = $request->has('year') ? $request->input('year') : date('Y');

        if (!preg_match('/^\d{4}$/', $year)) {
            return $this->returnError("Invalid year parameter");
        }

        $startOfYear = Carbon::create($year, 1, 1);

        $currentDate = Carbon::now();

        $employeeCounts = collect();
        $cumulativeCount = 0;

        for ($month = 1; $month <= 12; $month++) {
            $startOfMonth = $startOfYear->copy()->month($month)->startOfMonth();
            $endOfMonth = $startOfYear->copy()->month($month)->endOfMonth();

            if ($startOfMonth->isAfter($currentDate)) {
                $employeeCounts[$startOfMonth->format('Y-M')] = [
                    'employee_count' => 0,
                    'custom_month' => $startOfMonth->format('Y-M'),
                ];
                continue;
            }

            $customMonth = $startOfMonth->format('Y-M');

            $employeeCount = User::whereHas('user_detail', function ($query) use ($startOfMonth, $endOfMonth) {
                $query->whereBetween('hiring_date', [$startOfMonth, $endOfMonth]);
            })->count();

            $cumulativeCount += $employeeCount;

            $employeeCounts[$customMonth] = [
                'employee_count' => $cumulativeCount,
                'custom_month' => $customMonth,
            ];
        }

        $formattedCounts = $employeeCounts->sortBy(function ($value, $key) {
            return Carbon::parse($key)->month;
        });

        return $this->returnData('employeeCount', $formattedCounts->values()->all(), 'Employees count');
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

        $startOfYear = Carbon::create($year, 1, 1)->startOfDay();
        $endOfYear = Carbon::create($year, 12, 31)->endOfDay();

        // Filter employees based on hiring_date
        $employees = User::join('user_details', 'users.id', '=', 'user_details.user_id')
            ->whereBetween('user_details.hiring_date', [$startOfYear, $endOfYear])
            ->with('work_types')
            ->get();

        dd($employees->toArray());
        if ($employees->isEmpty()) {
            return $this->returnError("There are no employees found for the year {$year}");
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

        return $this->returnData('userWorkTypes', $percentages, 'percentage of employee work types');
    }

    public function getDepartmentEmployees(Request $request)
    {
        $departments = Department::get()->mapWithKeys(function ($department) {
            return [$department->name => 0];
        })->toArray();

        // dd($departments);

        $year = $request->has('year') ? $request->input('year') : date('Y');

        if (!$year || !preg_match('/^\d{4}$/', $year)) {
            return $this->returnError("Invalid year provided");
        }

        $startOfYear = Carbon::create($year, 1, 1);
        $endOfYear = Carbon::create($year, 12, 31);

        $users = User::whereBetween('created_at', [$startOfYear, $endOfYear])->get();

        foreach ($users as $user) {
            $departmentName = Department::find($user->department_id)->name ?? 'Unknown';
            if (array_key_exists($departmentName, $departments)) {
                $departments[$departmentName]++;
            }
        }

        return $this->returnData('departmentEmployeesCounts', $departments, 'Count of Employee Departments for the year ' . $year);
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
