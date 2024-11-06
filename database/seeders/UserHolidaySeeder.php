<?php

namespace Database\Seeders;

use App\Models\UserHoliday;
use Illuminate\Database\Seeder;

class UserHolidaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        UserHoliday::create([
            'name' => "23th revolution",
            'date_of_holiday' => "1952-07-23",
            'department_id' => 2,
            'user_id' => 2,

        ]);
        UserHoliday::create([
            'name' => "25th revolution",
            'date_of_holiday' => "2011-01-25",
            'department_id' => 1,
            'user_id' => 2,
        ]);
    }
}
