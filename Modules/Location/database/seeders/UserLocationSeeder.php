<?php

namespace Modules\Location\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Location\Models\Location;
use Modules\Users\Models\User;

class UserLocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $user_hr = User::findorFail(1);
        $location = Location::findOrFail(1);
        $user_hr->user_locations()->attach($location->id);
        $user_manager = User::findorFail(2);
        $user_manager->user_locations()->attach($location->id);
        $user_employee = User::findorFail(3);
        $user_employee->user_locations()->attach($location->id);

    }
}
