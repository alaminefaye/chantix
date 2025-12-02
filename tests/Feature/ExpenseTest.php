<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Project;
use App\Models\Expense;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExpenseTest extends TestCase
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
    public function authenticated_user_can_create_expense()
    {
        ['user' => $user, 'company' => $company] = $this->createAuthenticatedUser();
        $project = Project::factory()->create(['company_id' => $company->id, 'created_by' => $user->id]);

        $response = $this->actingAs($user)->post("/projects/{$project->id}/expenses", [
            'title' => 'Test Expense',
            'description' => 'Test Description',
            'amount' => 1000,
            'type' => 'materiaux',
            'expense_date' => now()->format('Y-m-d'),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('expenses', [
            'title' => 'Test Expense',
            'project_id' => $project->id,
        ]);
    }
}
