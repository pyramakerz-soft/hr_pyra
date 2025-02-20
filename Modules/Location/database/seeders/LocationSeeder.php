<?php

namespace Modules\Location\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Location\Models\Location;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Location::create([
            'name' => "Alex (Zizinia)",
            'address' => "603 أبو قير، العاقصة وباكوس، قسم أول الرمل، محافظة الإسكندرية 5450218، مصر",
            'latitude' => "31.2403970",
            'longitude' => "29.9660127",
            'range' => 350,
            'start_time' => '07:30',
            'end_time' => "15:30",
        ]);

        Location::create([
            'name' => "Maali Elsalam School",
            'address' => "العجمي- الهانوفيل ش السلام",
            'latitude' => "31.2475701",
            'longitude' => "29.9632017",
            'range' => 200,
            'start_time' => '07:30',
            'end_time' => "15:30",
        ]);

        // Location::factory()->count(19)->create();
    }
}
