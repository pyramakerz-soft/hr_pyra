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
        $working_hours = 8; //8
        $hourly_rate = ($salary / 30) / $working_hours;
        UserDetail::create([
            'salary' => $salary,
            'working_hours_day' => $working_hours,
            'hourly_rate' => $hourly_rate,
            'overtime_hours' => 1.5,
            'emp_type' => "Backend developer",
            'hiring_date' => "2024-7-8",
            'user_id' => 2,
        ]);
        UserDetail::create([
            'salary' => $salary,
            'working_hours_day' => $working_hours,
            'hourly_rate' => $hourly_rate,
            'overtime_hours' => 1.5,
            'emp_type' => "Front developer",
            'hiring_date' => "2024-8-8",
            'user_id' => 1,
        ]);
    }
}