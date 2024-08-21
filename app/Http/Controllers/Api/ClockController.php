<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClockInOutRequest;
use App\Http\Requests\UpdateClockInOutRequest;
use App\Http\Resources\ClockResource;
use App\Models\ClockInOut;
// use App\Models\Request;
use App\Models\User;
use App\Traits\HelperTrait;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class ClockController extends Controller
{
    use ResponseTrait;
    use HelperTrait;

    public function getUserClocksById(User $user)
    {
        $authUser = Auth::user();
        if (!$authUser->hasRole('Hr')) {
            return $this->returnError('You are not authorized to view users', 403);
        }
        $clocks = ClockInOut::where('user_id', $user->id)
            ->orderBy('clock_in', 'desc')
            ->get();

        if ($clocks->isEmpty()) {
            return $this->returnError('No Clocks For this user found');
        }

        $groupedClocks = $clocks->groupBy(function ($clock) {
            return Carbon::parse($clock->clock_in)->toDateString();
        });

        $data = [];

        foreach ($groupedClocks as $date => $clocksForDay) {
            $firstClockForDay = $clocksForDay->last();

            $otherClocksForDay = $clocksForDay->slice(1)->map(function ($clock) {
                return [
                    'clockIn' => Carbon::parse($clock->clock_in)->format('h:iA'),
                    'clockOut' => $clock->clock_out ? Carbon::parse($clock->clock_out)->format('h:iA') : null,
                ];
            });

            $data[] = (new ClockResource($firstClockForDay))->toArray(request()) + ['otherClocks' => $otherClocksForDay->values()->toArray()];
        }

        return $this->returnData("data", ['clocks' => $data], "Clocks Data for {$user->name}");
    }

    public function clockIn(StoreClockInOutRequest $request)
    {
        $authUser = Auth::user();

        $this->validate($request, [
            'latitude' => 'required',
            'longitude' => 'required',
        ]);

        $user_id = $authUser->id;
        $latitude = $request->latitude;
        $longitude = $request->longitude;

        $userLocations = $authUser->user_locations()->get();
        $closestLocation = null;
        $shortestDistance = null;

        foreach ($userLocations as $userLocation) {
            $location_id = $userLocation->pivot['location_id'];
            $userLongitude = $userLocation->longitude;
            $userLatitude = $userLocation->latitude;

            $distance = $this->haversineDistance($userLatitude, $userLongitude, $latitude, $longitude);

            if (is_null($shortestDistance) || $distance < $shortestDistance) {
                $shortestDistance = $distance;
                $closestLocation = [
                    'location_id' => $location_id,
                    'distance' => $distance,
                ];
            }
        }

        if (is_null($closestLocation)) {
            return $this->returnError('User is not located at any registered locations.');
        }

        $location_id = $closestLocation['location_id'];
        $distanceBetweenUserAndLocation = $closestLocation['distance'];
        $now = Carbon::now()->addRealHour(3);
        $UserEndTime = Carbon::createFromTimeString($authUser->user_detail->end_time);

        if ($now->greaterThan($UserEndTime)) {
            return $this->returnError('Your shift has already ended, you cannot clock in.');
        }
        $existingClockIn = ClockInOut::where('user_id', $user_id)
            ->where('location_id', $location_id)
            ->whereDate('clock_in', Carbon::today())
            ->whereNull('clock_out')
            ->orderBy('clock_in', 'desc')
            ->exists();
        if ($existingClockIn) {
            return $this->returnError('You have already clocked in.');
        }

        if ($distanceBetweenUserAndLocation < 50) {
            $clock = ClockInOut::create([
                'clock_in' => Carbon::now()->addRealHour(3),
                'clock_out' => null,
                'duration' => null,
                'user_id' => $user_id,
                'location_id' => $location_id,
            ]);
            return $this->returnData("clock", $clock, "Clock In Done");
        } else {
            return $this->returnError('User is not located at the correct location.');
        }
    }

    public function clockOut(UpdateClockInOutRequest $request)
    {
        $this->validate($request, [
            'latitude' => 'required',
            'longitude' => 'required',
        ]);

        $latitude = $request->latitude;
        $longitude = $request->longitude;

        $authUser = Auth::user();

        $ClockauthUser = ClockInOut::where('user_id', $authUser->id)
            ->whereNull('clock_out')
            ->orderBy('clock_in', 'desc')
            ->first();

        if (!$ClockauthUser) {
            return $this->returnError('You are not clocked in.');
        }

        $user_location = $authUser->user_locations()->wherePivot('location_id', $ClockauthUser->location_id)->first();

        if (!$user_location) {
            return $this->returnError('User is not located at the correct location.');
        }

        $userLongitude = $user_location->longitude;
        $userLatitude = $user_location->latitude;

        $distanceBetweenUserAndLocation = $this->haversineDistance($userLatitude, $userLongitude, $latitude, $longitude);

        if ($distanceBetweenUserAndLocation < 50) {
            $clock_in = Carbon::parse($ClockauthUser->clock_in);
            $clock_out = Carbon::now()->addRealHour(3);
            $durationInterval = $clock_out->diffAsCarbonInterval($clock_in);
            $durationFormatted = $durationInterval->format('%H:%I:%S');

            $ClockauthUser->update([
                'clock_out' => $clock_out,
                'duration' => $durationFormatted,
            ]);

            return $this->returnData("clock", $ClockauthUser, "Clock Out Done");
        } else {
            return $this->returnError('User is not located at the correct location.');
        }
    }

    public function showUserClocks()
    {
        $authUser = Auth::user();

        // Fetch all clocks for the user, ordered by clock_in ascending
        $clocks = ClockInOut::where('user_id', $authUser->id)
            ->orderBy('clock_in', 'desc')
            ->get();

        if ($clocks->isEmpty()) {
            return $this->returnError('No Clocks For this user found');
        }

        // Group clocks by date
        $groupedClocks = $clocks->groupBy(function ($clock) {
            return Carbon::parse($clock->clock_in)->toDateString();
        });

        // Prepare the final data array
        $data = [];

        foreach ($groupedClocks as $date => $clocksForDay) {
            // Get the first clock for the day
            $firstClockForDay = $clocksForDay->last();

            // Get other clocks for the day excluding the first one
            $otherClocksForDay = $clocksForDay->slice(1)->map(function ($clock) {
                return [
                    'clockIn' => Carbon::parse($clock->clock_in)->format('h:iA'),
                    'clockOut' => $clock->clock_out ? Carbon::parse($clock->clock_out)->format('h:iA') : null,
                ];
            });

            // Add the first clock with otherClocks to the data array
            $data[] = (new ClockResource($firstClockForDay))->toArray(request()) + ['otherClocks' => $otherClocksForDay->values()->toArray()];
        }

        return $this->returnData("data", ['clocks' => $data], "Clocks Data for {$authUser->name}");
    }
    // public function updateUserClock(Request $request, User $user, ClockInOut $clock)
    // {
    //     $authUser = Auth::user();
    //     if (!$authUser->hasRole('Hr')) {
    //         return $this->returnError('You are not authorized to Update users', 403);
    //     }
    //     $this->validate($request, [
    //         'clock_in' => ['required', 'date_format:H:i'],
    //         'clock_out' => ['required', 'date_format:H:i'],

    //     ]);
    //     $clock = ClockInOut::where('user_id', $user->id)->where('id', $clock->id)->first();
    //     $clock->update([
    //         'clock_in' => $request->clock_in,
    //         'clock_out' => $request->clock_out,
    //     ]);
    //     return $this->returnData("clock", new ClockResource($clock), "Clocks Data for {$user->name}");

    // }
}