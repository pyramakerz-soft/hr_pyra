<?php

namespace Modules\Users\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ProfileResource extends JsonResource
{

    // Helper method to get the user's active clock (without clock_out)
    private function getUserClock($authUser)
    {
        // Set timezone to Africa/Cairo
        $timezone = 'Africa/Cairo';
        $now = now()->setTimezone($timezone)->toDateString(); // Convert server time to Africa/Cairo
    
     
        $user_clock = $authUser->user_clocks()
            ->whereNull('clock_out') // Ensure clock_out is NULL
            ->whereNotNull('clock_in') // Ensure clock_in is NOT NULL
            ->whereDate('clock_in', $now) // Check based on Cairo timezone
            ->latest('clock_in') // Get the latest clock-in
            ->first();
    
        if ($user_clock) {
            return array_merge($user_clock->toArray(), [
                'location' => $user_clock->location ? [
                    'location_id' => $user_clock->location->id,
                    'location_name' =>  $user_clock->location->name,
                    'location_lat' => $user_clock->location->latitude,
                    'location_lng' => $user_clock->location->longitude,
                    'location_range' =>  $user_clock->location->range,
                ] : null,
            ]);
        }
    
        return null;
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
                'location_lat' => $user_location->latitude,
                'location_lng' => $user_location->longitude,
                'location_range' => $user_location->range,

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
        return $user_clock ? Carbon::parse($user_clock['clock_in'])->format('H:i:s') : null;
    }

    // Method to calculate total hours worked
    private function getTotalHours($authUser)
    {
        $getClocks = $this->getUserClocksForToday($authUser);
        $total_seconds = 0;

        foreach ($getClocks as $clock) {
            $clockIn = Carbon::parse($clock->clock_in);
            $clockOut = $clock->clock_out ? Carbon::parse($clock->clock_out) : Carbon::now();
            $duration = (int)$clockIn->diffInSeconds($clockOut);
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

            'last_clock_in_data' => $this->getUserClock($authUser),

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
