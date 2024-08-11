<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{

    /**
     * List of applications to add.
     */
    public $permissions = [
        'role-list',
        'role-create',
        'role-edit',
        'role-delete',
        'user-list',
        'user-create',
        'user-edit',
        'user-delete',
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
    ];
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // User::factory()->count(10)->create();

        foreach ($this->permissions as $permission) {
            Permission::create(['name' => $permission]);
        }
        $user_hr = User::create([
            'name' => 'Hr',
            'email' => 'hr@test.com',
            'password' => bcrypt("123456"),
            'phone' => "01203376449",
            'contact_phone' => "01211018851",
            'gender' => 'M',
            'department_id' => 1,
        ]);
        $user_manager = User::create([
            'name' => 'manager',
            'email' => 'manager@test.com',
            'password' => bcrypt("123456"),
            'phone' => "01203376447",
            'contact_phone' => "01211018850",
            'gender' => 'M',
            'department_id' => 2,
        ]);

        $role_hr = Role::create(['name' => 'Hr']);
        $permissions = Permission::pluck('id', 'id')->all();
        $role_hr->syncPermissions($permissions);
        $user_hr->assignRole([$role_hr->id]);

        // User::create([
        //     'name' => 'Mohamed',
        //     'email' => 'mohamed@gmail.com',
        //     'password' => bcrypt("123456"),
        //     'phone' => "01203376444",
        //     'contact_phone' => "01211018850",
        //     'gender' => 'M',
        //     'department_id' => 2,
        // ]);
    }
}
