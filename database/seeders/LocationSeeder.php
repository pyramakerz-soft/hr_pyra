<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Location::create([
            'name' => "Yehia Mosque",
            'address' => "AboQeir Street Zizinya",
            'latitude' => "100.0000000",
            'longitude' => "200.0000000",

        ]);
        Location::factory()->count(19)->create();
    }
}
