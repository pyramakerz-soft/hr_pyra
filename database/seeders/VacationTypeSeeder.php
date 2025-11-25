<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Users\Models\VacationType;
use Illuminate\Support\Facades\DB;

class VacationTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Define the vacation types based on the provided policies
        $vacationTypes = [
            [
                'name' => 'Marriage Leave',
                'default_days' => 10,
            ],
            [
                'name' => 'Hajj / Umrah Leave',
                'default_days' => 30, // Eligibility logic (5 years) should be handled in the application
            ],
            [
                'name' => 'Sick Leave',
                'default_days' => 0, // Calculation method to be discussed later
            ],
            [
                'name' => 'Casual Leave',
                'default_days' => 14, // Standard annual entitlement
            ],
            [
                'name' => 'Emergency Leave',
                'default_days' => 7, // Standard annual entitlement
            ],
            [
                'name' => 'Maternity Leave',
                'default_days' => 0, // Duration not specified yet
            ],
            [
                'name' => 'Unpaid Leave',
                'default_days' => 0,
            ],
            [
                'name' => 'Official Holiday',
                'default_days' => 0, // These are typically managed system-wide, not as a balance
            ],
            [
                'name' => 'Annual Leave',
                'default_days' => 0,
            ],
        ];

        // Insert or update the records in the database
        foreach ($vacationTypes as $type) {
            VacationType::updateOrCreate(['name' => $type['name']], $type);
        }
    }
}
