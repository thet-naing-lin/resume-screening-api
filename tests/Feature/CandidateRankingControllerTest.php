<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\Resume;
use App\Models\ResumeScore;
use App\Models\User;
use App\Models\JobDescription;
use Database\Factories\ResumeScoreFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CandidateRankingControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $hr;
    private User $hr2;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin',        'guard_name' => 'web']);
        Role::create(['name' => 'hr', 'guard_name' => 'web']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->hr = User::factory()->create();
        $this->hr->assignRole('hr');

        $this->hr2 = User::factory()->create();
        $this->hr2->assignRole('hr');
    }

    public function test_hr_only_sees_rankings_for_their_own_resumes()
    {
        $c1 = Candidate::factory()->create();
        $c2 = Candidate::factory()->create();

        $r1 = Resume::factory()->create(['uploaded_by' => $this->hr->id,  'candidate_id' => $c1->id]);
        $r2 = Resume::factory()->create(['uploaded_by' => $this->hr2->id, 'candidate_id' => $c2->id]);

        ResumeScoreFactory::factory()->create(['resume_id' => $r1->id]);
        ResumeScoreFactory::factory()->create(['resume_id' => $r2->id]);

        $resume = Resume::factory()->create([
            'uploaded_by' => $this->hr->id,
            'status'      => 'shortlisted',
            'score'       => 85.5,
        ]);

        $response = $this->actingAs($this->hr)
            ->getJson('/api/candidate-rankings');

        $response->assertStatus(200);
        $ids = collect($response->json('data'))->pluck('resume_id')->toArray();

        $this->assertContains($r1->id, $ids);
        $this->assertNotContains($r2->id, $ids);
    }

    public function test_hr_can_update_candidate_status_to_shortlisted()
    {
        $resume = Resume::factory()->create([
            'uploaded_by' => $this->hr->id,
            'status'      => 'shortlisted',
            'score'       => 85.5,
        ]);

        $score  = ResumeScoreFactory::factory()->create([
            'resume_id' => $resume->id,
            'status'    => 'under_review',
        ]);

        $response = $this->actingAs($this->hr)
            ->patchJson("/api/candidate-rankings/{$score->id}/status", [
                'status' => 'shortlisted',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('resume_scores', [
            'id'     => $score->id,
            'status' => 'shortlisted',
        ]);
    }

    public function test_status_update_rejects_invalid_status_value()
    {
        $resume = Resume::factory()->create(['uploaded_by' => $this->hr->id]);
        $score  = ResumeScoreFactory::factory()->create(['resume_id' => $resume->id]);

        $response = $this->actingAs($this->hr)
            ->patchJson("/api/candidate-rankings/{$score->id}/status", [
                'status' => 'maybe',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_hr_can_export_rankings_as_csv()
    {
        $candidate = Candidate::factory()->create();
        $resume    = Resume::factory()->create([
            'uploaded_by'  => $this->hr->id,
            'candidate_id' => $candidate->id,
        ]);
        ResumeScoreFactory::factory()->create(['resume_id' => $resume->id]);

        $response = $this->actingAs($this->hr)
            ->getJson('/api/candidate-rankings/export');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }
}
