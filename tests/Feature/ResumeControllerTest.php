<?php

namespace Tests\Feature;

use App\Models\Resume;
use App\Models\User;
use App\Models\JobDescription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ResumeControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $hr;
    private User $hr2;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        Role::create(['name' => 'admin',        'guard_name' => 'web']);
        Role::create(['name' => 'hr_recruiter', 'guard_name' => 'web']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->hr = User::factory()->create();
        $this->hr->assignRole('hr_recruiter');

        $this->hr2 = User::factory()->create();
        $this->hr2->assignRole('hr_recruiter');
    }

    // ── Upload ────────────────────────────────────────────────────

    public function test_hr_can_upload_a_pdf_resume()
    {
        $job  = JobDescription::factory()->create();
        $file = UploadedFile::fake()->create('candidate.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->hr)
            ->postJson('/api/resumes', [
                'resume'             => [$file],
                'job_description_id' => $job->id,
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('resumes', ['original_filename' => 'candidate.pdf']);
    }

    public function test_upload_rejects_non_pdf_docx_files()
    {
        $job  = JobDescription::factory()->create();
        $file = UploadedFile::fake()->create('photo.jpg', 200, 'image/jpeg');

        $response = $this->actingAs($this->hr)
            ->postJson('/api/resumes', [
                'resume'             => $file,
                'job_description_id' => $job->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['resume']);
    }

    public function test_upload_rejects_files_over_5mb()
    {
        $job  = JobDescription::factory()->create();
        $file = UploadedFile::fake()->create('big.pdf', 6000, 'application/pdf'); // 6MB

        $response = $this->actingAs($this->hr)
            ->postJson('/api/resumes', [
                'resume'             => $file,
                'job_description_id' => $job->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['resume']);
    }

    // ── Role-based data isolation ─────────────────────────────────

    public function test_hr_only_sees_their_own_resumes()
    {
        // hr uploads 2 resumes, hr2 uploads 1
        Resume::factory()->count(2)->create(['uploaded_by' => $this->hr->id]);
        Resume::factory()->count(1)->create(['uploaded_by' => $this->hr2->id]);

        $response = $this->actingAs($this->hr)
            ->getJson('/api/resumes');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    public function test_admin_sees_all_resumes()
    {
        Resume::factory()->count(2)->create(['uploaded_by' => $this->hr->id]);
        Resume::factory()->count(1)->create(['uploaded_by' => $this->hr2->id]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/resumes');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(3, $data);
    }

    // ── Delete ────────────────────────────────────────────────────

    public function test_hr_can_delete_their_own_uploaded_resume()
    {
        $resume = Resume::factory()->create([
            'uploaded_by' => $this->hr->id,
            'status'      => 'uploaded',
        ]);

        $response = $this->actingAs($this->hr)
            ->deleteJson("/api/resumes/{$resume->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('resumes', ['id' => $resume->id]);
    }

    public function test_hr_cannot_delete_another_hrs_resume()
    {
        $resume = Resume::factory()->create([
            'uploaded_by' => $this->hr2->id,
            'status'      => 'uploaded',
        ]);

        $response = $this->actingAs($this->hr)
            ->deleteJson("/api/resumes/{$resume->id}");

        $response->assertStatus(403);
    }
}
