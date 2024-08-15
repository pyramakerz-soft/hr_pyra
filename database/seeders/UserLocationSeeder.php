<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserLocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // $users = User::inRandomOrder()->limit(5)->get();
        // $locations = Location::inRandomOrder()->limit(10)->get();

        // foreach ($locations as $location) {
        //     $location->users()->attach($users[rand(0, count($users) - 1)]);
        // }
        $user_hr = User::findorFail(1);
        $location = Location::findOrFail(1);
        $user_hr->user_locations()->attach($location->id);
        $user_admin = User::findorFail(2);
        $user_admin->user_locations()->attach($location->id);
    }
}