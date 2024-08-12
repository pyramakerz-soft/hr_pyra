<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClockInOutRequest;
use App\Http\Requests\UpdateClockInOutRequest;
use App\Models\ClockInOut;
use App\Models\Location;
use App\Traits\ResponseTrait;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

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

        $location = Location::whereHas('users', function ($q) {
            // dd($q->get())

        })->find($request->location_id);
        $location_users = $location->users[0]->pivot;
        // foreach($location->users)
        if (!$location) {
            return $this->returnError("Location not found");
        }
        $user = $location->users->firstWhere('id', Auth::user()->id);
        $pivotData = $user->pivot;
        // dd($user->toArray());

        $clock = ClockInOut::create([
            'clock_in' => Carbon::now()->addRealHour(3),
            'clock_out' => null,
            'duration' => null,
            'user_id' => $request->user_id,
            'location_id' => $request->location_id,
        ]);
        return $this->returnData("clock", $clock, "clock Stored Successfully");

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
        // dd($clock->clock_in);
        $clock_in = $clock->clock_in;
        $clock_out = Carbon::now()->addRealHour(3);
        $duration = $clock_out->diffInHours($clock_in);
        $clock->update([
            'clock_out' => $clock_out,
            'duration' => $duration,
            'user_id' => $request->user_id,
            'location_id' => $request->location_id,
        ]);
        return $this->returnData("clock", $clock, "clock Updated Successfully");

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
