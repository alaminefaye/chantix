<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'created_by' => User::factory(),
            'name' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'address' => fake()->address(),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'start_date' => fake()->dateTimeBetween('-1 month', 'now'),
            'end_date' => fake()->dateTimeBetween('now', '+6 months'),
            'budget' => fake()->numberBetween(10000, 1000000),
            'status' => fake()->randomElement(['non_demarre', 'en_cours', 'termine', 'bloque']),
            'progress' => fake()->numberBetween(0, 100),
            'client_name' => fake()->name(),
            'client_contact' => fake()->phoneNumber(),
        ];
    }
}
