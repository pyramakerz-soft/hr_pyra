<?php

namespace App\Traits;

use App\Http\Resources\Api\ClockResource;
use App\Models\ClockInOut;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
trait HelperTrait
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
        // Retrieve the user's assigned location by location_id
        $userLocation = $authUser->user_locations()
            ->where('user_locations.location_id', $location_id)
            ->where('locations.id', $location_id)
            ->first();
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

}
