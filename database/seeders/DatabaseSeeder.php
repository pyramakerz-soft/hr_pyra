<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{

    /**
     * List of applications to add.
     */
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $permissions = [
            'role-list',
            'role-create',
            'role-edit',
            'role-delete',
            'user-list',
            'user-create',
            'user-edit',
            'user-delete',
        ];

        $this->call([
            DepartmentSeeder::class,
            WorkTypeSeeder::class,

            UserSeeder::class,
            UserWorkTypeSeeder::class,
            UserHolidaySeeder::class,
            UserVacationSeeder::class,
            LocationSeeder::class,
            UserDetailSeeder::class,

            PermissionSeeder::class,
            UserLocationSeeder::class,
        ]);

    }
}
