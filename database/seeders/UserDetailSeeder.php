<?php

namespace Database\Seeders;

use App\Models\UserDetail;
use Illuminate\Database\Seeder;

class UserDetailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $salary = 24000; //24000
        $working_hours = 8.00; //8
        $hourly_rate = ($salary / 30) / $working_hours;
        $start_time = "07:00";
        $end_time = "15:00";

        UserDetail::create([
            'salary' => $salary,
            'working_hours_day' => $working_hours,
            'hourly_rate' => $hourly_rate,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'overtime_hours' => 1.5,
            'emp_type' => "Frontend developer",
            'hiring_date' => "2024-7-8",
            'user_id' => 1,
        ]);
        UserDetail::create([
            'salary' => $salary,
            'working_hours_day' => $working_hours,
            'hourly_rate' => $hourly_rate,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'overtime_hours' => 1.5,
            'emp_type' => "Backend developer",
            'hiring_date' => "2024-8-8",
            'user_id' => 2,
        ]);
        UserDetail::create([
            'salary' => $salary,
            'working_hours_day' => $working_hours,
            'hourly_rate' => $hourly_rate,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'overtime_hours' => 1.5,
            'emp_type' => "Backend developer",
            'hiring_date' => "2024-9-8",
            'user_id' => 3,
        ]);

    }
}
