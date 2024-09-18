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

    protected function checkClockInWithoutClockOut($user_id)
    {
        $query = ClockInOut::where('user_id', $user_id)
            ->whereNull('clock_out')
            ->exists();

        return $query;
    }
    protected function calculateLateArrive($clockIn, $startTime)
    {
        // Extract the time portion only
        $clockInTime = $clockIn->format('H:i:s');
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
    protected function validateLocations($request, $authUser)
    {
        // Retrieve location details using location_id from the request
        $location_id = $request->location_id;

        // Validate that the location_id is assigned to the user
        $userLocation = $this->getUserAssignedLocationById($authUser, $location_id);
        if (!$userLocation) {
            return null;
        }

        // Validate latitude and longitude comparison with the assigned location
        $latitude = $request->latitude;
        $longitude = $request->longitude;

        $distance = $this->haversineDistance($latitude, $longitude, $userLocation->latitude, $userLocation->longitude);

        // Check if user is within an acceptable range (e.g., 50 meters)
        if ($distance > 50) {
            return $this->returnError('User is not located at the correct location. lat: ' . $latitude . ' / long: ' . $longitude);
        }

        // Return the validated location
        return $userLocation;
    }
    protected function createHomeClockIn($request, $user_id, $clockIn, $late_arrive)
    {
        $clock = ClockInOut::create([
            'clock_in' => $clockIn,
            'clock_out' => null,
            'duration' => null,
            'user_id' => $user_id,
            'location_type' => $request->location_type,
            'late_arrive' => $late_arrive,
            'early_leave' => null,
        ]);

        return $this->returnData("clock", $clock, "Clock In Done");
    }
    protected function handleHomeClockIn($request, $user_id)
    {
        if ($this->checkClockInWithoutClockOut($user_id)) {
            return $this->returnError('You have already clocked in.');
        }

        // Calculate Late_arrive
        $authUser = User::findOrFail($user_id);
        $clockIn = Carbon::parse($request->clock_in);
        $userStartTime = $authUser->user_detail->start_time;

        // Use the new calculateLateArrive method
        $late_arrive = $this->calculateLateArrive($clockIn, $userStartTime);

        // Call createHomeClockIn method to create the clock-in record
        return $this->createHomeClockIn($request, $user_id, $clockIn, $late_arrive);
    }

    /**
     * Handle clock-in for site location.
     */
    protected function handleSiteClockIn($request, $authUser)
    {
        if ($this->checkClockInWithoutClockOut($authUser->id)) {
            return $this->returnError('You have already clocked in');
        }

        // Validate location
        $userLocation = $this->validateLocations($request, $authUser);
        if ($userLocation == null) {
            return $this->returnError('Location is not assigned to the user.');
        }

        // Calculate Late_arrive
        $clockIn = Carbon::parse($request->clock_in);
        $locationStartTime = $userLocation->start_time;
        $late_arrive = $this->calculateLateArrive($clockIn, $locationStartTime);
        return $this->createClockInSiteRecord($request, $authUser, $userLocation, $clockIn, $late_arrive);
    }

    protected function createClockInSiteRecord($request, $authUser, $userLocation, $clockIn, $late_arrive)
    {
        $clock = ClockInOut::create([
            'clock_in' => $clockIn,
            'clock_out' => null,
            'duration' => null,
            'user_id' => $authUser->id,
            'location_id' => $request->location_id,
            'location_type' => $request->location_type,
            'late_arrive' => $late_arrive,
            'early_leave' => null,
        ]);

        return $this->returnData("clock", $clock, "Clock In Done");
    }
}
