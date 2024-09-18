<?php

namespace App\Traits;

use App\Models\ClockInOut;
use App\Models\User;
use Illuminate\Support\Carbon;

trait ClockOutTrait
{
    protected function getClockInWithoutClockOut($user_id)
    {
        $query = ClockInOut::where('user_id', $user_id)
            ->whereNull('clock_out')
            ->orderBy('clock_in', 'desc')
            ->first();

        return $query;
    }
    protected function validateClockOutTime($clockIn, $clockOut)
    {
        if (!$clockIn->isSameDay($clockOut)) {
            return $this->returnError("Clock-out must be on the same day as clock-in.");
        }
        if ($clockOut <= $clockIn) {
            return $this->returnError("You can't clock out before or at the same time as clock in.");
        }
    }
    protected function validateLocation($latitude, $longitude, $expectedLatitude, $expectedLongitude)
    {
        $distance = $this->haversineDistance($latitude, $longitude, $expectedLatitude, $expectedLongitude);
        if ($distance > 50) {
            return $this->returnError('User is not located at the correct location. lat: ' . $latitude . ' / long: ' . $longitude);
        }
    }
    protected function calculateEarlyLeave($clockOut, $endTime)
    {
        $early_leave = "00:00:00";
        if ($clockOut->lessThan($endTime)) {
            $early_leave = $endTime->diff($clockOut)->format('%H:%I:%S');
        }
        return $early_leave;
    }

    protected function updateClockOutRecord($clock, $clockOut, $durationFormatted, $late_arrive, $early_leave)
    {
        $clock->update([
            'clock_out' => $clockOut,
            'duration' => $durationFormatted,
            'late_arrive' => $late_arrive,
            'early_leave' => $early_leave,
        ]);
        // Hide the location relationship before returning the data
        $clock->makeHidden(['location']);

        return $this->returnData("clock", $clock, "Clock Out Done");
    }
    protected function handleHomeClockOut($clock, $clockOut)
    {
        //Validate ClockIn & ClockOut
        $clockIn = Carbon::parse($clock->clock_in);
        $error = $this->validateClockOutTime($clockIn, $clockOut);
        if ($error) {
            return $error;
        }

        //Prepare data for Calculate early_leave
        $user = User::findorFail($clock->user_id);
        $userEndTime = Carbon::parse($user->user_detail->end_time);

        //calculate Early_Leave
        $early_leave = $this->calculateEarlyLeave($clockOut, $userEndTime);
        $late_arrive = $clock->late_arrive;
        // Calculate duration and update clock record
        $durationFormatted = $clockIn->diffAsCarbonInterval($clockOut)->format('%H:%I:%S');
        return $this->updateClockOutRecord($clock, $clockOut, $durationFormatted, $late_arrive, $early_leave);

    }

    protected function handleSiteClockOut($request, $authUser, $clock, $clockOut)
    {
        $clock = $this->getClockInWithoutClockOut($authUser->id);
        if (!$clock) {
            return $this->returnError('You are not clocked in.');
        }
        $clockIn = Carbon::parse($clock->clock_in);

        // Validate ClockIn & ClockOut
        $error = $this->validateClockOutTime($clockIn, $clockOut);
        if ($error) {
            return $error;
        }

        // Check if the location type is 'site'
        if ($clock->location_type !== 'site') {
            return $this->returnError('Invalid location type for site clock-out.');
        }

        $lastClockedInLocation = $clock->location;
        $locationEndTime = Carbon::parse($lastClockedInLocation->end_time);

        //calculate early_leave
        $early_leave = "00:00:00";
        if ($clockOut->lessThan($locationEndTime)) {
            $early_leave = $locationEndTime->diff($clockOut)->format('%H:%I:%S');
        }
        if (!$lastClockedInLocation) {
            return $this->returnError('No location data found for the last clock-in.');
        }

        // Get the latitude and longitude from the last clock-in
        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $distance = $this->haversineDistance($latitude, $longitude, $lastClockedInLocation->latitude, $lastClockedInLocation->longitude);

        // Check if user is within an acceptable range (e.g., 50 meters)
        if ($distance > 50) {
            return $this->returnError('User is not located at the correct location. lat: ' . $latitude . ' / long: ' . $longitude);
        }

        // Proceed with clock-out as the user is within the accepted range
        $durationFormatted = $clockIn->diffAsCarbonInterval($clockOut)->format('%H:%I:%S');
        $late_arrive = $clock->late_arrive;

        return $this->updateClockOutRecord($clock, $clockOut, $durationFormatted, $late_arrive, $early_leave);

    }

}
