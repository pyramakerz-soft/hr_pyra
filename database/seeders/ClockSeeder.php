<?php

namespace Database\Seeders;

use App\Models\ClockInOut;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ClockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user_id = 3;
        $clockIn = Carbon::now()->startOfDay(); // Start at the beginning of the current day

        for ($i = 1; $i <= 30; $i++) {
            $clockOut = $clockIn->copy()->addHours(rand(1, 8)); // Randomly add 1 to 8 hours

            ClockInOut::create([
                'clock_in' => $clockIn,
                'clock_out' => $clockOut,
                'duration' => $clockOut->diff($clockIn)->format('%H:%I:%S'), // Calculate the duration
                'user_id' => $user_id, // Assumes a user with ID 3 exists
                'location_id' => 1, // Assumes a location with ID 1 exists
                'location_type' => "home",
                'late_arrive' => "00:00:00",
                'early_leave' => "00:00:00",
            ]);

            $clockIn = $clockIn->copy()->addDay(); // Move to the next day
        }
    }
}
