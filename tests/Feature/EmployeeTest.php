<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EmployeeTest extends TestCase
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
    public function authenticated_user_can_create_employee()
    {
        ['user' => $user, 'company' => $company] = $this->createAuthenticatedUser();

        $response = $this->actingAs($user)->post('/employees', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '0123456789',
            'position' => 'maçon',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('employees', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'company_id' => $company->id,
        ]);
    }

    /** @test */
    public function user_can_view_employees()
    {
        ['user' => $user, 'company' => $company] = $this->createAuthenticatedUser();
        $employee = Employee::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)->get('/employees');

        $response->assertStatus(200);
        $response->assertSee($employee->first_name);
    }
}
