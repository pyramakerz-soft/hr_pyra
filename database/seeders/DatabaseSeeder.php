<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Model\Role;

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
            UserSeeder::class,
            UserDetailSeeder::class,
            UserHolidaySeeder::class,
            UserVacationSeeder::class,
            LocationSeeder::class,
            UserLocationSeeder::class,
            PermissionSeeder::class

        ]);

    }
}
