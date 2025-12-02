<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Project;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProjectTest extends TestCase
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
    public function authenticated_user_can_create_project()
    {
        ['user' => $user, 'company' => $company] = $this->createAuthenticatedUser();

        $response = $this->actingAs($user)->post('/projects', [
            'name' => 'Test Project',
            'description' => 'Test Description',
            'status' => 'non_demarre',
            'budget' => 100000,
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addMonths(6)->format('Y-m-d'),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('projects', [
            'name' => 'Test Project',
            'company_id' => $company->id,
        ]);
    }

    /** @test */
    public function user_can_view_projects()
    {
        ['user' => $user, 'company' => $company] = $this->createAuthenticatedUser();
        $project = Project::factory()->create(['company_id' => $company->id, 'created_by' => $user->id]);

        $response = $this->actingAs($user)->get('/projects');

        $response->assertStatus(200);
        $response->assertSee($project->name);
    }

    /** @test */
    public function user_can_view_single_project()
    {
        ['user' => $user, 'company' => $company] = $this->createAuthenticatedUser();
        $project = Project::factory()->create(['company_id' => $company->id, 'created_by' => $user->id]);

        $response = $this->actingAs($user)->get("/projects/{$project->id}");

        $response->assertStatus(200);
        $response->assertSee($project->name);
    }

    /** @test */
    public function user_can_update_project()
    {
        ['user' => $user, 'company' => $company] = $this->createAuthenticatedUser();
        $project = Project::factory()->create(['company_id' => $company->id, 'created_by' => $user->id]);

        $response = $this->actingAs($user)->put("/projects/{$project->id}", [
            'name' => 'Updated Project',
            'description' => 'Updated Description',
            'status' => 'en_cours',
            'budget' => 150000,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Updated Project',
        ]);
    }

    /** @test */
    public function user_can_delete_project()
    {
        ['user' => $user, 'company' => $company] = $this->createAuthenticatedUser();
        $project = Project::factory()->create(['company_id' => $company->id, 'created_by' => $user->id]);

        $response = $this->actingAs($user)->delete("/projects/{$project->id}");

        $response->assertRedirect();
        // Vérifier que le projet est soft deleted
        $this->assertNotNull($project->fresh()->deleted_at);
    }

    /** @test */
    public function user_cannot_access_other_company_project()
    {
        ['user' => $user] = $this->createAuthenticatedUser();
        $otherCompany = Company::factory()->create();
        $project = Project::factory()->create(['company_id' => $otherCompany->id]);

        $response = $this->actingAs($user)->get("/projects/{$project->id}");

        $response->assertStatus(403);
    }
}
