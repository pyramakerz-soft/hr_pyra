<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreUserHolidayRequest;
use App\Http\Requests\Api\UpdateUserHolidayRequest;
use App\Http\Resources\Api\UserHolidayResource;
use App\Models\UserHoliday;
use App\Traits\ResponseTrait;
use Illuminate\Support\Facades\Auth;

class UserHolidayController extends Controller
{
    use ResponseTrait;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $userHolidays = UserHoliday::with('department', 'user')->get();
        if ($userHolidays->isEmpty()) {
            return $this->returnError('No User Holidays Found');
        }
        $data['user_holidays'] = UserHolidayResource::collection($userHolidays);
        return $this->returnData('data', $data, 'User Holidays Data');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserHolidayRequest $request)
    {
        $userHoliday = userHoliday::create($request->validated());
        return $this->returnData('UserHoliday', $userHoliday, 'User Holiday Stored');
    }

    /**
     * Display the specified resource.
     */
    public function show(UserHoliday $userHoliday)
    {
        return $this->returnData('UserHoliday', $userHoliday, 'User Holiday Data');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserHolidayRequest $request, UserHoliday $userHoliday)
    {
        $userHoliday->update($request->validated());
        return $this->returnData('UserHoliday', $userHoliday, 'User Holiday Updated');

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UserHoliday $userHoliday)
    {
        $userHoliday->delete();
        return $this->returnData('UserHoliday', $userHoliday, 'User Holiday deleted');
    }
    // public function profile()
    // {
    //     $authUser = Auth::user();
    //     $userHolidays = UserHoliday::where('user_id', $authUser->id)->get();
    //     if (!$userHolidays) {
    //         return $this->returnError('No User Holidays Found for this User');

    //     }
    //     return $this->returnData("UserHolidays", ($userHolidays), "UserHolidays Data");
    // }
}