<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClockInOutRequest;
use App\Http\Requests\UpdateClockInOutRequest;
use App\Models\ClockInOut;
use App\Models\User;
use App\Traits\HelperTrait;
use App\Traits\ResponseTrait;
use Illuminate\Support\Carbon;

class ClockController extends Controller
{
    use ResponseTrait;
    use HelperTrait;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $clocks = ClockInOut::all();
        if ($clocks->isEmpty()) {
            return $this->returnError('No clocks Found');
        }
        $data['clocks'] = $clocks;
        return $this->returnData("data", $data, "clocks Data");
    }

    /**
     * Store a newly created resource in storage.
     */

    // private function calculateDistance($latitude_location, $longitude_location, $latitude_user, $longitude_user)
    // {
    //     $earthRadiusInKilometers = 6371; // Earth's radius in kilometers

    //     $latitudeDifference = deg2rad($latitude_user - $latitude_location);
    //     $longitudeDifference = deg2rad($longitude_user - $longitude_location);

    //     $haversineFormula = sin($latitudeDifference / 2) * sin($latitudeDifference / 2) +
    //     cos(deg2rad($latitude_location)) * cos(deg2rad($latitude_user)) *
    //     sin($longitudeDifference / 2) * sin($longitudeDifference / 2);

    //     $angularDistance = 2 * atan2(sqrt($haversineFormula), sqrt(1 - $haversineFormula));

    //     return $earthRadiusInKilometers * $angularDistance; // Distance in kilometers
    // }

    public function clockIn(StoreClockInOutRequest $request)
    {
        $this->validate($request, [
            'latitude' => 'required',
            'longitude' => 'required',
        ]);
        $user_id = $request->user_id;
        $location_id = $request->location_id;
        $latitude = $request->latitude;
        $longitude = $request->longitude;

        $user = User::findorFail($user_id);
        $user_location = $user->user_locations()->wherePivot('location_id', $location_id)->first();
        if (!$user_location) {
            return $this->returnError('Not found');
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
        // dd($clock->user_id);

        // dd($clock->location_id);
        $this->validate($request, [
            'latitude' => 'required',
            'longitude' => 'required',
        ]);
        $location_id = $clock->location_id;
        $user_id = $clock->user_id;
        $latitude = $request->latitude;
        $longitude = $request->longitude;

        $user = User::findorFail($clock->user_id);
        $user_location = $user->user_locations()->wherePivot('location_id', $clock->location_id)->first();
        if (!$user_location) {
            return $this->returnError('Not Found');
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
                'user_id' => $user_id,
                'location_id' => $location_id,
            ]);
            return $this->returnData("clock", $clock, "Clock Out Done");
        } else {
            return $this->returnError('User is not located at the correct location');

        }
    }
    public function checkCurrentUserLOcation()
    {

    }
    // /**
    //  * Remove the specified resource from storage.
    //  */
    // public function destroy(ClockInOut $clock)
    // {
    //     $clock->delete();
    //     return $this->returnData("clock", $clock, "clock deleted Successfully");
    // }
}
