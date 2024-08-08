<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\User;
use Database\Factories\UserLocationFactory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserLocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::inRandomOrder()->limit(5)->get();
        $locations = Location::inRandomOrder()->limit(10)->get();

        foreach ($locations as $location) {
            $location->users()->attach($users[rand(0, count($users) -1)]);
        }



    }
}
