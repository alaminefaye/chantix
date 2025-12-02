<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Material;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MaterialTest extends TestCase
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
    public function authenticated_user_can_create_material()
    {
        ['user' => $user, 'company' => $company] = $this->createAuthenticatedUser();

        $response = $this->actingAs($user)->post('/materials', [
            'name' => 'Ciment',
            'description' => 'Ciment Portland',
            'unit' => 'kg',
            'unit_price' => 0.50,
            'category' => 'ciment',
            'current_stock' => 1000,
            'min_stock_level' => 100,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('materials', [
            'name' => 'Ciment',
            'company_id' => $company->id,
        ]);
    }

    /** @test */
    public function user_can_view_materials()
    {
        ['user' => $user, 'company' => $company] = $this->createAuthenticatedUser();
        $material = Material::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)->get('/materials');

        $response->assertStatus(200);
        $response->assertSee($material->name);
    }

    /** @test */
    public function user_can_update_material()
    {
        ['user' => $user, 'company' => $company] = $this->createAuthenticatedUser();
        $material = Material::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)->put("/materials/{$material->id}", [
            'name' => 'Updated Material',
            'unit' => $material->unit, // Champ requis
            'unit_price' => 0.75,
            'stock_quantity' => 1500,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('materials', [
            'id' => $material->id,
            'name' => 'Updated Material',
        ]);
    }

    /** @test */
    public function user_cannot_access_other_company_material()
    {
        ['user' => $user] = $this->createAuthenticatedUser();
        $otherCompany = Company::factory()->create();
        $material = Material::factory()->create(['company_id' => $otherCompany->id]);

        $response = $this->actingAs($user)->get("/materials/{$material->id}");

        $response->assertStatus(403);
    }
}
