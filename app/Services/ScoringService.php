<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ScoringService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.python_scorer.url');
    }

    /**
     * US-011: Compute TF-IDF cosine similarity score
     * Returns a score between 0 and 100
     */
    public function computeTfIdf(string $resumeText, string $jobText): float
    {
        try {
            $response = Http::timeout(30)
                ->post("{$this->baseUrl}/score/tfidf", [
                    'resume_text' => $resumeText,
                    'job_text'    => $jobText,
                ]);

            if ($response->failed()) {
                Log::error('TF-IDF scoring failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return 0.0;
            }

            return (float) $response->json('tfidf_score', 0);
        } catch (\Exception $e) {
            Log::error('TF-IDF service unreachable: ' . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * US-012: Compute MiniLM-L6-V2 semantic similarity score
     * Returns a score between 0 and 100
     */
    public function computeSemantic(string $resumeText, string $jobText): float
    {
        try {
            $response = Http::timeout(60) // semantic takes longer
                ->post("{$this->baseUrl}/score/semantic", [
                    'resume_text' => $resumeText,
                    'job_text'    => $jobText,
                ]);

            if ($response->failed()) {
                Log::error('Semantic scoring failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return 0.0;
            }

            return (float) $response->json('semantic_score', 0);
        } catch (\Exception $e) {
            Log::error('Semantic service unreachable: ' . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Check if Python service is alive
     */
    public function isAvailable(): bool
    {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/health");
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }
}
