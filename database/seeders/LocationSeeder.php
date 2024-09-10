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
            'name' => "Pyramakerz",
            'address' => "Yehia Mosque, AboQeir Street Zizinya",
            'latitude' => "31.2475701",
            'longitude' => "29.9632017",
            'start_time' => '07:30',
            'end_time' => "15:30",
        ]);
        Location::create([
            'name' => "Maali El Salam School",
            'address' => "Agami,Hanouvil ElSalam St",
            'latitude' => "31.2475801",
            'longitude' => "29.9632117",
            'start_time' => '08:00',
            'end_time' => "14:00",
        ]);
        // Location::create([
        //     'name' => "Pyramakerz",
        //     'address' => "Yehia Mosque, AboQeir Street Zizinya",
        //     'latitude' => "31.2475701",
        //     'longitude' => "29.9632017",
        //     'start_time' => '07:30',
        //     'end_time' => "15:30",
        // ]);

        // Location::factory()->count(19)->create();
    }
}
