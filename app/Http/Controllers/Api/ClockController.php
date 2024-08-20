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
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // TODO make this for spacific user by where

        $clocks = ClockInOut::orderBy('clock_in', 'desc')->paginate(7);
        if ($clocks->isEmpty()) {
            // TODO Edit the message like figma

            return $this->returnError('No clocks Found');
        }
        return $this->returnData("clocks", ClockResource::collection($clocks), "clocks Data");
    }

    /**
     * Store a newly created resource in storage.
     */

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


        //TODO the user can have many locations

        // Access the first location_id associated with the authenticated user
        $userLocation = $authUser->user_locations()->first();

        if (!$userLocation) {
            return $this->returnError('User does not have any associated locations.');
        }

        $location_id = $userLocation->pivot['location_id'];
        $userLongitude = $userLocation->longitude;
        $userLatitude = $userLocation->latitude;

        $existingClockIn = ClockInOut::where('user_id', $user_id)
            ->where('location_id', $location_id)
            ->whereNull('clock_out')
            ->first();

        if ($existingClockIn) {
            return $this->returnError('You have already clocked in at this location and have not clocked out yet.');
        }

        $distanceBetweenUserAndLocation = $this->haversineDistance($userLatitude, $userLongitude, $latitude, $longitude);

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
            return $this->returnError('User is not located at the correct location');
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

        // Get the last clock-in record where clock_out is null, ordered by clock_in descending
        $ClockauthUser = ClockInOut::where('user_id', $authUser->id)
            ->whereNull('clock_out')
            ->orderBy('clock_in', 'desc') // Order by clock_in in descending order
            ->first();

        if (!$ClockauthUser) {
            return $this->returnError('You are not clocked in.');
        }

        // Get the user's location associated with this clock-in
        $user_location = $authUser->user_locations()->wherePivot('location_id', $ClockauthUser->location_id)->first();

        if (!$user_location) {
            return $this->returnError('User is not located at the correct location.');
        }

        $userLongitude = $user_location->longitude;
        $userLatitude = $user_location->latitude;

        $distanceBetweenUserAndLocation = $this->haversineDistance($userLatitude, $userLongitude, $latitude, $longitude);

        if ($distanceBetweenUserAndLocation < 50) {
            $clock_in = $ClockauthUser->clock_in;
            $clock_out = Carbon::now()->addRealHour(3);

            $duration = $clock_out->diffInHours($clock_in); // Consider using diff() for more precision
            $ClockauthUser->update([
                'clock_out' => $clock_out,
                'duration' => $duration,
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
