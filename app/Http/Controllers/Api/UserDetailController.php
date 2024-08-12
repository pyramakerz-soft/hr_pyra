<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreUserDetailRequest;
use App\Http\Requests\Api\UpdateUserDetailRequest;
use App\Models\UserDetail;
use App\Traits\ResponseTrait;

class UserDetailController extends Controller
{
    use ResponseTrait;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user_details = UserDetail::all();
        if ($user_details->isEmpty()) {
            return $this->returnError('No User Details Found');
        }
        return $this->returnData('user_details', $user_details, 'User Details Data');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserDetailRequest $request)
    {
        $salary = $request->salary; //24000
        $working_hours_day = $request->working_hours_day; //8
        $hourly_rate = ($salary / 30) / $working_hours_day;
        $start_time = $request->start_time; //7.00
        $end_time = $start_time + $working_hours_day; //15.00
        $userDetail = UserDetail::create([
            'salary' => $salary,
            'working_hours_day' => $working_hours_day,
            'hourly_rate' => $hourly_rate,
            'overtime_hours' => $request->overtime_hours,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'emp_type' => $request->emp_type,
            'hiring_date' => $request->hiring_date,
            'user_id' => $request->user_id,
        ]);
        return $this->returnData('userDetail', $userDetail, 'User Details Stored');
    }

    /**
     * Display the specified resource.
     */
    public function show(UserDetail $userDetail)
    {
        return $this->returnData("UserDetail", $userDetail, "User Details Data");
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserDetailRequest $request, UserDetail $userDetail)
    {
        // dd($request->toArray());
        $salary = $request->salary; //24000
        $working_hours = $request->working_hours_day; //8
        $hourly_rate = ($salary / 30) / $working_hours;
        // dd($request->toArray());
        if ($userDetail) {
            $userDetail->update([
                'salary' => $salary,
                'working_hours_day' => $working_hours,
                'hourly_rate' => $hourly_rate,
                'overtime_hours' => $request->overtime_hours,
                'emp_type' => $request->emp_type,
                'hiring_date' => $request->hiring_date,
                'user_id' => $request->user_id,
            ]);
        }

        return $this->returnData('userDetail', $userDetail, 'User Details Updated');

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UserDetail $userDetail)
    {
        $userDetail->delete();
        return $this->returnData('userDetail', $userDetail, 'User Details deleted');

    }
}
