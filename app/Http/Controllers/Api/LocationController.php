<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLocationRequest;
use App\Http\Requests\UpdateLocationRequest;
use App\Models\Location;
use App\Traits\ResponseTrait;

class LocationController extends Controller
{
    use ResponseTrait;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        if (request()->has('search')) {
            $locations = Location::where('name', 'like', '%' . request()->get('search', '') . '%')->get();
        } else {
            $locations = Location::paginate(5);
        }
        if ($locations->isEmpty()) {
            return $this->returnError('No locations Found');
        }
        return $this->returnData('locations', $locations, 'Locations Data');
    }
    public function indexNames()
    {
        $locationNames = Location::pluck('name');
        return $this->returnData('location names', $locationNames, '');
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreLocationRequest $request)
    {
        $location = Location::create($request->validated());
        return $this->returnData('location', $location, 'Location Stored Successfully');

    }

    /**
     * Display the specified resource.
     */
    public function show(Location $location)
    {
        return $this->returnData('location', $location, 'Location Data');

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateLocationRequest $request, Location $location)
    {
        $location->update($request->validated());
        return $this->returnData('location', $location, 'Location updated Successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Location $location)
    {
        $location->delete();
        return $this->returnData('location', $location, 'Location deleted Successfully');
    }
}
