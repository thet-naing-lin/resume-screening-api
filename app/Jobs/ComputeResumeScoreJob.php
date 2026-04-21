<?php

namespace App\Jobs;

use App\Models\Resume;
use App\Models\Score;
use App\Services\ScoringService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ComputeResumeScoreJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;      // retry up to 3 times on failure
    public int $timeout = 120;    // 2 min max (model can be slow)

    public function __construct(public Resume $resume) {}

    public function handle(ScoringService $scorer): void
    {
        $resume = $this->resume->load('jobDescription');

        // ✅ Guard check
        if (empty($resume->raw_text) || empty($resume->jobDescription?->description)) {
            Log::warning("ComputeResumeScoreJob: Missing text for resume #{$resume->id}");
            $resume->update(['status' => 'failed']);
            return;
        }

        $resume->update(['status' => 'scoring']);

        $resumeText = $resume->raw_text;
        $jobText    = $resume->jobDescription->description;

        // Log previews of both texts
        Log::info("Scoring resume #{$resume->id} text samples", [
            'resume_preview' => mb_substr($resumeText, 0, 400),
            'job_preview'    => mb_substr($jobText, 0, 400),
        ]);

        // US-011: TF-IDF
        $tfidfScore = $scorer->computeTfIdf($resumeText, $jobText);

        // US-012: Semantic
        $semanticScore = $scorer->computeSemantic($resumeText, $jobText);

        // US-013: Weighted final
        $finalScore = round(($tfidfScore * 0.40) + ($semanticScore * 0.60), 2);

        Score::updateOrCreate(
            ['resume_id' => $resume->id],
            [
                'job_description_id' => $resume->job_description_id,
                'tfidf_score'        => $tfidfScore,
                'semantic_score'     => $semanticScore,
                'final_score'        => $finalScore,
                'status'             => 'under_review',
            ]
        );

        $resume->update(['status' => 'scored']);

        Log::info("Resume #{$resume->id} scored: TF-IDF={$tfidfScore}, Semantic={$semanticScore}, Final={$finalScore}");
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("ComputeResumeScoreJob failed for resume #{$this->resume->id}: " . $exception->getMessage());
        $this->resume->update(['status' => 'failed']);
    }
}
