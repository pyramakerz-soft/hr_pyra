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
<<<<<<< HEAD
            WorkTypeSeeder::class,
=======
            PermissionSeeder::class

>>>>>>> 1447adec13c4eff540f9e6fe5db1abc7942f00b4
        ]);

    }
}
