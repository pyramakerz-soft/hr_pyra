<?php

namespace Modules\Clocks\Traits;

use GuzzleHttp\Client;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Modules\Clocks\Models\ClockInOut;
use Modules\Clocks\Resources\Api\ClockResource;

trait ClockCalculationsHelperTrait
{
    // protected $resource;
    // public function __construct(IResource $resource)
    // {
    //     $this->resource = $resource;
    // }
    protected function calculateLateArrive($clockIn, $startTime)
    {
        // Extract the time portion only
        $clockInTime = carbon::parse($clockIn)->format('H:i:s');
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
    protected function calculateEarlyLeave($clockOut, $endTime)
    {

        // Extract the time portion only
        $clockOutTime = Carbon::parse($clockOut)->format('H:i:s');
        $endTimeFormatted = Carbon::parse($endTime)->format('H:i:s');

        // Initialize late_arrive as "00:00:00" (no late arrival by default)
        $early_leave = "00:00:00";

        // Check if the user clocked in late (after the start time)
        if ($clockOutTime < $endTimeFormatted) {
            // Calculate the late arrival duration and format it as H:i:s
            $early_leave = Carbon::createFromFormat('H:i:s', $endTimeFormatted)
                ->diff(Carbon::createFromFormat('H:i:s', $clockOutTime))
                ->format('%H:%I:%S');
        }

        return $early_leave;
    }
    protected function calculateDuration($clockIn, $clockOut)
    {
        return $clockOut ? Carbon::parse($clockIn)->diff(Carbon::parse($clockOut))->format('%H:%I:%S') : null;
    }
    protected function isLocationTime($authUser)
    {
        if (!$authUser || !$authUser->department) {
            return false;
        }
        return (bool) $authUser->department->is_location_time;
    }
    protected function getUserClock($userId, $clockId)
    {
        $clock = ClockInOut::where('user_id', $userId)
            ->where('id', $clockId)
            ->first();
        if (!$clock) {
            throw ValidationException::withMessages(['error' => 'No clocks found for this user']);
        }
        return $clock;
    }
    protected function getClockInTime($request, $clock)
    {
        if ($request->clock_in) {
            return Carbon::createFromFormat('Y-m-d H:i', $request->clock_in);
        }

        return Carbon::parse($clock->clock_in);
    }

    protected function getClockOutTime($request, $clock)
    {
        if ($request->clock_out) {
            return Carbon::createFromFormat('Y-m-d H:i', $request->clock_out);
        }

        return Carbon::parse($clock->clock_out);
    }
    protected function getUserLocationTimes($user)
    {
        $userLocation = $user->user_locations()->first();
        return [
            'start_time' => Carbon::parse($userLocation->start_time)->format('H:i:s'),
            'end_time' => Carbon::parse($userLocation->end_time)->format('H:i:s'),
        ];
    }
    protected function getUserDetailTimes($user)
    {
        return [
            'start_time' => Carbon::parse($user->user_detail->start_time)->format('H:i:s'),
            'end_time' => Carbon::parse($user->user_detail->end_time)->format('H:i:s'),
        ];
    }
    protected function checkExistingClockInWithoutClockOut($user_id)
    {
        $query = ClockInOut::where('user_id', $user_id)
            ->whereNull('clock_out')
            ->orderBy('clock_in', 'desc')
            ->exists();

        return $query;
    }
    protected function getExistingClockInWithoutClockOut($user_id)
    {
        $query = ClockInOut::where('user_id', $user_id)
            ->whereNull('clock_out')
            ->orderBy('clock_in', 'desc')
            ->first();

        return $query;
    }

    protected function getUserAssignedLocationById($authUser, $location_id)
    {
        // Log function parameters
        Log::info('getUserAssignedLocationById called', [
            'authUserId' => $authUser->id ?? 'N/A',
            'location_id' => $location_id
        ]);

        // Retrieve the user's assigned location by location_id
        $userLocation = $authUser->user_locations()
            ->where('user_locations.location_id', $location_id)
            ->where('locations.id', $location_id)
            ->first();

        // Log the query result
        Log::info('User location fetched', [
            'userLocation' => $userLocation
        ]);

        return $userLocation;
    }

    protected function prepareClockData($clocks)
    {
        // Ensure $clocks is paginated
        $isPaginated = $clocks instanceof LengthAwarePaginator;

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
            // Process each clock of the day
            foreach ($clocksForDay as $clock) {
                // Use ClockResource to format each clock and add it to data
                $data[] = (new ClockResource($clock))->toArray(request());
            }
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




    protected function groupClockForUser($clocks)
    {

        // Ensure $clocks is paginated
        $isPaginated = $clocks instanceof LengthAwarePaginator;

        // Group clocks by date
        $groupedClocks = $clocks->groupBy(function ($clock) {
            $timezoneValue = $clock->user->timezone ? $clock->user->timezone->value : 3;  // Default to +3 if no timezone
            return Carbon::parse($clock->clock_in)->addHours(value: $timezoneValue)->toDateString(); // Apply +3 offset
        });

        $data = [];
        foreach ($groupedClocks as $date => $clocksForDay) {
            if ($clocksForDay->isEmpty()) {
                continue;
            }

            // Sort clocks by adjusted clock_in time (descending)
            $sortedClocks = $clocksForDay->sortByDesc(function ($clock) {
                return Carbon::parse($clock->clock_in)->addHours(3);
            })->values(); // Reset array keys

            // Format each clock and apply +3 offset
            $formattedClocks = $sortedClocks->map(function ($clock) {
                // $timezoneValue = 0;  // Default to +3 if no timezone

                $clock->clock_in = Carbon::parse($clock->clock_in)->format('Y-m-d H:i:s');

                if ($clock->clock_out) {
                    $clock->clock_out = Carbon::parse($clock->clock_out)->format('Y-m-d H:i:s');
                }

                return (new ClockResource($clock))->toArray(request());
            });

            // Group under the respective date
            $data[] = [
                'Date' => $date,
                'clocks' => $formattedClocks,
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


    protected function getAddressFromCoordinates($latitude, $longitude)
    {
        $client = new Client([
            'timeout' => 15,
            'connect_timeout' => 5,
        ]);

        // Try multiple FREE geocoding services in order of preference (no API keys required)
        $geocodingServices = [
            'bigdatacloud' => function () use ($client, $latitude, $longitude) {
                return $this->tryBigDataCloudGeocoding($client, $latitude, $longitude);
            },
            'photon' => function () use ($client, $latitude, $longitude) {
                return $this->tryPhotonGeocoding($client, $latitude, $longitude);
            },
            'nominatim' => function () use ($client, $latitude, $longitude) {
                return $this->tryNominatimGeocoding($client, $latitude, $longitude);
            }
        ];

        foreach ($geocodingServices as $serviceName => $serviceFunction) {
            try {
                $result = $serviceFunction();
                if ($result && isset($result['display_name'])) {
                    Log::info("Address fetched successfully from {$serviceName}", [
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                        'address' => $result['display_name']
                    ]);
                    return $result;
                }
            } catch (\Exception $e) {
                Log::warning("Geocoding service {$serviceName} failed: " . $e->getMessage(), [
                    'latitude' => $latitude,
                    'longitude' => $longitude
                ]);
                continue; // Try next service
            }
        }

        // Final fallback to coordinate-based address
        Log::info("All geocoding services failed, falling back to coordinate-based address", [
            'latitude' => $latitude,
            'longitude' => $longitude
        ]);

        return [
            'display_name' => "Location: {$latitude}, {$longitude}",
            'lat' => $latitude,
            'lon' => $longitude,
            'fallback' => true,
            'address' => [
                'road' => "Location: {$latitude}, {$longitude}"
            ]
        ];
    }

    private function tryNominatimGeocoding($client, $latitude, $longitude)
    {
        // Add a delay to respect rate limits (1 request per second maximum)

        $response = $client->get('https://nominatim.openstreetmap.org/reverse', [
            'headers' => [
                'User-Agent' => 'HRAttendanceApp/1.0',
                'Accept' => 'application/json',
                'Accept-Language' => 'en'
            ],
            'query' => [
                'lat' => $latitude,
                'lon' => $longitude,
                'format' => 'json',
                'addressdetails' => '1',
                'zoom' => '16', // Reduced zoom level for better stability
                'limit' => '1',
            ]
        ]);

        $data = json_decode($response->getBody()->getContents(), true);
        return $data && isset($data['display_name']) ? $data : null;
    }

    private function tryPhotonGeocoding($client, $latitude, $longitude)
    {
        // Photon - Free geocoding service by OpenStreetMap
        $response = $client->get('https://photon.komoot.io/reverse', [
            'headers' => [
                'Accept' => 'application/json',
            ],
            'query' => [
                'lat' => $latitude,
                'lon' => $longitude,
                'limit' => '1',
            ]
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        if ($data && isset($data['features']) && count($data['features']) > 0) {
            $feature = $data['features'][0];
            $properties = $feature['properties'] ?? [];

            // Build display name from available properties
            $addressParts = [];
            if (isset($properties['name']))
                $addressParts[] = $properties['name'];
            if (isset($properties['street']))
                $addressParts[] = $properties['street'];
            if (isset($properties['city']))
                $addressParts[] = $properties['city'];
            if (isset($properties['country']))
                $addressParts[] = $properties['country'];

            return [
                'display_name' => implode(', ', $addressParts) ?: "Location: {$latitude}, {$longitude}",
                'lat' => $latitude,
                'lon' => $longitude,
                'address' => $properties
            ];
        }

        return null;
    }

    private function tryBigDataCloudGeocoding($client, $latitude, $longitude)
    {
        // BigDataCloud - Free reverse geocoding service
        $response = $client->get('https://api.bigdatacloud.net/data/reverse-geocode-client', [
            'headers' => [
                'Accept' => 'application/json',
            ],
            'query' => [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'localityLanguage' => 'en',
            ]
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        if ($data && isset($data['locality'])) {
            // Build display name from BigDataCloud response
            $addressParts = [];
            if (isset($data['locality']))
                $addressParts[] = $data['locality'];
            if (isset($data['city']))
                $addressParts[] = $data['city'];
            if (isset($data['principalSubdivision']))
                $addressParts[] = $data['principalSubdivision'];
            if (isset($data['countryName']))
                $addressParts[] = $data['countryName'];

            return [
                'display_name' => implode(', ', $addressParts) ?: "Location: {$latitude}, {$longitude}",
                'lat' => $latitude,
                'lon' => $longitude,
                'address' => [
                    'road' => $data['locality'] ?? '',
                    'city' => $data['city'] ?? '',
                    'state' => $data['principalSubdivision'] ?? '',
                    'country' => $data['countryName'] ?? ''
                ]
            ];
        }

        return null;
    }



    protected function updateClockRecord($clock, $clockIn, $clockOut, $duration, $lateArrive, $earlyLeave)
    {
        $clockIn = Carbon::parse($clockIn)->subHours(3);
        $clockOut = Carbon::parse($clockOut)->subHours(3);
        $clock->update([
            'clock_in' => $clockIn->format('Y-m-d H:i:s'),
            'clock_out' => $clockOut->format('Y-m-d H:i:s'),
            'duration' => $duration,
            'late_arrive' => $lateArrive,
            'early_leave' => $earlyLeave,
        ]);

        if (method_exists($this, 'recordDailyOvertime')) {
            $this->recordDailyOvertime($clock);
        }

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




    protected function validateClockTime($clockIn, $clockOut)
    {
        if ($clockOut->lessThanOrEqualTo($clockIn)) {
            throw ValidationException::withMessages(['error' => "You can't clock out before or at the same time as clock in."]);
        }
    }


    protected function validateLocations($request, $authUser)
    {
        // Retrieve location details using location_id from the request
        $location_id = $request->location_id;
        // Log::error("Error fetching address: " . $request->location_id);
        //         Log::error("Error fetching address: " . $authUser);

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
        // Check if user is within the acceptable range
        if ($distance > $range) {
            $dist = round($distance - $range, 1);
            Log::info("User " . $authUser->name . " location: {$userLocation->name}____({$latitude}, {$longitude}) is outside the range of {$dist} meters. Returning error.");
            return $this->returnError("You are not located at the correct location of {$userLocation->name}" . " you are outside with {$dist} meters outside the range.");
        }
        // Return the validated location
        return $userLocation;
    }
}
