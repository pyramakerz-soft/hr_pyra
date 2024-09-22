<?php

namespace App\Traits;

use App\Http\Resources\ClockResource;
use App\Models\ClockInOut;
use App\Traits\HelperTrait;
use Illuminate\Support\Carbon;
trait ClockTrait
{
    use ClockValidator;
    use HelperTrait;
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

    //Update Clock

    protected function getUserClock($userId, $clockId)
    {
        return ClockInOut::where('user_id', $userId)
            ->where('id', $clockId)
            ->first();
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

    // protected function calculateDuration($clockIn, $clockOut)
    // {
    //     return $clockOut ? $clockIn->diff($clockOut)->format('%H:%I:%S') : null;
    // }
    protected function isFactoryOrAcademicSchool($user)
    {
        return $user->department->name === 'Factory' || $user->department->name === 'Academic_school';
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
        if ($this->isFactoryOrAcademicSchool($user)) {
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
        $durationFormatted = $clockOut ? $clockIn->diff($clockOut)->format('%H:%I:%S') : null;

        // Get start and end times from the user's details (for home)
        $startTime = Carbon::parse($user->user_detail->start_time)->format('H:i:s');
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
