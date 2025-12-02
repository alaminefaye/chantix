<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'created_by' => User::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'status' => fake()->randomElement(['a_faire', 'en_cours', 'termine', 'bloque']),
            'priority' => fake()->randomElement(['basse', 'moyenne', 'haute', 'urgente']),
            'category' => fake()->randomElement(['maçonnerie', 'fondations', 'électricité', 'peinture', 'plomberie']),
            'start_date' => fake()->dateTimeBetween('-1 month', 'now'),
            'deadline' => fake()->dateTimeBetween('now', '+3 months'),
            'progress' => fake()->numberBetween(0, 100),
        ];
    }
}
