<?php

namespace App\Traits;

trait HelperTrait
{

    public function haversineDistance(float $userLatitude, float $userLongitude, float $locationLatitude, float $locationLongitude)
    {
        $R = 6371000; // Earth's radius in metres

        $userLatitudeRad = $userLatitude * M_PI / 180; // Convert user latitude from degrees to radians
        $locationLatitudeRad = $locationLatitude * M_PI / 180; // Convert location latitude from degrees to radians

        $deltaLatitude = ($locationLatitude - $userLatitude) * M_PI / 180; // Difference in latitude in radians
        $deltaLongitude = ($locationLongitude - $userLongitude) * M_PI / 180; // Difference in longitude in radians

        $a = sin($deltaLatitude / 2) * sin($deltaLatitude / 2) +
        cos($userLatitudeRad) * cos($locationLatitudeRad) *
        sin($deltaLongitude / 2) * sin($deltaLongitude / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        $distance = $R * $c; // Distance in metres
        return $distance;
    }
    public function uploadImage($request, $inputName = 'image', $directory = 'assets/images/Users')
    {
        if ($request->hasFile($inputName)) {
            $newImageName = uniqid() . "-employee." . $request->file($inputName)->extension();
            $request->file($inputName)->move(public_path($directory), $newImageName);
            return asset($directory . '/' . $newImageName);
        }
        return false;
    }

}