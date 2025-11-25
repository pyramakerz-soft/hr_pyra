<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use Illuminate\Database\Seeder;
use Modules\Clocks\Database\Seeders\DeductionRuleTemplateSeeder;
// use Modules\Location\Database\Seeders\LocationSeeder;
// use Modules\Location\Database\Seeders\UserLocationSeeder;
// use Modules\Users\Database\Seeders\DepartmentSeeder;
// use Modules\Users\Database\Seeders\PermissionSeeder;
// use Modules\Users\Database\Seeders\UserDetailSeeder;
// use Modules\Users\Database\Seeders\UserHolidaySeeder;
// use Modules\Users\Database\Seeders\UserSeeder;
// use Modules\Users\Database\Seeders\UserVacationSeeder;
// use Modules\Users\Database\Seeders\UserWorkTypeSeeder;

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
            // DepartmentSeeder::class,
            // UserWorkTypeSeeder::class,

            // UserSeeder::class,
            // UserWorkTypeSeeder::class,
            // UserHolidaySeeder::class,
            // UserVacationSeeder::class,
            // LocationSeeder::class,
            // UserDetailSeeder::class,

            // PermissionSeeder::class,
            // UserLocationSeeder::class,
            // ClockSeeder::class,
            DeductionRuleTemplateSeeder::class,
            RolesSeeder::class,
            TimezoneSeeder::class,
            VacationTypeSeeder::class,
        ]);

    }
}
