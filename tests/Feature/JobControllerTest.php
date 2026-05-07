<?php

namespace Tests\Feature;

use App\Models\JobDescription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class JobControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $hr;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin',        'guard_name' => 'web']);
        Role::create(['name' => 'hr_recruiter', 'guard_name' => 'web']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->hr = User::factory()->create();
        $this->hr->assignRole('hr_recruiter');
    }

    // ── List ──────────────────────────────────────────────────────

    public function test_hr_can_list_job_descriptions()
    {
        JobDescription::factory()->count(3)->create();

        $response = $this->actingAs($this->hr)
            ->getJson('/api/jobs');

        $response->assertStatus(200);
    }

    public function test_unauthenticated_user_cannot_list_jobs()
    {
        $response = $this->getJson('/api/jobs');

        $response->assertStatus(401);
    }

    // ── Create ────────────────────────────────────────────────────

    public function test_hr_can_create_a_job_description()
    {
        $response = $this->actingAs($this->hr)
            ->postJson('/api/jobs', [
                'title'       => 'Full Stack Developer',
                'description' => 'We need a developer with Laravel and React experience.',
                'required_skills'  => ['PHP', 'Laravel', 'React'],
                'experience_level' => 'mid',
                'employment_type'  => 'full-time',
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['title' => 'Full Stack Developer']);

        $this->assertDatabaseHas('job_descriptions', ['title' => 'Full Stack Developer']);
    }

    public function test_creating_job_fails_without_required_fields()
    {
        $response = $this->actingAs($this->hr)
            ->postJson('/api/jobs', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'description']);
    }

    // ── Update ────────────────────────────────────────────────────

    public function test_hr_can_update_their_own_job_description()
    {
        $job = JobDescription::factory()->create(['created_by' => $this->hr->id]);

        $response = $this->actingAs($this->hr)
            ->putJson("/api/jobs/{$job->id}", [
                'title'       => 'Updated Title',
                'description' => 'Updated description text.',
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['title' => 'Updated Title']);
    }

    // ── Delete ────────────────────────────────────────────────────

    public function test_hr_can_delete_a_job_description()
    {
        $job = JobDescription::factory()->create(['created_by' => $this->hr->id]);

        $response = $this->actingAs($this->hr)
            ->deleteJson("/api/jobs/{$job->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('job_descriptions', ['id' => $job->id]);
    }
}
