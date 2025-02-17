<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

trait LocationHelperTrait
{
     use HelperTrait;

    
    protected function validateLocations($request, $authUser)
    {
        // Retrieve location details using location_id from the request
        $location_id = $request->location_id;

        // Validate that the location_id is assigned to the user
        $userLocation = $this->getUserAssignedLocationById($authUser, $location_id);
        if (is_null($userLocation)) {
            Log::info("This Email: {$authUser->email} not assigned to this location");
            return $this->returnError('User is not assigned to this location');
        }
        $range = $userLocation->range ?? 350;
        // Validate latitude and longitude comparison with the assigned location
        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $distance = $this->haversineDistance($latitude, $longitude, $userLocation->latitude, $userLocation->longitude);
        // Check if user is within an acceptable range (e.g., 50 meters)
        if ($distance > $range) {
            // Log and return error response if user is not within the range
            Log::info("Distance exceeds {$range} meters. Returning error.");
            return $this->returnError('User is not located at the correct location.');
        }
        // Return the validated location
        return $userLocation;
    }
}
