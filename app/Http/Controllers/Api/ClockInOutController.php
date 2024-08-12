<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClockInOutRequest;
use App\Http\Requests\UpdateClockInOutRequest;
use App\Models\ClockInOut;
use App\Traits\ResponseTrait;

class ClockInOutController extends Controller
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
    public function store(StoreClockInOutRequest $request)
    {

    }

    /**
     * Display the specified resource.
     */
    public function show(ClockInOut $clock)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateClockInOutRequest $request, ClockInOut $clock)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ClockInOut $clock)
    {
        //
    }
}
