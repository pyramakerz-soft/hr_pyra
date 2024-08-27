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
            'latitude' => "31.2403946",
            'longitude' => "29.9653698",

        ]);
        Location::factory()->count(19)->create();
    }
}