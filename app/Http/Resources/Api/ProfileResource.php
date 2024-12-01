<?php

namespace App\Http\Resources\Api;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class ProfileResource extends JsonResource
{

    // Helper method to get the user's active clock (without clock_out)
    private function getUserClock($authUser)
    {
        $user_clock = $authUser->user_clocks
            ->whereNull('clock_out')
            ->whereBetween('clock_in', [Carbon::parse($this->clock_in)->startOfDay(), Carbon::parse($this->clock_in)->endOfDay()])
            ->last();
        return $user_clock;
    }

    // Helper method to get user clocks for the current day
    private function getUserClocksForToday($authUser)
    {
        $today = Carbon::today()->toDateString();
        return $authUser->user_clocks->filter(function ($clock) use ($today) {
            return $clock->clock_in && Carbon::parse($clock->clock_in)->toDateString() == $today;
        });
    }
    // Method to check if user is working from home
    private function isWorkingFromHome($authUser)
    {
        return $authUser->work_types()->count() > 1;
    }

    // Method to get user's assigned locations
    private function getAssignedLocations($authUser)
    {
        return $authUser->user_locations()->get()->map(function ($user_location) {
            return [
                'location_id' => $user_location->id,
                'location_name' => $user_location->name,
                'location_start_time' => $user_location->start_time,
                'location_end_time' => $user_location->end_time,
            ];
        });
    }

    // Method to check if notification should be sent based on location
    private function isNotifyByLocation()
    {
        return $this->department->is_location_time ? true : false;
    }
    // Method to check if the user is clocked out
    private function isClockedOut($authUser)
    {
        $user_clock = $this->getUserClock($authUser);
        return !$user_clock ? true : false;
    }

    // Method to get the user's clock-in time
    private function getClockInTime($authUser)
    {
        $user_clock = $this->getUserClock($authUser);
        return $user_clock ? Carbon::parse($user_clock->clock_in)->format('H:i:s') : null;
    }

    // Method to calculate total hours worked
    private function getTotalHours($authUser)
    {
        $getClocks = $this->getUserClocksForToday($authUser);
        $total_seconds = 0;

        foreach ($getClocks as $clock) {
            $clockIn = Carbon::parse($clock->clock_in);
            $clockOut = $clock->clock_out ? Carbon::parse($clock->clock_out) : Carbon::now();
            $duration = $clockIn->diffInSeconds($clockOut);
            $total_seconds += $duration;
        }

        return gmdate('H:i:s', $total_seconds);
    }
    public function toArray(Request $request): array
    {
        $authUser = Auth::user();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'national_id' => $this->national_id,
            'image' => $this->image ?? null,
            'job_title' => $this->user_detail->emp_type ?? null,
            'department_id' => $this->department->id,
            'department_name' => $this->department->name,
            'role_name' => $this->getRoleName(),
            'is_clocked_out' => $this->isClockedOut($authUser),
            'clockIn' => $this->getClockInTime($authUser),
            'total_hours' => $this->getTotalHours($authUser),
            'user_start_time' => $this->user_detail->start_time,
            'user_end_time' => $this->user_detail->end_time,
            'is_notify_by_location' => $this->isNotifyByLocation(),
            'assigned_locations_user' => $this->getAssignedLocations($authUser),
            'work_home' => $this->isWorkingFromHome($authUser),
            'work_types' => $this->work_types->pluck('name')->toArray(), 

            // 'is_float' => $this->user_detail->is_float == 1 ? true : false,

        ];
    }
}
/**
 * Transform the resource into an array.
 *
 * @return array<string, mixed>
 */
// public function toArray(Request $request): array
// {

//     $authUser = Auth::user();
//     $today = Carbon::today()->toDateString();
//     $user_clock = $authUser->user_clocks->whereNull('clock_out')->whereBetween('clock_in', [Carbon::parse($this->clock_in)->startOfDay(), Carbon::parse($this->clock_in)->endOfDay()])->last();
//     // $user_clock = $authUser->user_clocks->whereNull('clock_out')->last();
//     $getClocks = $authUser->user_clocks->filter(function ($clock) use ($today) {
//         return $clock->clock_in && Carbon::parse($clock->clock_in)->toDateString() == $today;
//     });
//     $total_seconds = 0;
//     foreach ($getClocks as $clock) {
//         $clockIn = Carbon::parse($clock->clock_in);
//         $clockOut = $clock->clock_out ? Carbon::parse($clock->clock_out) : Carbon::now();

//         $duration = $clockIn->diffInSeconds($clockOut);
//         $total_seconds += $duration;
//     }
//     $total_hours = gmdate('H:i:s', $total_seconds);
//     $is_clocked_out = false;
//     if (!$user_clock) {
//         $is_clocked_out = true;
//     }
//     $clockIn = null;
//     if ($user_clock) {
//         $clockIn = Carbon::parse($user_clock->clock_in)->format('H:i:s');
//     }

//     //Handle Work_from_home
//     $countOfUserWorkTypes = $authUser->work_types()->count();
//     $work_home = $countOfUserWorkTypes > 1 ? true : false;

//     //Handle UserLocations
//     $user_locations = $authUser->user_locations()->get();
//     $locations_name = $user_locations->map(function ($user_location) {
//         return [
//             'location_id' => $user_location->id,
//             'location_name' => $user_location->name,
//             'location_start_time' => $user_location->start_time,
//             'location_end_time' => $user_location->end_time,

//         ];
//     });
//     //Handle Notification by location
//     $is_notify_by_location = ($this->department->is_location_time) ? true : false;

//     return [
//         'id' => $this->id,
//         'name' => $this->name,
//         'national_id' => $this->national_id,
//         'image' => $this->image ?? null,
//         'job_title' => $this->user_detail->emp_type ?? null,
//         'department_id' => $this->department->id,
//         'department_name' => $this->department->name,

//         'role_name' => $this->getRoleName(),
//         'is_clocked_out' => $is_clocked_out,
//         'clockIn' => $clockIn,
//         'total_hours' => $total_hours,
//         'user_start_time' => $this->user_detail->start_time,
//         'user_end_time' => $this->user_detail->end_time,
//         'is_notify_by_location' => $is_notify_by_location,

//         'assigned_locations_user' => $locations_name,
//         'work_home' => $work_home,
//     ];

// }
