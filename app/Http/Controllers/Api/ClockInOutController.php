<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClockInOutRequest;
use App\Http\Requests\UpdateClockInOutRequest;
use App\Models\ClockInOut;

class ClockInOutController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreClockInOutRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(ClockInOut $clockInOut)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ClockInOut $clockInOut)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateClockInOutRequest $request, ClockInOut $clockInOut)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ClockInOut $clockInOut)
    {
        //
    }
}
