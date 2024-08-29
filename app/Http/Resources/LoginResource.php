<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class LoginResource extends JsonResource
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
        // dd($getClocks);
        $total_seconds = 0;
        foreach ($getClocks as $clock) {
            $clockIn = Carbon::parse($clock->clock_in);
            $clockOut = $clock->clock_out ? Carbon::parse($clock->clock_out) : Carbon::now();
            // dd($clockOut->toArray());

            $duration = $clockIn->diffInSeconds($clockOut);
            $total_seconds += $duration;
        }
        $total_hours = gmdate('H:i:s', $total_seconds);
        // dd($total_hours);
        $is_clocked_out = false;
        if (!$user_clock) {
            $is_clocked_out = true;
        }
        $clockIn = null;
        if ($user_clock) {
            $clockIn = Carbon::parse($user_clock->clock_in)->format('H:i:s');
        }

        $work_home = false;
        $locationTypes = $authUser->work_types->pluck('name');
        if (count($locationTypes) > 1) {
            $work_home = true;
        }
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
            'work_home' => $work_home,
        ];

    }
}
