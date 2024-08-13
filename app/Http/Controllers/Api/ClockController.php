<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClockInOutRequest;
use App\Http\Requests\UpdateClockInOutRequest;
use App\Models\ClockInOut;
use App\Models\User;
use App\Traits\ResponseTrait;
use Illuminate\Support\Carbon;

class ClockController extends Controller
{
    use ResponseTrait;
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
    public function clockIn(StoreClockInOutRequest $request)
    {
        // dd($request->toArray());

        $user_id = $request->user_id;
        $location_id = $request->location_id;
        $latitude = $request->latitude;
        $longitude = $request->longitude;

        $user = User::findorFail($user_id);
        $user_locations = $user->user_locations()->wherePivot('location_id', $location_id)->first();
        // dd($latitude);
        // dd($user_locations['longitude']);
        if (!$user_locations) {
            return $this->returnError('Not found');
        }
        if ($user_locations['latitude'] == $latitude && $user_locations['longitude'] == $longitude) {
            $clock = ClockInOut::create([
                'clock_in' => Carbon::now()->addRealHour(3),
                'clock_out' => null,
                'duration' => null,
                'user_id' => $user_id,
                'location_id' => $location_id,
            ]);
            return $this->returnData("clock", $clock, "clock Stored Successfully");
        } else {
            return $this->returnError('User is not located at the correct location');

        }

    }

    /**
     * Display the specified resource.
     */
    // public function show(ClockInOut $clock)
    // {
    //     return $this->returnData("clock", $clock, "Clock Data");
    // }

    /**
     * Update the specified resource in storage.
     */
    public function clockOut(UpdateClockInOutRequest $request, ClockInOut $clock)
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
        $user_locations = $user->user_locations()->wherePivot('location_id', $location_id)->first();
        // dd($->toArray());
        if (!$user_locations) {
            return $this->returnError('User is not clocking out from the same location they clocked in.');
        }
        if ($user_locations['latitude'] == $latitude && $user_locations['longitude'] == $longitude) {

            $clock_in = $clock->clock_in;
            $clock_out = Carbon::now()->addRealHour(3);
            $duration = $clock_out->diffInHours($clock_in);

            $clock->update([
                'clock_out' => $clock_out,
                'duration' => $duration,
                'user_id' => $user_id,
                'location_id' => $location_id,
            ]);
            return $this->returnData("clock", $clock, "clock Updated Successfully");
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
