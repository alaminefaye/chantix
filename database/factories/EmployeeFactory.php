<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'position' => fake()->randomElement(['maçon', 'électricien', 'plombier', 'peintre', 'charpentier']),
            'employee_number' => fake()->unique()->numerify('EMP-####'),
            'hire_date' => fake()->dateTimeBetween('-2 years', 'now'),
            'hourly_rate' => fake()->randomFloat(2, 15, 50),
            'address' => fake()->address(),
            'city' => fake()->city(),
            'country' => fake()->country(),
            'birth_date' => fake()->dateTimeBetween('-50 years', '-18 years'),
            'id_number' => fake()->numerify('##########'),
            'is_active' => true,
        ];
    }
}
