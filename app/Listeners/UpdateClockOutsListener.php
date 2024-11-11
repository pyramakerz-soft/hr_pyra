<?php

namespace App\Listeners;

use App\Events\CheckClockOutsEvent;
use App\Models\ClockInOut;
use App\Traits\ResponseTrait;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

class UpdateClockOutsListener
{
    use ResponseTrait;
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(CheckClockOutsEvent $event)
    {
        $allClocks = ClockInOut::whereNull('clock_out')->get();
        if ($allClocks->isEmpty()) {
            Log::warning('No clock-in records found for missing clock-outs');
            throw new Exception('No clock-in records found for missing clock-outs');
            return;
        }

        foreach ($allClocks as $clock) {
            $endTime = $clock->user->department->is_location_time ? $clock->location->end_time : $clock->user->user_detail->end_time;
            // Get the current date and combine it with the end time to form a proper timestamp
            $clockInDate = Carbon::parse($clock->clock_in)->format('Y-m-d');
            $endTimestamp = Carbon::parse($clockInDate . ' ' . $endTime);
            // Calculate duration and format it as HH:MM:SS
            $clockInTimestamp = Carbon::parse($clock->clock_in);

            $duration = $clockInTimestamp->diff($endTimestamp);
            $durationFormatted = sprintf('%02d:%02d:%02d', $duration->h, $duration->i, $duration->s);
            $clock->update([
                'clock_out' => $endTimestamp,
                'is_issue' => true,
                'early_leave' => "00:00:00",
                'duration' => $durationFormatted,
            ]);
            Log::info($clock->toArray());

        }
    }
}