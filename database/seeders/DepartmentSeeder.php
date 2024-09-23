<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Department::create(['name' => 'Software', 'is_location_time' => false]);
        Department::create(['name' => 'Academic_school', 'is_location_time' => true]);
        Department::create(['name' => 'Factory', 'is_location_time' => true]);
        Department::factory()->count(7)->create();
    }
}
