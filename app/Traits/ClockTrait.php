<?php

namespace App\Traits;

use App\Http\Requests\Api\AddClockRequest;
use App\Http\Resources\ClockResource;
use App\Models\ClockInOut;
use App\Models\User;
use Illuminate\Support\Carbon;
trait ClockTrait
{

    protected function prepareClockData($clocks)
    {
        // Ensure $clocks is paginated
        $isPaginated = $clocks instanceof \Illuminate\Pagination\LengthAwarePaginator;

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
            // dd($clocksForDay);
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
    protected function getUserAssignedLocationById($authUser, $location_id)
    {
        // Retrieve the user's assigned location by location_id
        $userLocation = $authUser->user_locations()
            ->where('user_locations.location_id', $location_id)
            ->where('locations.id', $location_id)
            ->first();
        return $userLocation;
    }

    protected function getOrCheckExistingClockInWithoutClockOut($user_id, $returnRecord = false)
    {
        $query = ClockInOut::where('user_id', $user_id)
            ->whereNull('clock_out')
            ->orderBy('clock_in', 'desc');
        if ($returnRecord) {
            return $query->first();
        }
        return $query->exists();
    }
    //Update Clock

    /**
     * Validate the request for updating clock-in/out times.
     */
    // protected function validateUpdateClockRequest($request)
    // {
    //     $request->validate([
    //         'clock_in' => ['nullable', 'date_format:Y-m-d H:i'],
    //         'clock_out' => ['nullable', 'date_format:Y-m-d H:i'],
    //     ]);
    // }

    /**
     * Check if the clock entry belongs to the user.
     */

    protected function getUserClock($userId, $clockId)
    {
        return ClockInOut::where('user_id', $userId)
            ->where('id', $clockId)
            ->first();
    }
    //HR Clock

    protected function handleAddSiteClockByHr(AddClockRequest $request, User $user)
    {
        // Retrieve location details using location_id from the request
        $location_id = $request->location_id;

        // Validate that the location_id is assigned to the user
        $userLocation = $user->user_locations()->where('location_id', $location_id)->first();

        if (!$userLocation) {
            return $this->returnError('The specified location is not assigned to the user.');
        }

        // Parse the clock-in time from the request
        $clockIn = Carbon::parse($request->clock_in);

        // Get the start time for the location
        $locationStartTime = Carbon::parse($userLocation->start_time);

        $late_arrive = $clockIn->greaterThan($locationStartTime)
        ? $locationStartTime->diff($clockIn)->format('%H:%I:%S')
        : "00:00:00";

        // Calculate the duration (clock_in to now or clock_out if provided)
        $duration = $clockIn->diffAsCarbonInterval(Carbon::now())->format('%H:%I:%S');

        // Create the clock-in record
        $clock = ClockInOut::create([
            'clock_in' => $clockIn,
            'clock_out' => null,
            'duration' => $duration,
            'user_id' => $user->id,
            'location_id' => $location_id,
            'location_type' => 'site',
            'late_arrive' => $late_arrive, // Store late_arrive
            'early_leave' => null, // Initialize early_leave as null
        ]);

        return $this->returnData('clock', $clock, 'Clock-in added by HR for site.');
    }

    // /**
    //  * Update the clock entry for the user.
    //  */
    // protected function updateClockEntry($request, $clock, $user)
    // {
    //     $clockIn = $this->getClockInTime($request, $clock);
    //     $clockOut = $this->getClockOutTime($request, $clock);

    //     // Check if clock_in and clock_out are on the same day
    //     if (!$clockOut->isSameDay($clockIn)) {
    //         return $this->returnError("Clock-in and clock-out must be on the same day", 400);
    //     }

    //     // Ensure clock_out is after clock_in
    //     if ($clockOut->lessThanOrEqualTo($clockIn)) {
    //         return $this->returnError("Clock-out must be after clock-in", 400);
    //     }

    //     // Calculate the duration
    //     if (!$clockOut) {
    //         $durationFormatted = $clockIn->diff(Carbon::now())->format('%H:%I:%S');
    //     }
    //     $durationFormatted = $clockIn->diff($clockOut)->format('%H:%I:%S');

    //     // Update clock record
    //     $clock->update([
    //         'clock_in' => $clockIn->format('Y-m-d H:i:s'),
    //         'clock_out' => $clockOut->format('Y-m-d H:i:s'),
    //         'duration' => $durationFormatted,

    //     ]);

    //     return $this->returnData("clock", new ClockResource($clock), "Clock Updated Successfully for {$user->name}");
    // }

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

    protected function updateClockEntry($request, $clock, $user)
    {
        if ($clock->location_type == 'site') {
            return $this->updateSiteClock($request, $clock, $user);
        }

        if ($clock->location_type == 'home') {
            return $this->updateHomeClock($request, $clock, $user);
        }

        return $this->returnError('Unknown location type', 400);
    }
    protected function updateSiteClock($request, $clock, $user)
    {
        $clockIn = $this->getClockInTime($request, $clock);
        $clockOut = $this->getClockOutTime($request, $clock);

        // If clock_out is null, use the current time (now) for calculating the duration
        // $clockOut = $clockOut ?? Carbon::now();

        if (!$clockOut->isSameDay($clockIn)) {
            return $this->returnError("Clock-in and clock-out must be on the same day", 400);
        }

        if ($clockOut->lessThanOrEqualTo($clockIn)) {
            return $this->returnError("Clock-out must be after clock-in", 400);
        }
        // If clock_out is null, calculate duration from clock_in to now
        $durationFormatted = $clockOut ? $clockIn->diff($clockOut)->format('%H:%I:%S') : $clockIn->diff(Carbon::now())->format('%H:%I:%S');

        // Get start and end times from the user's assigned location
        $userLocation = $user->user_locations()->first();
        $startTime = Carbon::parse($userLocation->start_time)->format('H:i:s');
        $endTime = Carbon::parse($userLocation->end_time)->format('H:i:s');

        //Extract ClockIn & ClockOut Time only
        $clockInTime = $clockIn->format('H:i:s');
        $clockOutTime = $clockOut->format('H:i:s');

        // Calculate late_arrive and early_leave based on time only
        $late_arrive = ($clockInTime > $startTime) ? Carbon::createFromTimeString($startTime)->diff(Carbon::createFromTimeString($clockInTime))->format('%H:%I:%S') : '00:00:00';
        $early_leave = ($clockOutTime < $endTime) ? Carbon::createFromTimeString($endTime)->diff(Carbon::createFromTimeString($clockOutTime))->format('%H:%I:%S') : '00:00:00';

        // Update clock record
        $clock->update([
            'clock_in' => $clockIn->format('Y-m-d H:i:s'),
            'clock_out' => $clockOut->format('Y-m-d H:i:s'),
            'duration' => $durationFormatted,
            'late_arrive' => $late_arrive,
            'early_leave' => $early_leave,
        ]);

        return $this->returnData("clock", new ClockResource($clock), "Clock Updated Successfully for {$user->name}");
    }
    protected function updateHomeClock($request, $clock, $user)
    {
        $clockIn = $this->getClockInTime($request, $clock);
        $clockOut = $this->getClockOutTime($request, $clock);

        // If clock_out is null, use the current time (now) for calculating the duration

        if (!$clockOut->isSameDay($clockIn)) {
            return $this->returnError("Clock-in and clock-out must be on the same day", 400);
        }

        if ($clockOut->lessThanOrEqualTo($clockIn)) {
            return $this->returnError("Clock-out must be after clock-in", 400);
        }

        // Calculate the duration
        // If clock_out is null, calculate duration from clock_in to now
        $durationFormatted = $clockOut ? $clockIn->diff($clockOut)->format('%H:%I:%S') : $clockIn->diff(Carbon::now())->format('%H:%I:%S');

        // Get start and end times from the user's details (for home)
        $startTime = Carbon::parse($user->user_detail->start_time)->format('H:i:s');
        // dd($startTime);
        $endTime = Carbon::parse($user->user_detail->end_time)->format('H:i:s');
        //Extract ClockIn & ClockOut Time only
        $clockInTime = $clockIn->format('H:i:s');
        $clockOutTime = $clockOut->format('H:i:s');

        // Calculate late_arrive and early_leave based on time only
        $late_arrive = ($clockInTime > $startTime) ? Carbon::createFromTimeString($startTime)->diff(Carbon::createFromTimeString($clockInTime))->format('%H:%I:%S') : '00:00:00';
        $early_leave = ($clockOutTime < $endTime) ? Carbon::createFromTimeString($endTime)->diff(Carbon::createFromTimeString($clockOutTime))->format('%H:%I:%S') : '00:00:00';
        // Update clock record
        $clock->update([
            'clock_in' => $clockIn->format('Y-m-d H:i:s'),
            'clock_out' => $clockOut->format('Y-m-d H:i:s'),
            'duration' => $durationFormatted,
            'late_arrive' => $late_arrive,
            'early_leave' => $early_leave,
        ]);

        return $this->returnData("clock", new ClockResource($clock), "Clock Updated Successfully for {$user->name}");
    }

}
