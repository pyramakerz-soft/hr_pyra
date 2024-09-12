<?php

namespace App\Traits;

use App\Models\ClockInOut;
use App\Models\User;
use App\Traits\ClockTrait;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
trait ClockInTrait
{
    use ClockTrait;

    protected function checkExistingHomeClockIn($user_id)
    {
        return ClockInOut::where('user_id', $user_id)
            ->where('location_type', 'home')
            ->whereNull('clock_out')
            ->whereDate('clock_in', Carbon::today())
            ->exists();
    }

    protected function handleHomeClockIn($request, $user_id)
    {
        if ($this->checkExistingHomeClockIn($user_id)) {
            return $this->returnError('You have already clocked in.');
        }
        $authUser = User::findOrFail($user_id);
        $clockIn = Carbon::parse($request->clock_in);
        $duration = $clockIn->diffAsCarbonInterval(Carbon::now())->format('%H:%I:%S');
        $userStartTime = Carbon::parse($authUser->user_detail->start_time);

        // Initialize late_arrive as "00:00:00" (no late arrival by default)
        $late_arrive = "00:00:00";

        // Check if the user clocked in late (after the location's start time)
        if ($clockIn->greaterThan($userStartTime)) {
            // Calculate the late arrival duration and format it as H:i:s
            $late_arrive = $userStartTime->diff($clockIn)->format('%H:%I:%S');
        }

        $clock = ClockInOut::create([
            'clock_in' => $clockIn,
            'clock_out' => null,
            'duration' => $duration,
            'user_id' => $user_id,
            'location_type' => $request->location_type,
            'late_arrive' => $late_arrive,
            'early_leave' => null,
        ]);

        return $this->returnData("clock", $clock, "Clock In Done");
    }

    protected function checkExistingSiteClockIn($user_id, $location_id)
    {
        return ClockInOut::where('user_id', $user_id)
            ->where('location_type', "site")
            ->where('location_id', $location_id)
            ->whereNull('clock_out')
            ->whereDate('clock_in', Carbon::today())
            ->exists();
    }

    /**
     * Handle clock-in for site location.
     */
    protected function handleSiteClockIn($request, $authUser)
    {
        // Retrieve location details using location_id from the request
        $location_id = $request->location_id;

        // Validate that the location_id is assigned to the user
        $userLocation = $this->getUserAssignedLocationById($authUser, $location_id);
        if (!$userLocation) {
            return $this->returnError('Location is not assigned to the user.');
        }
        // Validate latitude and longitude comparison with the assigned location
        $latitude = $request->latitude;
        $longitude = $request->longitude;

        $distance = $this->haversineDistance($latitude, $longitude, $userLocation->latitude, $userLocation->longitude);

        // Check if user is within an acceptable range (e.g., 50 meters)
        if ($distance > 50) {
            return $this->returnError('User is not located at the correct location. lat: ' . $request->latitude . ' / long: ' . $request->longitude);
        }

        if ($this->getOrCheckExistingClockInWithoutClockOut($authUser->id, $location_id, true)) {
            return $this->returnError('You have already clocked in at this location today.');
        }
        return $this->createClockInSiteRecord($request, $authUser, $userLocation);
    }

    protected function createClockInSiteRecord($request, $authUser, $userLocation)
    {
        $clockIn = Carbon::parse($request->clock_in);
        $duration = $clockIn->diffAsCarbonInterval(Carbon::now())->format('%H:%I:%S');
        $user_location = $authUser->user_locations()->first();
        $locationStartTime = Carbon::parse($user_location->start_time);

        // Initialize late_arrive as "00:00:00" (no late arrival by default)
        $late_arrive = "00:00:00";

        // Check if the user clocked in late (after the location's start time)
        if ($clockIn->greaterThan($locationStartTime)) {
            // Calculate the late arrival duration and format it as H:i:s
            $late_arrive = $locationStartTime->diff($clockIn)->format('%H:%I:%S');
        }

        // dd($late_arrive);
        $clock = ClockInOut::create([
            'clock_in' => $clockIn,
            'clock_out' => null,
            'duration' => $duration,
            'user_id' => $authUser->id,
            'location_id' => $request->location_id,
            'location_type' => $request->location_type,
            'late_arrive' => $late_arrive,
            'early_leave' => null,
        ]);

        return $this->returnData("clock", $clock, "Clock In Done");
    }
}
