<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminUserControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $hr;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin',        'guard_name' => 'web']);
        Role::create(['name' => 'hr', 'guard_name' => 'web']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->hr = User::factory()->create();
        $this->hr->assignRole('hr');
    }

    // ── List users ────────────────────────────────────────────────


    public function test_admin_can_list_all_users()
    {
        User::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/users');

        $response->assertStatus(200);
    }

    public function test_hr_cannot_access_user_list()
    {
        $response = $this->actingAs($this->hr)
            ->getJson('/api/admin/users');

        $response->assertStatus(403);
    }

    // ── Create user ───────────────────────────────────────────────

    public function test_admin_can_create_a_new_user()
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/users', [
                'name'     => 'New HR',
                'email'    => 'newhr@example.com',
                'password' => 'password123',
                'role'     => 'hr',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', ['email' => 'newhr@example.com']);
    }

    public function test_hr_cannot_create_users()
    {
        $response = $this->actingAs($this->hr)
            ->postJson('/api/admin/users', [
                'name'     => 'Sneaky User',
                'email'    => 'sneaky@example.com',
                'password' => 'password123',
                'role'     => 'hr',
            ]);

        $response->assertStatus(403);
    }

    public function test_creating_user_fails_with_duplicate_email()
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/users', [
                'name'     => 'Another User',
                'email'    => 'taken@example.com',
                'password' => 'password123',
                'role'     => 'hr',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    // ── Assign role ───────────────────────────────────────────────

    public function test_admin_can_assign_role_to_user()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->admin)
            ->patchJson("/api/admin/users/{$user->id}/role", [
                'role' => 'hr',
            ]);

        $response->assertStatus(200);
        $this->assertTrue($user->fresh()->hasRole('hr'));
    }

    // ── Delete user ───────────────────────────────────────────────

    public function test_admin_can_delete_a_user()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/admin/users/{$user->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_admin_cannot_delete_themselves()
    {
        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/admin/users/{$this->admin->id}");

        $response->assertStatus(403);
    }
}
