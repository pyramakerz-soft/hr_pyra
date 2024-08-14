<?php

namespace Database\Seeders;

use App\Models\WorkType;
use Illuminate\Database\Seeder;

class WorkTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        WorkType::create(['name' => 'site']);
        WorkType::create(['name' => 'home']);
    }
}