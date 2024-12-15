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
            // Check if the user's work type is 'float'
            if ($clock->user->work_types === 'float') {
                // Define clock-out time logic for 'float' work types
                $clockOutTimestamp = Carbon::parse($clock->clock_in)->addHours(8); // Assuming an 8-hour shift for float workers
                $duration = Carbon::parse($clock->clock_in)->diff($clockOutTimestamp);
                $durationFormatted = sprintf('%02d:%02d:%02d', $duration->h, $duration->i, $duration->s);

                // Update the record for 'float' work type
                $clock->update([
                    'clock_out' => $clockOutTimestamp,
                    'is_issue' => true,
                    'early_leave' => "00:00:00",
                    'duration' => $durationFormatted,
                ]);

                Log::info($clock->toArray());
            } else {
                // Default logic for other work types
                $endTime = $clock->user->department->is_location_time
                    ? $clock->location->end_time
                    : $clock->user->user_detail->end_time;

                $clockInDate = Carbon::parse($clock->clock_in)->format('Y-m-d');
                $endTimestamp = Carbon::parse($clockInDate . ' ' . $endTime);
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
}
