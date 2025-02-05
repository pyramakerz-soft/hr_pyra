<?php

namespace App\Traits;

use App\Models\ClockInOut;
use App\Models\User;
use App\Traits\ClockValidator;
use Illuminate\Support\Carbon;

trait ClockOutTrait
{
    use ClockValidator, HelperTrait;
    protected function getClockInWithoutClockOut($user_id)
    {
        $query = ClockInOut::where('user_id', $user_id)
            ->whereNull('clock_out')
            ->orderBy('clock_in', 'desc')
            ->first();

        return $query;
    }

    protected function validateLocation($latitude, $longitude, $expectedLatitude, $expectedLongitude)
    {
        $distance = $this->haversineDistance($latitude, $longitude, $expectedLatitude, $expectedLongitude);
        if ($distance > 50) {
            return $this->returnError('User is not located at the correct location.');
        }
        return;
    }

    protected function updateClockOutRecord($clock, $clockOut, $durationFormatted, $late_arrive, $early_leave, $latitudeOut = null, $longitudeOut = null)
    {
        $addressOut = $this->getAddressFromCoordinates($latitudeOut, $longitudeOut);

        $formatted_address_out = isset($addressOut['address']['road']) ? $addressOut['address']['road'] : 'Address not available';

        $clock->update([
            'clock_out' => $clockOut,
            'duration' => $durationFormatted,
            'late_arrive' => $late_arrive,
            'early_leave' => $early_leave,
            'address_clock_out' => $formatted_address_out,
        ]);

        $clock->makeHidden(['location']);

        return $this->returnData("clock", $clock, "Clock Out Done");
    }

    protected function handleHomeClockOut($clock, $clockOut)
    {
        //1- Validate ClockIn & ClockOut
        $clockIn = Carbon::parse($clock->clock_in);
        $error = $this->validateClockTime($clockIn, $clockOut);
        if ($error) {
            return $error;
        }

        //2- Prepare data for Calculate early_leave
        $user = User::findorFail($clock->user_id);
        $userEndTime = Carbon::parse($user->user_detail->end_time);

        //3- calculate Early_Leave
        $early_leave = $this->calculateEarlyLeave($clockOut, $userEndTime);
        $late_arrive = $clock->late_arrive;
        //4- Calculate duration
        $durationFormatted = $clockIn->diffAsCarbonInterval($clockOut)->format('%H:%I:%S');

        //5- update clock record
        return $this->updateClockOutRecord($clock, $clockOut, $durationFormatted, $late_arrive, $early_leave);
    }
    protected function handleFloatClockOut($clock, $clockOut, $latitudeOut, $longitudeOut)
    {
        $clockIn = Carbon::parse($clock->clock_in);
        $error = $this->validateClockTime($clockIn, $clockOut);
        if ($error) {
            return $error;
        }

        $user = User::findOrFail($clock->user_id);
        $userEndTime = Carbon::parse($user->user_detail->end_time);

        $early_leave = $this->calculateEarlyLeave($clockOut, $userEndTime);
        $late_arrive = $clock->late_arrive;
        $durationFormatted = $clockIn->diffAsCarbonInterval($clockOut)->format('%H:%I:%S');

        return $this->updateClockOutRecord($clock, $clockOut, $durationFormatted, $late_arrive, $early_leave, $latitudeOut, $longitudeOut);
    }

    protected function handleSiteClockOut($request, $authUser, $clock, $clockOut)
    {
        //1- Retrieve the clock-in record without clock-out
        $clock = $this->getClockInWithoutClockOut($authUser->id);
        if (!$clock) {
            return $this->returnError('You are not clocked in.');
        }

        //2- Get the last clocked-in location and validate
        $lastClockedInLocation = $clock->location;
        if (!$lastClockedInLocation) {
            return $this->returnError('No location data found for the last clock-in.');
        }

        //3- Validate location of user and location of the site
        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $validationError = $this->validateLocation($latitude, $longitude, $lastClockedInLocation->latitude, $lastClockedInLocation->longitude);

        // If the validation failed, return the error
        if ($validationError) {
            return $validationError;
        }
        //4- check the department_name for authenticated_user
        if ($this->isLocationTime($authUser)) {
            // calculate the early leave depend on location
            $locationEndTime = Carbon::parse($lastClockedInLocation->end_time);
            $early_leave = $this->calculateEarlyLeave($clockOut, $locationEndTime);
        } else {
            // calculate the early leave depend on user
            $userEndTime = $authUser->user_detail->end_time;
            $early_leave = $this->calculateEarlyLeave($clockOut, $userEndTime);
        }

        //5- Calc Duration
        $clockIn = Carbon::parse($clock->clock_in);
        $durationFormatted = $this->calculateDuration($clockIn, $clockOut);

        //6- ClockOut by updating the clock model
        return $this->updateClockOutRecord($clock, $clockOut, $durationFormatted, $clock->late_arrive, $early_leave,$latitude,$longitude);
    }
}
