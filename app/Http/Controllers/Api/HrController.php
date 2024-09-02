<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClockInOut;
use App\Models\User;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class HrController extends Controller
{
    use ResponseTrait;
    // public function employeesPerMonth()
    // {
    //     $earliestUser = User::orderBy('created_at', 'asc')->first();
    //     dd($earliestUser);
    // }
    //@TODO: Month must be sorted from asc
    public function employeesPerMonth(Request $request)
    {
        // Validate the year parameter
        $year = $request->input('year');
        if (!$year || !preg_match('/^\d{4}$/', $year)) {
            return response()->json([
                'result' => 'false',
                'message' => 'Invalid year parameter',
            ], 400);
        }

        // Define the start and end of the year
        $startOfYear = Carbon::create($year, 1, 1);
        $endOfYear = Carbon::create($year, 12, 31);

        // Get the current date
        $currentDate = Carbon::now();

        // Initialize an array to store counts by month
        $employeeCounts = collect();
        $cumulativeCount = 0;

        // Iterate through each month of the specified year
        for ($month = 1; $month <= 12; $month++) {
            // Calculate the start and end dates of the current month
            $startOfMonth = $startOfYear->copy()->month($month)->startOfMonth();
            $endOfMonth = $startOfYear->copy()->month($month)->endOfMonth();

            // Check if the month is after the current month
            if ($startOfMonth->isAfter($currentDate)) {
                $employeeCounts[$startOfMonth->format('Y-M')] = [
                    'employee_count' => 0,
                    'custom_month' => $startOfMonth->format('Y-M'),
                ];
                continue;
            }

            // Calculate the month label for the response
            $customMonth = $startOfMonth->format('Y-M');

            // Count employees hired within the month
            $employeeCount = User::whereHas('user_detail', function ($query) use ($startOfMonth, $endOfMonth) {
                $query->whereBetween('hiring_date', [$startOfMonth, $endOfMonth]);
            })->count();

            // Update the cumulative count
            $cumulativeCount += $employeeCount;

            // Store the cumulative count in the collection
            $employeeCounts[$customMonth] = [
                'employee_count' => $cumulativeCount,
                'custom_month' => $customMonth,
            ];
        }

        // Sort the months from January to December
        $formattedCounts = $employeeCounts->sortKeys();

        return response()->json([
            'result' => 'true',
            'message' => 'Employee count per month',
            'employeeCount' => $formattedCounts,
        ]);
    }

    public function getEmployeesWorkTypesprecentage()
    {
        $data = [
            'site' => 0,
            'home' => 0,
        ];

        $employees = User::with('work_types')->get();
        if ($employees->isEmpty()) {
            return $this->returnError("There is no employees found");
        }
        foreach ($employees as $employee) {
            foreach ($employee->work_types as $work_type) {
                if ($work_type->pivot->work_type_id == 1) {
                    $data['site']++;
                } elseif ($work_type->pivot->work_type_id == 2) {
                    $data['home']++;
                }
                // $data[] = $work_type->pivot;
            }
        }
        $totalWorkTypes = $data['site'] + $data['home'];

        $percentages = [
            'site' => $totalWorkTypes > 0 ? ($data['site'] / $totalWorkTypes) * 100 : 0,
            'home' => $totalWorkTypes > 0 ? ($data['home'] / $totalWorkTypes) * 100 : 0,
        ];
        return $this->returnData('userWorkTypes', $percentages, 'precentage of employee work types');
    }
    public function getDepartmentEmployees()
    {
        $departments = [
            'software' => 0,
            'academic' => 0,
            'graphic' => 0,

        ];
        $users = User::get()->toArray();
        foreach ($users as $user) {
            if ($user['department_id'] == 1) {
                $departments['software']++;
            } else if ($user['department_id'] == 2) {
                $departments['academic']++;
            } else if ($user['department_id'] == 3) {
                $departments['graphic']++;
            }

        }
        return $this->returnData('departmentEmployeesCounts', $departments, 'Count of Employee Departments');
    }
    public function getLocationAssignedToUser(User $user)
    {

        $users = User::with('user_locations')->where('id', $user->id)->get();
        $data = [];

        foreach ($users as $user) {
            foreach ($user->user_locations as $location) {
                $data[] = [
                    'id' => $location->id,
                    'name' => $location->name,
                ];
            }

        }
        return $this->returnData('userLocations', $data, 'User Location Data');

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

}
