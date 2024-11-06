<?php

namespace App\Traits;

use App\Http\Resources\Api\ClockResource;
use App\Traits\HelperTrait;

trait ClockTrait
{
    use ClockValidator;
    use HelperTrait;

    //Update Clock

    protected function updateClockRecord($clock, $clockIn, $clockOut, $duration, $lateArrive, $earlyLeave)
    {
        $clock->update([
            'clock_in' => $clockIn->format('Y-m-d H:i:s'),
            'clock_out' => $clockOut->format('Y-m-d H:i:s'),
            'duration' => $duration,
            'late_arrive' => $lateArrive,
            'early_leave' => $earlyLeave,
        ]);
        return $this->returnData("clock", new ClockResource($clock), "Clock Updated Successfully");

    }
    protected function updateSiteClock($request, $clock, $user)
    {
        // Step 1: Validate the Clock In and Out times
        $clockIn = $this->getClockInTime($request, $clock);
        $clockOut = $this->getClockOutTime($request, $clock);
        $this->validateClockTime($clockIn, $clockOut);

        // Step 2: Calculate Duration
        $durationFormatted = $this->calculateDuration($clockIn, $clockOut);

        // Step 3: Handle clock-in and clock-out time only
        $clockInTime = $clockIn->format('H:i:s');
        $clockOutTime = $clockOut->format('H:i:s');

        // Step 4: Determine the time boundaries based on the user's department
        if ($this->isLocationTime($user)) {
            $locationTimes = $this->getUserLocationTimes($user);
        } else {
            $locationTimes = $this->getUserDetailTimes($user);
        }

        // Step 5: Calculate Late Arrival and Early Leave
        $lateArrive = $this->calculateLateArrive($clockInTime, $locationTimes['start_time']);
        $earlyLeave = $this->calculateEarlyLeave($clockOutTime, $locationTimes['end_time']);

        // Step 6: Update the clock record
        return $this->updateClockRecord($clock, $clockIn, $clockOut, $durationFormatted, $lateArrive, $earlyLeave);
    }
    protected function updateHomeClock($request, $clock, $user)
    {
        //1- Validate ClockIn & ClockOut
        $clockIn = $this->getClockInTime($request, $clock);
        $clockOut = $this->getClockOutTime($request, $clock);
        $this->validateClockTime($clockIn, $clockOut);

        //2- Calculate the duration
        $durationFormatted = $this->calculateDuration($clockIn, $clockOut);

        //3- Prepare Data for Calculate Late Arrival and Early Leave
        $userTimes = $this->getUserDetailTimes($user);
        $clockInTime = $clockIn->format('H:i:s');
        $clockOutTime = $clockOut->format('H:i:s');

        //4- Calculate late_arrive and early_leave based on time only
        $late_arrive = $this->calculateLateArrive($clockInTime, $userTimes['start_time']);
        $early_leave = $this->calculateEarlyLeave($clockOutTime, $userTimes['end_time']);
        //5- Update clock record
        return $this->updateClockRecord($clock, $clockIn, $clockOut, $durationFormatted, $late_arrive, $early_leave);
    }

}
