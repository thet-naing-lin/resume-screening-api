<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\Resume;
use App\Models\ResumeScore;
use App\Models\User;
use App\Models\JobDescription;
use Database\Factories\ResumeScoreFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class CandidateMailControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $hr;
    private User $hr2;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake(); // intercept all emails — nothing actually sends

        Role::create(['name' => 'admin',        'guard_name' => 'web']);
        Role::create(['name' => 'hr_recruiter', 'guard_name' => 'web']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->hr = User::factory()->create();
        $this->hr->assignRole('hr_recruiter');

        $this->hr2 = User::factory()->create();
        $this->hr2->assignRole('hr_recruiter');
    }

    // ── Template ──────────────────────────────────────────────────

    public function test_template_returns_interview_content()
    {
        $response = $this->actingAs($this->hr)
            ->getJson('/api/candidates/mail-template?type=interview&candidate_name=John&job_title=Developer');

        $response->assertStatus(200)
            ->assertJsonStructure(['subject', 'body'])
            ->assertJsonFragment(['subject' => 'Interview Invitation – Developer Position']);
    }

    public function test_template_returns_rejection_content()
    {
        $response = $this->actingAs($this->hr)
            ->getJson('/api/candidates/mail-template?type=rejection&candidate_name=Jane&job_title=Designer');

        $response->assertStatus(200)
            ->assertJsonStructure(['subject', 'body']);
    }

    public function test_template_fails_with_invalid_type()
    {
        $response = $this->actingAs($this->hr)
            ->getJson('/api/candidates/mail-template?type=invalid&candidate_name=John&job_title=Dev');

        $response->assertStatus(422);
    }

    // ── Individual Send ───────────────────────────────────────────

    public function test_hr_can_send_individual_email_to_candidate()
    {
        $candidate = Candidate::factory()->create(['email' => 'candidate@test.com']);
        $resume    = Resume::factory()->create([
            'uploaded_by'  => $this->hr->id,
            'candidate_id' => $candidate->id,
        ]);

        $response = $this->actingAs($this->hr)
            ->postJson('/api/candidates/send-mail', [
                'resume_id' => $resume->id,
                'type'      => 'interview',
                'subject'   => 'Interview Invite',
                'body'      => 'You are invited for an interview.',
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => "Email sent successfully to candidate@test.com"]);

        Mail::assertSentCount(1);
    }

    public function test_individual_send_fails_when_no_email_on_record()
    {
        $candidate = Candidate::factory()->create(['email' => null]);
        $resume    = Resume::factory()->create([
            'uploaded_by'  => $this->hr->id,
            'candidate_id' => $candidate->id,
        ]);

        $response = $this->actingAs($this->hr)
            ->postJson('/api/candidates/send-mail', [
                'resume_id' => $resume->id,
                'type'      => 'interview',
                'subject'   => 'Test',
                'body'      => 'Test body',
            ]);

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'No email address provided.']);
    }

    // ── Bulk Send ─────────────────────────────────────────────────

    // public function test_hr_bulk_send_only_emails_their_own_candidates()
    // {
    //     Mail::fake();

    //     $job = JobDescription::factory()->create(['user_id' => $this->hr->id]);

    //     $ownCandidate = Candidate::factory()->create(['email' => 'own@example.com']);
    //     $resume = Resume::factory()->create([
    //         'candidate_id'       => $ownCandidate->id,
    //         'job_description_id' => $job->id,
    //         'uploaded_by'        => $this->hr->id,
    //         'status'             => 'scored',   // ✅ valid resume status
    //     ]);

    //     // Insert score record directly — no model needed
    //     DB::table('scores')->insert([
    //         'resume_id'  => $resume->id,
    //         'status'     => 'shortlisted',
    //         'created_at' => now(),
    //         'updated_at' => now(),
    //     ]);

    //     // Other HR's candidate — should NOT get email
    //     $otherHr  = User::factory()->create();
    //     DB::table('user_roles')->insert([  ]); // use your role pattern here
    //     $otherJob = JobDescription::factory()->create(['user_id' => $otherHr->id]);
    //     $otherCandidate = Candidate::factory()->create(['email' => 'other@example.com']);
    //     $otherResume = Resume::factory()->create([
    //         'candidate_id'       => $otherCandidate->id,
    //         'job_description_id' => $otherJob->id,
    //         'uploaded_by'        => $otherHr->id,
    //         'status'             => 'scored',
    //     ]);
    //     DB::table('scores')->insert([
    //         'resume_id'  => $otherResume->id,
    //         'status'     => 'shortlisted',
    //         'created_at' => now(),
    //         'updated_at' => now(),
    //     ]);

    //     $response = $this->actingAs($this->hr)
    //         ->postJson('/api/candidates/mail/send-bulk', [
    //             'status'  => 'shortlisted',
    //             'subject' => 'Interview Invitation',
    //             'body'    => 'You have been shortlisted.',
    //         ]);

    //     $response->assertStatus(200);
    //     $this->assertCount(1, $response->json('sent'));
    //     $this->assertEquals('own@example.com', $response->json('sent.0.email'));
    // }

    public function test_admin_bulk_send_emails_all_shortlisted_candidates()
    {
        Mail::fake();

        $job1 = JobDescription::factory()->create(['user_id' => $this->hr->id]);

        $candidate1 = Candidate::factory()->create(['email' => 'c1@example.com']);
        $candidate2 = Candidate::factory()->create(['email' => 'c2@example.com']);

        $resume1 = Resume::factory()->create([
            'candidate_id'       => $candidate1->id,
            'job_description_id' => $job1->id,
            'uploaded_by'        => $this->hr->id,
            'status'             => 'scored',
        ]);
        $resume2 = Resume::factory()->create([
            'candidate_id'       => $candidate2->id,
            'job_description_id' => $job1->id,
            'uploaded_by'        => $this->hr->id,
            'status'             => 'scored',
        ]);

        DB::table('scores')->insert([
            ['resume_id' => $resume1->id, 'status' => 'shortlisted', 'created_at' => now(), 'updated_at' => now()],
            ['resume_id' => $resume2->id, 'status' => 'shortlisted', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/candidates/mail/send-bulk', [
                'status'  => 'shortlisted',
                'subject' => 'Interview Invitation',
                'body'    => 'You have been shortlisted.',
            ]);

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('sent'));
    }

    public function test_bulk_send_returns_404_when_no_candidates_match_status()
    {
        $response = $this->actingAs($this->hr)
            ->postJson('/api/candidates/mail/send-bulk', [
                'status'  => 'shortlisted',
                'subject' => 'Test',
                'body'    => 'Test body.',
            ]);

        $response->assertStatus(404);
    }
}
