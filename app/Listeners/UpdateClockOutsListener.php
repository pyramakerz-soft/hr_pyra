<?php

namespace App\Listeners;

use App\Events\CheckClockOutsEvent;
use App\Traits\ResponseTrait;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use Modules\Clocks\Models\ClockInOut;

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
            if ($clock->location)
                $endTime = $clock->user->department->is_location_time ? $clock->location->end_time : $clock->user->user_detail->end_time;
            else
                $endTime = $clock->user->department->is_location_time ? $clock->user->user_detail->end_time : date('H:i:s');
            $clockInDate = Carbon::parse($clock->clock_in)->format('Y-m-d');
            $endTimestamp = Carbon::parse($clockInDate . ' ' . $endTime);
            $clockInTimestamp = Carbon::parse($clock->clock_in);
            $duration = $clockInTimestamp->diff($endTimestamp);
            $clock->update([

                'is_issue' => true,
                'early_leave' => "00:00:00",
                'duration' => "00:00:00",
            ]);
            Log::info($clock->toArray());
        }
    }
}
