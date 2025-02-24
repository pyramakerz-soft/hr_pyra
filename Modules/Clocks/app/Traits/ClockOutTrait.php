<?php

namespace Modules\Clocks\Traits;

use App\Traits\HelperTrait;
use App\Traits\LocationHelperTrait;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Modules\Clocks\Models\ClockInOut;
use Modules\Users\Models\User;

trait ClockOutTrait
{
use ClockCalculationsHelperTrait;


    protected function getClockInWithoutClockOut($user_id)
    {
        $query = ClockInOut::where('user_id', $user_id)
            ->whereNull('clock_out')
            ->orderBy('clock_in', 'desc')
            ->first();

        return $query;
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
        $latest_clock_in = ClockInOut::where('user_id',$authUser->id)->orderBy('id','DESC')->first()->location_id;
        $request->location_id = $latest_clock_in;
          // 1- Validate location of user and location of the site
          $userLocation = $this->validateLocations($request, $authUser);

          if ($userLocation instanceof \Illuminate\Http\JsonResponse) {
              return $userLocation; // Return the error response
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
}
