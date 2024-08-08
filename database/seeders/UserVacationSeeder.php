<?php

namespace Database\Seeders;

use App\Models\UserVacation;
use Illuminate\Database\Seeder;

class UserVacationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        UserVacation::create([
            'sick_left' => 5,
            'paid_left' => 15,
            'deduction_left' => 1,
            'user_id' => 1,
        ]);

        UserVacation::create([
            'sick_left' => 5,
            'paid_left' => 15,
            'deduction_left' => 1,
            'user_id' => 2,
        ]);

    }
}
