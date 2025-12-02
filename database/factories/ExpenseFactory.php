<?php

namespace Database\Factories;

use App\Models\Expense;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'user_id' => User::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'amount' => fake()->randomFloat(2, 10, 10000),
            'type' => fake()->randomElement(['materiaux', 'transport', 'main_oeuvre', 'location', 'autres']),
            'expense_date' => fake()->dateTimeBetween('-1 month', 'now'),
            'is_paid' => fake()->boolean(),
        ];
    }
}
