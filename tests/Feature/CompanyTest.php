<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CompanyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Créer les rôles
        Role::create(['name' => 'admin', 'display_name' => 'Administrateur', 'description' => 'Admin', 'permissions' => ['*']]);
    }

    /** @test */
    public function authenticated_user_can_create_company()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post('/companies', [
            'name' => 'New Company',
            'email' => 'company@example.com',
            'phone' => '0123456789',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('companies', ['name' => 'New Company']);
    }

    /** @test */
    public function user_can_switch_companies()
    {
        $user = User::factory()->create();
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();
        $role = Role::where('name', 'admin')->first();
        
        $user->companies()->attach($company1->id, ['role_id' => $role->id, 'is_active' => true]);
        $user->companies()->attach($company2->id, ['role_id' => $role->id, 'is_active' => true]);
        $user->update(['current_company_id' => $company1->id]);

        $response = $this->actingAs($user)->post("/companies/{$company2->id}/switch");

        $response->assertStatus(302); // Peut rediriger vers dashboard ou autre
        $this->assertEquals($company2->id, $user->fresh()->current_company_id);
    }

    /** @test */
    public function user_can_view_companies()
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();
        $role = Role::where('name', 'admin')->first();
        
        $user->companies()->attach($company->id, ['role_id' => $role->id, 'is_active' => true]);
        $user->update(['current_company_id' => $company->id]);

        $response = $this->actingAs($user)->get('/companies');

        $response->assertStatus(200);
        $response->assertSee($company->name);
    }

    /** @test */
    public function user_can_update_company()
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();
        $role = Role::where('name', 'admin')->first();
        
        $user->companies()->attach($company->id, ['role_id' => $role->id, 'is_active' => true]);
        $user->update(['current_company_id' => $company->id]);

        $response = $this->actingAs($user)->put("/companies/{$company->id}", [
            'name' => 'Updated Company',
            'email' => 'updated@example.com',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'name' => 'Updated Company',
        ]);
    }
}
