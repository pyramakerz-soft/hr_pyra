<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClockInOutRequest;
use App\Http\Requests\UpdateClockInOutRequest;
use App\Http\Resources\ClockResource;
use App\Models\ClockInOut;
use App\Models\User;
use App\Traits\HelperTrait;
use App\Traits\ResponseTrait;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class ClockController extends Controller
{
    use ResponseTrait;
    use HelperTrait;

    public function getUserClockById(User $user)
    {
        $authUser = Auth::user();
        if (!$authUser) {
            return $this->returnError('You are not authorized to Update users', 403);

        }
        $clocks = ClockInOut::orderBy('clock_in', 'desc')->paginate(7);
        if ($clocks->isEmpty()) {

            return $this->returnError('No clocks Found');
        }
        return $this->returnData("clocks", ClockResource::collection($clocks), "clocks Data");
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
            ->whereNull('clock_out')
            ->orderBy('clock_in', 'desc')
            ->first();

        $LastClockedOut = ClockInOut::where('user_id', $user_id)
            ->where('location_id', $location_id)
            ->whereNotNull('clock_out')
            ->whereDate('clock_out', Carbon::today())
            ->orderBy('clock_out', 'desc')
            ->first();

        if ($LastClockedOut) {
            $clock_in = Carbon::parse($LastClockedOut->clock_in);
            $clock_out = Carbon::parse($LastClockedOut->clock_out);
            $userWorkingHoursDay = $authUser->user_detail->working_hours_day;

            // Calculate the duration between clock_in and clock_out as a CarbonInterval
            $durationInterval = $clock_out->diffAsCarbonInterval($clock_in);

            // Format the duration as H:i:s
            $durationFormatted = $durationInterval->format('%H:%I:%S');

            // Convert the duration to total hours for comparison with working hours
            $durationInHours = $durationInterval->totalHours;
            // dd($durationInHours);
            // // $DurationClocked = $clock_out->diffInHours($clock_in);
            // $durationInterval = $clock_out->diffAsCarbonInterval($clock_in);
            // dd($durationInterval->toArray());
            // $durationFormatted = $durationInterval->format('%H:%I:%S');

            if ($durationInHours < $userWorkingHoursDay) {
                $LastClockedOut->update([
                    'clock_in' => $clock_in,
                    'clock_out' => null,
                    'duration' => $durationFormatted,
                ]);
                return $this->returnData("clock", $LastClockedOut, "Clock In Done");
            }
        }

        if ($existingClockIn) {
            return $this->returnError('You have already clocked in.');
        }

        if ($distanceBetweenUserAndLocation < 50) {
            $clock = ClockInOut::create([
                'clock_in' => Carbon::now()->addRealHour(3),
                'clock_out' => null,
                'duration' => null, // Set initial duration to 0
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
        $clocks = ClockInOut::where('user_id', $authUser->id)->orderBy('clock_in', 'desc')->paginate(7);

        if ($clocks->isEmpty()) {
            return $this->returnError('No Clocks For this user found');
        }

        return $this->returnData("clocks", ClockResource::collection($clocks), "Clocks Data for {$authUser->name}");
    }

}
