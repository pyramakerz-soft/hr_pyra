<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserHolidayRequest;
use App\Http\Requests\UpdateUserHolidayRequest;
use App\Models\UserHoliday;
use App\Traits\ResponseTrait;

class UserHolidayController extends Controller
{
    use ResponseTrait;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $userHolidays = UserHoliday::all();
        if ($userHolidays->isEmpty()) {
            return $this->returnError('No User Holidays Found');
        }
        return $this->returnData('user_holidays', $userHolidays, 'User Holidays Data');
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
        $userHoliday->update([
            'name' => $request->name,
            'date_of_holiday' => $request->date_of_holiday,
            'user_id' => $request->user_id,

        ]);
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
}
