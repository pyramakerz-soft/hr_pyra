<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB as FacadesDB;

class TimezoneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        FacadesDB::table('timezones')->insert([
            ['name' => 'Egypt', 'value' => 3],
            ['name' => 'Saudi Arabia', 'value' => 3],
        ]);
    }
}
