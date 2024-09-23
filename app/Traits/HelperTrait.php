<?php

namespace App\Traits;

use Illuminate\Support\Carbon;

trait HelperTrait
{
    protected function calculateLateArrive($clockIn, $startTime)
    {
        // Extract the time portion only
        $clockInTime = carbon::parse($clockIn)->format('H:i:s');
        $startTimeFormatted = Carbon::parse($startTime)->format('H:i:s');

        // Initialize late_arrive as "00:00:00" (no late arrival by default)
        $late_arrive = "00:00:00";

        // Check if the user clocked in late (after the start time)
        if ($clockInTime > $startTimeFormatted) {
            // Calculate the late arrival duration and format it as H:i:s
            $late_arrive = Carbon::createFromFormat('H:i:s', $startTimeFormatted)
                ->diff(Carbon::createFromFormat('H:i:s', $clockInTime))
                ->format('%H:%I:%S');
        }

        return $late_arrive;
    }
    protected function calculateEarlyLeave($clockOut, $endTime)
    {

        // Extract the time portion only
        $clockOutTime = Carbon::parse($clockOut)->format('H:i:s');
        $endTimeFormatted = Carbon::parse($endTime)->format('H:i:s');

        // Initialize late_arrive as "00:00:00" (no late arrival by default)
        $early_leave = "00:00:00";

        // Check if the user clocked in late (after the start time)
        if ($clockOutTime < $endTimeFormatted) {
            // Calculate the late arrival duration and format it as H:i:s
            $early_leave = Carbon::createFromFormat('H:i:s', $endTimeFormatted)
                ->diff(Carbon::createFromFormat('H:i:s', $clockOutTime))
                ->format('%H:%I:%S');
        }

        return $early_leave;
    }
    protected function calculateDuration($clockIn, $clockOut)
    {
        return $clockOut ? Carbon::parse($clockIn)->diff(Carbon::parse($clockOut))->format('%H:%I:%S') : null;

    }
}
