<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Support\Carbon;

trait ClockOutTrait
{

    protected function handleHomeClockOut($clockInOut, $clockOut)
    {
        $clockIn = Carbon::parse($clockInOut->clock_in);

        // Check if clock-out is on the same day as clock-in
        if (!$clockIn->isSameDay($clockOut)) {
            return $this->returnError("Clock-out must be on the same day as clock-in.");
        }
        $user = User::findorFail($clockInOut->user_id);
        $userEndTime = Carbon::parse($user->user_detail->end_time);
        $early_leave = "00:00:00";
        $late_arrive = $clockInOut->late_arrive;

        if (Carbon::parse($clockOut)->lessThan($userEndTime)) {
            $early_leave = $userEndTime->diff($clockOut)->format('%H:%I:%S');
        }
        // Calculate duration and update clock record
        $durationFormatted = $clockIn->diffAsCarbonInterval($clockOut)->format('%H:%I:%S');

        $clockInOut->update([
            'clock_out' => $clockOut,
            'duration' => $durationFormatted,
            'late_arrive' => $late_arrive,
            'early_leave' => $early_leave,
        ]);

        return $this->returnData("clock", $clockInOut, "Clock Out Done");
    }

    protected function handleSiteClockOut($request, $authUser, $clockInOut, $clockOut)
    {
        // Check if the location type is 'site'
        if ($clockInOut->location_type !== 'site') {
            return $this->returnError('Invalid location type for site clock-out.');
        }

        $lastClockedInLocation = $clockInOut->location;
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
        $clockIn = Carbon::parse($clockInOut->clock_in);
        $durationFormatted = $clockIn->diffAsCarbonInterval($clockOut)->format('%H:%I:%S');
        $late_arrive = $clockInOut->late_arrive;

        $clockInOut->update([
            'clock_out' => $clockOut,
            'duration' => $durationFormatted,
            'late_arrive' => $late_arrive,
            'early_leave' => $early_leave,

        ]);

        return $this->returnData("clock", $clockInOut, "Clock Out Done");
    }
}
