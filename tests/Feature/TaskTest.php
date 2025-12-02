<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Project;
use App\Models\Task;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'admin', 'display_name' => 'Admin', 'description' => 'Admin', 'permissions' => ['*']]);
    }

    protected function createAuthenticatedUser()
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();
        $role = Role::where('name', 'admin')->first();
        
        $user->companies()->attach($company->id, ['role_id' => $role->id, 'is_active' => true]);
        $user->update(['current_company_id' => $company->id]);
        
        return ['user' => $user, 'company' => $company];
    }

    /** @test */
    public function authenticated_user_can_create_task()
    {
        ['user' => $user, 'company' => $company] = $this->createAuthenticatedUser();
        $project = Project::factory()->create(['company_id' => $company->id, 'created_by' => $user->id]);

        $response = $this->actingAs($user)->post("/projects/{$project->id}/tasks", [
            'title' => 'Test Task',
            'description' => 'Test Description',
            'status' => 'a_faire',
            'priority' => 'moyenne',
            'assigned_to' => null,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tasks', [
            'title' => 'Test Task',
            'project_id' => $project->id,
        ]);
    }

    /** @test */
    public function user_can_view_tasks()
    {
        ['user' => $user, 'company' => $company] = $this->createAuthenticatedUser();
        $project = Project::factory()->create(['company_id' => $company->id, 'created_by' => $user->id]);
        $task = Task::factory()->create(['project_id' => $project->id, 'created_by' => $user->id]);

        $response = $this->actingAs($user)->get("/projects/{$project->id}/tasks");

        $response->assertStatus(200);
        $response->assertSee($task->title);
    }
}
