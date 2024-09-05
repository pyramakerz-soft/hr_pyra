<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\User;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{

    /**
     * List of applications to add.
     */

    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $faker = Faker::create();
        // Get the department name based on the department_id
        $department_1 = Department::find(1);

        // Generate a unique code
        do {
            $departmentPrefix = substr(Str::slug($department_1->name), 0, 4); // Get the first 4 letters of the department name
            $randomDigits = mt_rand(1000, 9999);
            $code = strtoupper($departmentPrefix) . '-' . $randomDigits;
        } while (User::where('code', $code)->exists());

        $user_hr = User::create([
            'name' => 'Mohamed Ali',
            'email' => 'hr@test.com',
            'password' => bcrypt("123456"),
            'phone' => "01203376449",

            'contact_phone' => "01211018851",
            'national_id' => "30201010214378",
            'code' => $code,
            'image' => $faker->imageUrl(),
            'gender' => 'M',
            'department_id' => 1,
        ]);
        // Get the department name based on the department_id
        $department_2 = Department::find(2);

        // Generate a unique code
        do {
            $departmentPrefix = substr(Str::slug($department_2->name), 0, 4); // Get the first 4 letters of the department name
            $randomDigits = mt_rand(1000, 9999);
            $code = strtoupper($departmentPrefix) . '-' . $randomDigits;
        } while (User::where('code', $code)->exists());

        $user_manager = User::create([
            'name' => 'Eng.Ashraf',
            'email' => 'manager@test.com',
            'password' => bcrypt("123456"),
            'phone' => "01203376447",
            'contact_phone' => "01211018850",
            'national_id' => "30201010214377",
            'code' => $code,

            'gender' => 'M',
            'department_id' => 2,
        ]);
        // Get the department name based on the department_id
        $department_3 = Department::find(3);

        // Generate a unique code
        do {
            $departmentPrefix = substr(Str::slug($department_3->name), 0, 5); // Get the first 4 letters of the department name
            $randomDigits = mt_rand(1000, 9999);
            $code = strtoupper($departmentPrefix) . '-' . $randomDigits;
        } while (User::where('code', $code)->exists());
        $user_emp = User::create([
            'name' => 'belal',
            'email' => 'emp@test.com',
            'password' => bcrypt("123456"),
            'phone' => "01203376440",
            'contact_phone' => "01211018856",
            'national_id' => "30201010214376",
            'code' => $code,

            'gender' => 'F',
            'department_id' => 2,
        ]);
        User::factory()->count(30)->create();

        $department = Department::findOrFail(1);
        $department->update(['manager_id' => 1]);

        $department = Department::findOrFail(2);
        $department->update(['manager_id' => 2]);

    }
}
