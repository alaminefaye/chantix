<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'project_id' => Project::factory(),
            'type' => fake()->randomElement(['comment', 'mention', 'task_assigned', 'progress_update', 'expense_added']),
            'title' => fake()->sentence(3),
            'message' => fake()->paragraph(),
            'link' => fake()->url(),
            'is_read' => false,
            'data' => [],
        ];
    }
}
