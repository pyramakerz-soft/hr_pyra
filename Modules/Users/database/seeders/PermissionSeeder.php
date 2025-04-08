<?php

// namespace Modules\Users\Database\Seeders;

// use Illuminate\Database\Seeder;
// use Modules\Users\Models\User;
// use Spatie\Permission\Models\Permission;
// use Spatie\Permission\Models\Role;

// class PermissionSeeder extends Seeder
// {
//     /**
//      * Run the database seeds.
//      */
//     public function run(): void
//     {
//         $permissions = [
//             //User
//             'user-list',
//             'user-create',
//             'user-edit',
//             'user-delete',
//             //Role
//             'role-list',
//             'role-create',
//             'role-edit',
//             'role-delete',
//             //Department
//             'department-list',
//             'department-create',
//             'department-edit',
//             'department-delete',
//             //Location
//             'location-list',
//             'location-create',
//             'location-edit',
//             'location-delete',
//             //Permission
//             'permission-list',
//             'permission-create',
//             'permission-edit',
//             'permission-delete',
//             //UserDetail
//             'user-detail-list',
//             //Clock
//             'clock-list',
//             'clock-create',
//             'clock-edit',
//             //WorkType
//             'work-type-list',
//             'work-type-create',
//             'work-type-edit',
//             'work-type-delete',

//         ];

//         foreach ($permissions as $permission) {
//             Permission::firstOrCreate(['name' => $permission]);
//         }
//         $allPermissions = Permission::all();
//         $roleHr = Role::firstOrCreate(['name' => 'Hr']);
//         $roleAdmin = Role::firstOrCreate(['name' => 'Manager']);
//         $roleEmp = Role::firstOrCreate(['name' => 'Employee']);

//         $HrPermsissions = $allPermissions;
//         $roleHr->givePermissionTo($HrPermsissions);
//         $user_hr = User::findOrFail(1);
//         $user_hr->assignRole($roleHr);

//         $roleAdmin->givePermissionTo($allPermissions);
//         $user_admin = User::findOrFail(2);
//         $user_admin->assignRole($roleAdmin);

//         $user_employee = User::findOrFail(3);
//         $user_employee->assignRole($roleEmp);

//     }
// }
