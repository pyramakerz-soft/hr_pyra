<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class ProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        $authUser = Auth::user();
        $today = Carbon::today()->toDateString();

        $user_clock = $authUser->user_clocks->whereNull('clock_out')->last();
        $getClocks = $authUser->user_clocks->filter(function ($clock) use ($today) {
            return $clock->clock_in && Carbon::parse($clock->clock_in)->toDateString() == $today;
        });
        $total_seconds = 0;
        foreach ($getClocks as $clock) {
            $clockIn = Carbon::parse($clock->clock_in);
            $clockOut = $clock->clock_out ? Carbon::parse($clock->clock_out) : Carbon::now();

            $duration = $clockIn->diffInSeconds($clockOut);
            $total_seconds += $duration;
        }
        $total_hours = gmdate('H:i:s', $total_seconds);
        $is_clocked_out = false;
        if (!$user_clock) {
            $is_clocked_out = true;
        }
        $clockIn = null;
        if ($user_clock) {
            $clockIn = Carbon::parse($user_clock->clock_in)->format('H:i:s');
        }

        $work_home = false;
        $user_locations = $authUser->user_locations()->get();
        $locations_name = $user_locations->map(function ($user_location) {
            return [
                'location_id' => $user_location->id,
                'location_name' => $user_location->name,
                'location_start_time' => $user_location->start_time,
                'location_end_time' => $user_location->end_time,

            ];
        });


        return [
            'id' => $this->id,
            'name' => $this->name,
            'national_id' => $this->national_id,
            'image' => $this->image ?? null,
            'job_title' => $this->user_detail->emp_type ?? null,
            'role_name' => $this->getRoleName(),
            'is_clocked_out' => $is_clocked_out,
            'clockIn' => $clockIn,
            'total_hours' => $total_hours,
            'user_start_time' => $this->user_detail->start_time,
            'user_end_time' => $this->user_detail->end_time,

            'assigned_locations_user' => $locations_name,
            'assignedLocationsUser' => $locations_name,
            'work_home' => isset($locationTypes[0]) ? $locationTypes[0] == 'home' : false,
        ];

    }
}
