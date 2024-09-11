<?php

namespace App\Traits;

use App\Http\Resources\ClockResource;
use App\Models\ClockInOut;
use Illuminate\Support\Carbon;
trait ClockTrait
{

    protected function prepareClockData($clocks)
    {
        // Ensure $clocks is paginated
        $isPaginated = $clocks instanceof \Illuminate\Pagination\LengthAwarePaginator;
        // dd($clocks->toArray());
        // Group clocks by date
        $groupedClocks = $clocks->groupBy(function ($clock) {
            return Carbon::parse($clock->clock_in)->toDateString();
        });

        $data = [];
        foreach ($groupedClocks as $date => $clocksForDay) {
            if (!$clocksForDay) {
                continue;
            }

            // Sort clocks by clock_in
            $clocksForDay = $clocksForDay->sortByDesc(function ($clock) {
                return Carbon::parse($clock->clock_in);
            });
            // dd($clocksForDay->toArray());
            // First clock of the day
            $firstClockAtTheDay = $clocksForDay->first();

            // Process other clocks of the day
            $otherClocksForDay = $clocksForDay->filter(function ($clock) use ($firstClockAtTheDay) {
                return $clock->id !== $firstClockAtTheDay->id;
            })->sortBy(function ($clock) {
                return Carbon::parse($clock->clock_in);
            })->map(function ($clock) {
                $clockIn = $clock->clock_in ? Carbon::parse($clock->clock_in) : null;
                $clockOut = $clock->clock_out ? Carbon::parse($clock->clock_out) : null;
                $totalHours = null;

                if ($clockIn && $clockOut) {
                    $durationInSeconds = $clockIn->diffInSeconds($clockOut);
                    $totalHours = gmdate('H:i', $durationInSeconds);
                } elseif ($clockIn) {
                    $durationInSeconds = $clockIn->diffInSeconds(Carbon::now());
                    $totalHours = gmdate('H:i', $durationInSeconds);
                }

                return [
                    'id' => $clock->id,
                    'clockIn' => $clockIn ? $clockIn->format('H:i') : null,
                    'clockOut' => $clockOut ? $clockOut->format('H:i') : null,
                    'totalHours' => $totalHours,
                    'site' => $clock->location_type,
                    'location_in' => $clock->location->address ?? null,
                    'location_out' => $clock->location->address ?? null,
                    'formattedClockIn' => $clockIn ? $clockIn->format('Y-m-d H:i') : null,
                    'formattedClockOut' => $clockOut ? $clockOut->format('Y-m-d H:i') : null,
                ];
            });

            // Calculate total duration
            $totalDurationInSeconds = 0;
            foreach ($clocksForDay as $clock) {
                if ($clock->clock_in) {
                    $clockIn = Carbon::parse($clock->clock_in);

                    if ($clock->clock_out) {
                        $clockOut = Carbon::parse($clock->clock_out);
                        $durationInSeconds = $clockIn->diffInSeconds($clockOut);
                    } else {
                        $durationInSeconds = $clockIn->diffInSeconds(Carbon::now());
                    }

                    $totalDurationInSeconds += $durationInSeconds;
                    $clock->duration = gmdate('H:i:s', $durationInSeconds);
                }
            }

            $totalDurationFormatted = gmdate('H:i:s', $totalDurationInSeconds);
            $firstClockAtTheDay->duration = $totalDurationFormatted;

            $data[] = (new ClockResource($firstClockAtTheDay))->toArray(request()) + [
                'otherClocks' => $otherClocksForDay->values()->toArray(),
                'totalHours' => $totalDurationFormatted,
            ];
        }

        return [
            'clocks' => $data,
            'pagination' => $isPaginated ? [
                'current_page' => $clocks->currentPage(),
                'next_page_url' => $clocks->nextPageUrl(),
                'previous_page_url' => $clocks->previousPageUrl(),
                'last_page' => $clocks->lastPage(),
                'total' => $clocks->total(),
            ] : null,
        ];
    }
    protected function haversineDistance(float $userLatitude, float $userLongitude, float $locationLatitude, float $locationLongitude)
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
    /**
     * Check if user has an existing clock-in without clock-out.
     */
    protected function hasExistingClockInWithoutClockOut($user_id)
    {
        return ClockInOut::where('user_id', $user_id)
            ->whereNull('clock_out')
            ->exists();
    }
    /**
     * Validate clock-in request.
     */
    protected function validateClockInRequest($request)
    {
        $request->validate([
            'location_type' => 'required|string|exists:work_types,name',
            'clock_in' => ['required', 'date_format:Y-m-d H:i:s'],
        ]);
    }

