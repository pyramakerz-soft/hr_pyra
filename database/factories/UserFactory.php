<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */

    public function definition(): array
    {
        $department = Department::find(1);

        // Generate a unique code
        do {
            $departmentPrefix = substr(Str::slug($department->name), 0, 4); // Get the first 4 letters of the department name
            $randomDigits = mt_rand(1000, 9999);
            $code = strtoupper($departmentPrefix) . '-' . $randomDigits;
        } while (User::where('code', $code)->exists());

        return [
            'name' => 'Rana', // Fixed name
            'email' => $this->faker->unique()->safeEmail(), // Unique email
            'email_verified_at' => now(),
            'password' => Hash::make('123456'), // Same password as the seeder
            'phone' => $this->faker->unique()->phoneNumber(), // Unique phone number
            'contact_phone' => $this->faker->unique()->numerify('012########'), // Generates a unique 11-digit phone number
            'national_id' => $this->faker->unique()->numerify('302###########'), // Generates a unique 14-digit national ID
            'gender' => $this->faker->randomElement(['F', 'M']), // Randomly selects 'F' or 'M'
            'department_id' => 2, // Fixed department ID
            'code' => $code,
            'remember_token' => Str::random(10),

        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
