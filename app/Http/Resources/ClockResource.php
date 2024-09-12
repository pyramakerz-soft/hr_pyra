<?php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class ClockResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Format clock in time
        $clockIn = $this->clock_in ? Carbon::parse($this->clock_in)->format('h:iA') : null;

        // Calculate clock out time and duration
        if ($this->clock_out) {
            $clockOut = Carbon::parse($this->clock_out)->format('h:iA');
            $formattedClockOut = Carbon::parse($this->clock_out)->format('Y-m-d H:i');
            $duration = Carbon::parse($this->clock_in)->diff(Carbon::parse($this->clock_out))->format('%H:%I');
        } else {
            $clockOut = null;
            $formattedClockOut = null;
            $duration = Carbon::parse($this->clock_in)->diff(Carbon::now())->format('%H:%I');
        }

        // Get location for clock in and clock out
        $locationIn = $this->location_type === "site" && $this->clock_in ? $this->location->address : null;
        $locationOut = $this->location_type === "site" && $this->clock_out ? $this->location->address : null;

// // Get all clock-ins and clock-outs for the user on the same day, excluding the current clock entry
//         $allClocks = ClockInOut::where('user_id', $this->user_id)->get();
//         $clocksForDay = $allClocks->filter(function ($clock) {
//             return Carbon::parse($clock->clock_in)->toDateString() === Carbon::parse($this->clock_in)->toDateString();
//         });

        // // Map other clocks for the same day
        // $otherClocksForDay = $clocksForDay->filter(function ($clock) {
        //     return $clock->id !== $this->id;
        // })->map(function ($clock) {
        //     return [
        //         'id' => $clock->id,
        //         'clockIn' => $clock->clock_in ? Carbon::parse($clock->clock_in)->format('h:iA') : null,
        //         'clockOut' => $clock->clock_out ? Carbon::parse($clock->clock_out)->format('h:iA') : null,
        //         'totalHours' => $clock->clock_in && $clock->clock_out
        //         ? Carbon::parse($clock->clock_in)->diff(Carbon::parse($clock->clock_out))->format('%H:%I')
        //         : ($clock->clock_in ? Carbon::parse($clock->clock_in)->diff(Carbon::now())->format('%H:%I') : null),
        //         'site' => $clock->location_type,
        //         'formattedClockIn' => $clock->clock_in ? Carbon::parse($clock->clock_in)->format('Y-m-d H:i') : null,
        //         'formattedClockOut' => $clock->clock_out ? Carbon::parse($clock->clock_out)->format('Y-m-d H:i') : null,
        //     ];
        // })->values()->toArray();

// // Calculate the total duration for all clocks on the same day
//         $totalDurationForDay = $clocksForDay->reduce(function ($carry, $clock) {
//             if ($clock->clock_in) {
//                 $clockIn = Carbon::parse($clock->clock_in);

//                 if ($clock->clock_out) {
//                     $clockOut = Carbon::parse($clock->clock_out);
//                     $diffInMinutes = $clockIn->diffInMinutes($clockOut);
//                 } else {
//                     $diffInMinutes = $clockIn->diffInMinutes(Carbon::now());
//                 }

//                 $carry += $diffInMinutes;
//             }
//             return $carry;
//         }, 0);

// // Format total duration for the day in 'H:i'
//         $totalHoursForDay = sprintf('%02d:%02d', floor($totalDurationForDay / 60), $totalDurationForDay % 60);

// Return the structured clock-in/out data with total duration for the day
        return [
            'id' => $this->id,
            'Day' => Carbon::parse($this->clock_in)->format('l'),
            'Date' => Carbon::parse($this->clock_in)->format('Y-m-d'),
            'clockIn' => $clockIn,
            'clockOut' => $clockOut,
            'totalHours' => $duration,
            'locationIn' => $locationIn,
            'locationOut' => $locationOut,
            'userId' => $this->user->id,
            'site' => $this->location_type,
            'formattedClockIn' => Carbon::parse($this->clock_in)->format('Y-m-d H:i'),
            'formattedClockOut' => $formattedClockOut,
            'lateArrive' => $this->late_arrive,
            'earlyLeave' => $this->early_leave,
            // 'otherClocks' => $otherClocksForDay,
        ];
    }
}
