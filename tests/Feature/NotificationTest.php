<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Notification;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

class NotificationTest extends TestCase
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
    public function user_can_view_notifications()
    {
        ['user' => $user] = $this->createAuthenticatedUser();
        $notification = Notification::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/notifications');

        $response->assertStatus(200);
        $response->assertSee($notification->title);
    }

    /** @test */
    public function user_can_mark_notification_as_read()
    {
        ['user' => $user] = $this->createAuthenticatedUser();
        $notification = Notification::factory()->create([
            'user_id' => $user->id,
            'is_read' => false,
        ]);

        $response = $this->actingAs($user)->post("/notifications/{$notification->id}/read");

        $response->assertStatus(200);
        $this->assertTrue($notification->fresh()->is_read);
    }

    /** @test */
    public function user_can_get_unread_count()
    {
        ['user' => $user] = $this->createAuthenticatedUser();
        Notification::factory()->count(3)->create([
            'user_id' => $user->id,
            'is_read' => false,
        ]);

        $response = $this->actingAs($user)->get('/api/notifications/unread-count');

        $response->assertStatus(200);
        $response->assertJson(['count' => 3]);
    }
}
