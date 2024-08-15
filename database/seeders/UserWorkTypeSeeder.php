<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\WorkType;
use Illuminate\Database\Seeder;

class UserWorkTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user_hr = User::findorFail(1);
        $workType = WorkType::findOrFail(1);
        $user_hr->work_types()->attach($workType->id);
        $user_admin = User::findorFail(2);
        $user_admin->work_types()->attach($workType->id);
    }
}
