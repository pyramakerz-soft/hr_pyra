<?php

namespace Modules\Clocks\Traits;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use Modules\Clocks\Http\Requests\Api\AddClockRequest;
use Modules\Clocks\Models\ClockInOut;
use Modules\Users\Models\User;
use Modules\Users\Models\UserDetail;

trait ClockInTrait
{

    use ClockCalculationsHelperTrait;


    protected function checkClockInWithoutClockOut($user_id, $clock_in)
    {
        // Check if the user has an unresolved clock-in (without clock-out) for today
        $query = ClockInOut::where('user_id', $user_id)
            ->whereNull('clock_out')
            ->whereBetween('clock_in', [Carbon::parse($clock_in)->startOfDay(), Carbon::parse($clock_in)->endOfDay()])
            ->exists();
        return $query;
    }

    protected function handleHomeClockIn($request, $user_id)
    {
        //1- Calculate Late_arrive
        $authUser = User::findOrFail($user_id);

        $clockIn = Carbon::parse($request->clock_in);

        $userStartTime = Carbon::parse($authUser->user_detail->start_time);
        $late_arrive = $this->calculateLateArrive($clockIn, $userStartTime);

        // Call createHomeClockIn method to create the clock-in record
        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $address = $this->getAddressFromCoordinates($latitude, $longitude);
        $formatted_address = isset($address['address']['road']) ? $address['address']['road'] : 'Address not available';
        $clock = ClockInOut::create([
            'clock_in' => $clockIn,
            'clock_out' => null,
            'duration' => null,
            'user_id' => $user_id,
            'location_type' => $request->location_type,
            'location_id' => null,
            'late_arrive' => $late_arrive,
            'early_leave' => null,
            'address_clock_in' => $formatted_address
        ]);

        return $this->returnData("clock", $clock, "Clock In Done");
    }


    protected function handleFloatClockIn($request, $user_id)
    {

        $authUser = User::findOrFail($user_id);

        $clockIn = Carbon::parse($request->clock_in);

        $userStartTime = Carbon::parse($authUser->user_detail->start_time);
        $late_arrive = $this->calculateLateArrive($clockIn, $userStartTime);


        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $address = $this->getAddressFromCoordinates($latitude, $longitude);

        $formatted_address = isset($address['address']['road']) ? $address['address']['road'] : 'Address not available';

        $clock = ClockInOut::create([
            'clock_in' => $clockIn,
            'clock_out' => null,
            'duration' => null,
            'user_id' => $user_id,
            'location_type' => $request->location_type,
            'location_id' => null,
            'late_arrive' => $late_arrive,
            'early_leave' => null,
            'address_clock_in' => $formatted_address
        ]);

        return $this->returnData("clock", $clock, "Clock In Done");
    }


    protected function handleSiteClockIn($request, $authUser)
    {


        // 1- Validate location of user and location of the site
        $userLocation = $this->validateLocations($request, $authUser);

        if ($userLocation instanceof \Illuminate\Http\JsonResponse) {
            return $userLocation; // Return the error response
        }
        //2- check the department_name for authenticated user
        $clockIn = Carbon::parse($request->clock_in);

        $userLocation = $authUser->user_locations()->first();
        if ($this->isLocationTime($authUser)) {
            //Calculate Late_arrive by location time
            $locationStartTime = Carbon::parse($userLocation->start_time);
            $late_arrive = $this->calculateLateArrive($clockIn, $locationStartTime);
        } else {
            $userStartTime = carbon::parse($authUser->user_detail->start_time);
            $late_arrive = $this->calculateLateArrive($clockIn, $userStartTime);
        }
        $userId = $authUser->id;
        $userFloat = UserDetail::where('user_id', $userId)->first();
        //3- create ClockIn Site Record
        return $this->createClockInSiteRecord($request, $authUser, $userLocation, $clockIn, $late_arrive);
    }



    //HR Clock
    protected function handleSiteClockInByHr(AddClockRequest $request, User $user)
    {

        //1- Validate that the location_id is assigned to the user
        $location_id = $request->location_id;

        $userLocation = $user->user_locations()->where('location_id', $location_id)->first();

        if (!$userLocation) {
            return $this->returnError('The specified location is not assigned to the user.');
        }

        //2- Calculate the late_arrive
        $clockIn = Carbon::parse($request->clock_in);
        if ($this->isLocationTime($user)) {
            //Calculate Late_arrive by location_time
            $locationStartTime = Carbon::parse($userLocation->start_time);
            $late_arrive = $this->calculateLateArrive($clockIn, $locationStartTime);
        } else {
            //Calculate Late_arrive by User time
            $userStartTime = carbon::parse($user->user_detail->start_time);
            $late_arrive = $this->calculateLateArrive($clockIn, $userStartTime);
        }
        //Test Vendor Location
        // Create the clock-in recording
        //3- create ClockIn Site Record

        return $this->createClockInSiteRecord($request, $user, $userLocation, $clockIn, $late_arrive);
    }

    protected function createClockInSiteRecord($request, $authUser, $userLocation, $clockIn, $late_arrive)
    {

        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $address = $this->getAddressFromCoordinates($latitude, $longitude);
        $formatted_address = isset($address['address']['road']) ? $address['address']['road'] : 'Address not available';

        $clock = ClockInOut::create([
            'clock_in' => $clockIn,
            'clock_out' => null,
            'duration' => null,
            'user_id' => $authUser->id,
            'location_id' => $request->location_id,
            'location_type' => $request->location_type,
            'late_arrive' => $late_arrive,
            'early_leave' => null,
            'address_clock_in' => $formatted_address
        ]);

        return $this->returnData("clock", $clock, "Clock In Done");
    }
}
