<?php

namespace App\Services\Api\User;

use App\Models\UserDetail;
use App\Traits\ResponseTrait;
use Carbon\Carbon;

class UserDetailService
{
    use ResponseTrait;
    public function createUserDetail($user, $data)
    {
        $salary = $data['salary'];
        $working_hours_day = $data['working_hours_day'];
        $overtime_hours = $data['overtime_hours'];
        $hourly_rate = ($salary / 22) / $working_hours_day;
        $overtime_hourly_rate = (($salary / 30) / $working_hours_day) * $overtime_hours;
        $start_time = $data['start_time'];
        $end_time = $data['end_time'];

        if ($end_time <= $start_time) {
            return null;
        }

        return UserDetail::create([
            'salary' => $salary,
            'working_hours_day' => $working_hours_day,
            'hourly_rate' => $hourly_rate,
            'overtime_hourly_rate' => $overtime_hourly_rate,
            'overtime_hours' => $overtime_hours,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'emp_type' => $data['emp_type'],
            'hiring_date' => $data['hiring_date'],
            'user_id' => $user->id,
        ]);
    }

    public function updateUserDetail($userDetail, $data)
    {
        $salary = $data['salary'] ?? $userDetail->salary;
        $working_hours_day = $data['working_hours_day'] ?? $userDetail->working_hours_day;
        $hourly_rate = $working_hours_day === null || $working_hours_day == 0 ? 0 : ($salary / 30) / $working_hours_day;

        $start_time = isset($data['start_time']) ? Carbon::parse($data['start_time'])->format("H:i:s") : $userDetail->start_time;
        $end_time = isset($data['end_time']) ? Carbon::parse($data['end_time'])->format("H:i:s") : $userDetail->end_time;

        if ($end_time <= $start_time) {
            return null;
        }

        $userDetail->update([
            'salary' => $salary,
            'working_hours_day' => $working_hours_day,
            'hourly_rate' => $hourly_rate,
            'overtime_hours' => $data['overtime_hours'] ?? $userDetail->overtime_hours,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'emp_type' => $data['emp_type'] ?? $userDetail->emp_type,
            'hiring_date' => $data['hiring_date'] ?? $userDetail->hiring_date,
            'user_id' => $userDetail->user_id,
        ]);

        return $userDetail;
    }
}
