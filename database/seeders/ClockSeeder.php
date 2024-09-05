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
        $userId = 3; // Replace with the desired user ID

        // Set the start and end dates for the desired month range
        $startDate = Carbon::create(2024, 9, 1);
        $endDate = Carbon::create(2024, 9, 31);

        // Generate random clock-in times within the specified range
        while ($startDate <= $endDate) {
            $clockIn = $startDate->copy()->addHours(rand(8, 17)); // Adjust hours as needed
            $clockOut = $clockIn->copy()->addHours(rand(1, 8));

            ClockInOut::create([
                'clock_in' => $clockIn,
                'clock_out' => $clockOut,
                'duration' => $clockOut->diff($clockIn)->format('%H:%I:%S'),
                'user_id' => $userId,
                'location_id' => 1, // Replace with the actual location ID
            ]);

            $startDate = $startDate->addDay();
        }
    }
}