    /**
     * Handle clock-in for home location.
     */
    protected function handleHomeClockIn($request, $user_id)
    {
        $existingHomeClockIn = ClockInOut::where('user_id', $user_id)
            ->whereDate('clock_in', Carbon::today())
            ->where('location_type', "home")
            ->whereNull('clock_out')
            ->exists();

        if ($existingHomeClockIn) {
            return $this->returnError('You have already clocked in.');
        }

        $clockIn = Carbon::parse($request->clock_in);
        $duration = $clockIn->diffAsCarbonInterval(Carbon::now())->format('%H:%I:%S');

        $clock = ClockInOut::create([
            'clock_in' => $clockIn,
            'clock_out' => null,
            'duration' => $duration,
            'user_id' => $user_id,
            'location_type' => $request->location_type,
        ]);

        return $this->returnData("clock", $clock, "Clock In Done");
    }

    /**
     * Handle clock-in for site location.
     */
    protected function handleSiteClockIn($request, $authUser)
    {
        // Validate latitude and longitude
        $request->validate([
            'latitude' => 'required',
            'longitude' => 'required',
        ]);

        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $closestLocation = $this->getClosestUserLocation($authUser, $latitude, $longitude);
        dd($closestLocation);
        if (!$closestLocation) {
            return $this->returnError('User is not located at any registered locations.');
        }

        // Check if user has already clocked in today
        $existingSiteClockIn = ClockInOut::where('user_id', $authUser->id)
            ->where('location_id', $closestLocation['location_id'])
            ->whereDate('clock_in', Carbon::today())
            ->whereNull('clock_out')
            ->exists();

        if ($existingSiteClockIn) {
            return $this->returnError('You have already clocked in.');
        }

        return $this->createClockInRecord($request, $authUser, $closestLocation);
    }
    /**
     * Get the closest registered user location.
     */
    protected function getClosestUserLocation($authUser, $latitude, $longitude)
    {
        $userLocations = $authUser->user_locations()->get();
        dd($userLocations);
        $closestLocation = null;
        $shortestDistance = null;

        foreach ($userLocations as $userLocation) {
            $distance = $this->haversineDistance(
                $userLocation->latitude,
                $userLocation->longitude,
                $latitude,
                $longitude
            );

            if (is_null($shortestDistance) || $distance < $shortestDistance) {
                $shortestDistance = $distance;
                $closestLocation = [
                    'location_id' => $userLocation->pivot['location_id'],
                    'distance' => $distance,
                ];
            }
        }

        return $closestLocation;
    }
    /**
     * Create clock-in record for site location.
     */
    protected function createClockInRecord($request, $authUser, $closestLocation)
    {
        if ($closestLocation['distance'] < 50) {
            $clockIn = Carbon::parse($request->clock_in);
            $duration = $clockIn->diffAsCarbonInterval(Carbon::now())->format('%H:%I:%S');

            $clock = ClockInOut::create([
                'clock_in' => $clockIn,
                'clock_out' => null,
                'duration' => $duration,
                'user_id' => $authUser->id,
                'location_id' => $closestLocation['location_id'],
                'location_type' => $request->location_type,
            ]);

            return $this->returnData("clock", $clock, "Clock In Done");
        }

        return $this->returnError(
            'User is not located at the correct location. lat: ' . $request->latitude . ' / long: ' . $request->longitude
        );
    }
    //ClockOut Functions

    /**
     * Get the existing clock-in record without a clock-out for the user.
     */
    protected function getExistingClockInWithoutClockOut($user_id)
    {
        return ClockInOut::where('user_id', $user_id)
            ->whereNull('clock_out')
            ->orderBy('clock_in', 'desc')
            ->first();
    }
    /**
     * Validate the clock-out request.
     */
    protected function validateClockOutRequest($request)
    {
        $request->validate([
            'clock_out' => ['required', 'date_format:Y-m-d H:i:s'],
        ]);
    }

