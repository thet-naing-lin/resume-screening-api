<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ResumeUploadTest extends TestCase
{
    use RefreshDatabase;

    // public function test_hr_can_upload_a_pdf_resume(): void
    // {
    //     Storage::fake('private');  // fake storage, no real files needed

    //     $user = User::factory()->create();

    //     // Create a fake job description
    //     $job = \App\Models\JobDescription::factory()->create();

    //     $response = $this->actingAs($user, 'sanctum')
    //         ->postJson('/api/resumes', [
    //             'resume_file'        => UploadedFile::fake()->create('john_cv.pdf', 500, 'application/pdf'),
    //             'job_description_id' => $job->id,
    //         ]);

    //     $response->assertStatus(201)
    //         ->assertJsonPath('data.status', 'uploaded')
    //         ->assertJsonPath('data.file_type', 'pdf');

    //     // Assert file is in storage
    //     $storedFilename = $response->json('data.stored_filename');
    //     Storage::disk('private')->assertExists("resumes/{$storedFilename}");

    //     // Assert DB has the record
    //     $this->assertDatabaseHas('resumes', [
    //         'uploaded_by' => $user->id,
    //         'file_type'   => 'pdf',
    //         'status'      => 'uploaded',
    //     ]);
    // }

    // public function test_upload_rejects_invalid_file_type(): void
    // {
    //     Storage::fake('private');
    //     $user = User::factory()->create();
    //     $job  = \App\Models\JobDescription::factory()->create();

    //     $response = $this->actingAs($user, 'sanctum')
    //         ->postJson('/api/resumes', [
    //             'resume_file'        => UploadedFile::fake()->create('malware.exe', 100),
    //             'job_description_id' => $job->id,
    //         ]);

    //     $response->assertStatus(422);  // Validation error
    // }

    // public function test_upload_rejects_file_over_5mb(): void
    // {
    //     Storage::fake('private');
    //     $user = User::factory()->create();
    //     $job  = \App\Models\JobDescription::factory()->create();

    //     $response = $this->actingAs($user, 'sanctum')
    //         ->postJson('/api/resumes', [
    //             'resume_file'        => UploadedFile::fake()->create('big.pdf', 6000), // 6MB
    //             'job_description_id' => $job->id,
    //         ]);

    //     $response->assertStatus(422);
    // }

    // public function test_unauthenticated_user_cannot_upload(): void
    // {
    //     $response = $this->postJson('/api/resumes', []);
    //     $response->assertStatus(401);
    // }
}
