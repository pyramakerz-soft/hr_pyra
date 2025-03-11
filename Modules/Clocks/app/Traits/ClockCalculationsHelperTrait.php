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
        return $authUser->department->is_location_time ? true : false;
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
<<<<<<< HEAD
            
            // Log::error("Error fetching address: " . $userLocation.'\n'.$authUser.'\n'.$location_id);
=======
    
        // Log the query result
        Log::info('User location fetched', [
            'userLocation' => $userLocation
        ]);
    
>>>>>>> a623b53a (finished phase2)
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
        return Carbon::parse($clock->clock_in)->toDateString(); // Group by clock_in date
    });

    $data = [];
    foreach ($groupedClocks as $date => $clocksForDay) {
        if ($clocksForDay->isEmpty()) {
            continue;
        }

        // Sort clocks by clock_in time (descending order)
        $sortedClocks = $clocksForDay->sortByDesc(function ($clock) {
            return Carbon::parse($clock->clock_in);
        })->values(); // Reset array keys

        // Format each clock using ClockResource
        $formattedClocks = $sortedClocks->map(function ($clock) {
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
        $client = new Client();
        $url = "https://nominatim.openstreetmap.org/reverse?lat={$latitude}&lon={$longitude}&format=json";

        try {
            $response = $client->get($url, [
                'headers' => [
                    'User-Agent' => 'YourAppName/1.0',
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            Log::error("Error fetching address: " . $e->getMessage());
            return null;
        }
    }
  

    
    protected function updateClockRecord($clock, $clockIn, $clockOut, $duration, $lateArrive, $earlyLeave)
    {
        $clock->update([
            'clock_in' => $clockIn->format('Y-m-d H:i:s'),
            'clock_out' => $clockOut->format('Y-m-d H:i:s'),
            'duration' => $duration,
            'late_arrive' => $lateArrive,
            'early_leave' => $earlyLeave,
        ]);
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
    
        if (!$clockIn->isSameDay($clockOut)) {


            throw ValidationException::withMessages(['error' => 'Clock-out must be on the same day as clock-in.']);
        }
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
            Log::info("User location: ({$latitude}, {$longitude}) is outside the range of {$range} meters. Returning error.");
            return $this->returnError('User is not located at the correct location.'.' User location: ({$latitude}, {$longitude}) is outside the range of {$range} meters. Returning error.');
        }
        // Return the validated location
        return $userLocation;
    }


}