    /**
     * Handle clock-out for home location.
     */
    protected function handleHomeClockOut($clockInOut, $clockOut)
    {
        $clockIn = Carbon::parse($clockInOut->clock_in);
        $durationFormatted = $clockIn->diffAsCarbonInterval($clockOut)->format('%H:%I:%S');

        $clockInOut->update([
            'clock_out' => $clockOut,
            'duration' => $durationFormatted,
        ]);

        return $this->returnData("clock", $clockInOut, "Clock Out Done");
    }

    protected function handleSiteClockOut($request, $authUser, $clockInOut, $clockOut)
    {
        // Validate latitude and longitude for site-based clock-out
        $request->validate([
            'latitude' => 'required',
            'longitude' => 'required',
        ]);

        $latitude = $request->latitude;
        $longitude = $request->longitude;

        // Get the closest registered location based on the user's current position
        $closestLocation = $this->getClosestUserLocation($authUser, $latitude, $longitude);

        if (!$closestLocation || $closestLocation['location_id'] !== $clockInOut->location_id) {
            return $this->returnError('User is not located at the correct location.');
        }

        // If distance is acceptable (less than 50 meters), proceed with clock-out
        if ($closestLocation['distance'] < 50) {
            $clockIn = Carbon::parse($clockInOut->clock_in);
            $durationFormatted = $clockIn->diffAsCarbonInterval($clockOut)->format('%H:%I:%S');

            $clockInOut->update([
                'clock_out' => $clockOut,
                'duration' => $durationFormatted,
            ]);

            return $this->returnData("clock", $clockInOut, "Clock Out Done");
        } else {
            return $this->returnError(
                'User is not located at the correct location. lat: ' . $latitude . " / long: " . $longitude
            );
        }
    }

    //Update ClockInOut

    /**
     * Validate the request for updating clock-in/out times.
     */
    protected function validateUpdateClockRequest($request)
    {
        $request->validate([
            'clock_in' => ['nullable', 'date_format:Y-m-d H:i'],
            'clock_out' => ['nullable', 'date_format:Y-m-d H:i'],
        ]);
    }

    /**
     * Check if the clock entry belongs to the user.
     */
    protected function getUserClock($userId, $clockId)
    {
        return ClockInOut::where('user_id', $userId)
            ->where('id', $clockId)
            ->first();
    }

    /**
     * Update the clock entry for the user.
     */
    protected function updateClockEntry($request, $clock, $user)
    {
        $clockIn = $this->getClockInTime($request, $clock);
        $clockOut = $this->getClockOutTime($request, $clock);

        // Check if clock_in and clock_out are on the same day
        if (!$clockOut->isSameDay($clockIn)) {
            return $this->returnError("Clock-in and clock-out must be on the same day", 400);
        }

        // Ensure clock_out is after clock_in
        if ($clockOut->lessThanOrEqualTo($clockIn)) {
            return $this->returnError("Clock-out must be after clock-in", 400);
        }

        // Calculate the duration
        $durationFormatted = $clockIn->diff($clockOut)->format('%H:%I:%S');

        // Update clock record
        $clock->update([
            'clock_in' => $clockIn->format('Y-m-d H:i:s'),
            'clock_out' => $clockOut->format('Y-m-d H:i:s'),
            'duration' => $durationFormatted,
        ]);

        return $this->returnData("clock", new ClockResource($clock), "Clock Updated Successfully for {$user->name}");
    }

    /**
     * Get the clock-in time.
     */
    protected function getClockInTime($request, $clock)
    {
        if ($request->clock_in) {
            return Carbon::createFromFormat('Y-m-d H:i', $request->clock_in);
        }

        return Carbon::parse($clock->clock_in);
    }

    /**
     * Get the clock-out time.
     */
    protected function getClockOutTime($request, $clock)
    {
        if ($request->clock_out) {
            return Carbon::createFromFormat('Y-m-d H:i', $request->clock_out);
        }

        return Carbon::parse($clock->clock_out);
    }

}
