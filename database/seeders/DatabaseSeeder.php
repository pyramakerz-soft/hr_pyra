<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{

    /**
     * List of applications to add.
     */
    private $permissions = [
        'role-list',
        'role-create',
        'role-edit',
        'role-delete',
        'user-list',
        'user-create',
        'user-edit',
        'user-delete',
    ];
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        $this->call([
            DepartmentSeeder::class,
            UserSeeder::class,
            UserDetailSeeder::class,
            UserHolidaySeeder::class,
            UserVacationSeeder::class,
            LocationSeeder::class,
            UserLocationSeeder::class,
            WorkTypeSeeder::class,
        ]);

    }
}
