<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Create roles needed for tests
        Role::create(['name' => 'admin',        'guard_name' => 'web']);
        Role::create(['name' => 'hr_recruiter', 'guard_name' => 'web']);
    }

    // ── Login ─────────────────────────────────────────────────────

    public function test_user_can_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'email'    => 'hr@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'hr@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token', 'user']);
    }

    public function test_login_fails_with_wrong_password()
    {
        User::factory()->create([
            'email'    => 'hr@example.com',
            'password' => Hash::make('correct_password'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'hr@example.com',
            'password' => 'wrong_password',
        ]);

        $response->assertStatus(422);
    }

    public function test_login_fails_with_missing_fields()
    {
        $response = $this->postJson('/api/auth/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_authenticated_user_can_logout()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/auth/logout');

        $response->assertStatus(200);
    }

    // ── Forgot / Reset Password ───────────────────────────────────

    public function test_forgot_password_returns_success_for_existing_email()
    {
        User::factory()->create(['email' => 'user@example.com']);

        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'user@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Password reset link sent to your email.']);
    }

    public function test_forgot_password_fails_for_non_existent_email()
    {
        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'nobody@example.com',
        ]);

        $response->assertStatus(422);
    }
}
