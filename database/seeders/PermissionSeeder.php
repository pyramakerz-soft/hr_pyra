<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
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
            'assign-location',
            'user-details-list',
            'user-details-create',
            'user-details-edit',
            'user-details-delete',
            'user-vacations-list',
            'user-vacations-create',
            'user-vacations-edit',
            'user-vacations-delete',
            'department-list',
            'department-create',
            'department-edit',
            'department-delete',
            'user-holidays-list',
            'user-holidays-create',
            'user-holidays-edit',
            'user-holidays-delete',
            'location-list',
            'location-create',
            'location-edit',
            'location-delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $roleHr = Role::firstOrCreate(['name' => 'Hr']);
        $roleAdmin = Role::firstOrCreate(['name' => 'Manager']);
        $roleEmp = Role::firstOrCreate(['name' => 'Employee']);
        $HrPermsissions = [
            'role-list',
            'role-create',
            'role-edit',
            'role-delete',
            'user-create',
            'user-edit',
            'user-delete',
            'user-list',
            'assign-location',
        ];
        $roleHr->givePermissionTo($HrPermsissions);
        $user_hr = User::findOrFail(1);
        $user_hr->assignRole($roleHr);

        $roleAdmin->givePermissionTo(Permission::all());
        $user_admin = User::findOrFail(2);
        $user_admin->assignRole($roleAdmin);

        $user_employee = User::findOrFail(3);
        $user_employee->assignRole($roleEmp);

    }
}
