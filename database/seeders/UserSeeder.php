<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->count(100)->create();
        User::create([
            'name' => 'Manager',
            'email' => 'manager@test.com',
            'password' => bcrypt("123456"),
            'phone' => "01203376449",
            'contact_phone' => "01211018851",
            'gender' => 'M',
            'department_id' => null,
        ]);
        User::create([
            'name' => 'Mohamed',
            'email' => 'mohamed@gmail.com',
            'password' => bcrypt("123456"),
            'phone' => "01203376444",
            'contact_phone' => "01211018850",
            'gender' => 'M',
            'department_id' => null,
        ]);
    }
}
