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
        $clocks = ClockInOut::paginate(10);
        if ($clocks->isEmpty()) {
            return $this->returnError('No clocks Found');
        }
        // $data['clocks'] = ;
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
        $user_id = Auth::user()->id;
        $location_id = (int) $request->location_id;
        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $user = User::findorFail($user_id);
        $existingClockIn = ClockInOut::where('user_id', $user_id)
            ->where('location_id', $location_id)
            ->whereNull('clock_out')
            ->first();

        if ($existingClockIn) {
            return $this->returnError('You have already clocked in at this location and have not clocked out yet.');
        }
        $user_location = $user->user_locations()->wherePivot('location_id', $location_id)->first();
        if (!$user_location) {
            return $this->returnError('User is not located at the correct location');
        }
        $userLongitude = $user_location->longitude;
        $userLatitude = $user_location->latitude;

        $distanceBetweenUserAndLocation = $this->
            haversineDistance($userLatitude, $userLongitude, $latitude, $longitude);
        //Distance by meter
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

    public function clockOut(UpdateClockInOutRequest $request, ClockInOut $clock)
    {
        $this->validate($request, [
            'latitude' => 'required',
            'longitude' => 'required',
        ]);
        $latitude = $request->latitude;
        $longitude = $request->longitude;

        $user = User::findorFail($clock->user_id);
        $user_location = $user->user_locations()->wherePivot('location_id', $clock->location_id)->first();
        if (!$user_location) {
            return $this->returnError('User is not located at the correct location');
        }
        $userLongitude = $user_location->longitude;
        $userLatitude = $user_location->latitude;
        $distanceBetweenUserAndLocation = $this->
            haversineDistance($userLatitude, $userLongitude, $latitude, $longitude);
        if ($distanceBetweenUserAndLocation < 50) {

            $clock_in = $clock->clock_in;
            $clock_out = Carbon::now()->addRealHour(3);
            $duration = $clock_out->diffInHours($clock_in);

            $clock->update([
                'clock_out' => $clock_out,
                'duration' => $duration,

            ]);
            return $this->returnData("clock", $clock, "Clock Out Done");
        } else {
            return $this->returnError('User is not located at the correct location');

        }
    }
    public function showUserClocks()
    {
        $clocks = ClockInOut::where('user_id', Auth::user()->id)->get();
        dd($clocks->toArray());
        if ($clocks->isEmpty()) {
            return $this->returnError('No Clocks For this user found');
        }
        return $this->returnData("clocks", $clocks, "Clocks Data for {$user->name}");

    }

}
