<?php

namespace Database\Factories;

use App\Models\Resume;
use App\Models\ResumeScore;
use Illuminate\Database\Eloquent\Factories\Factory;

class ResumeScoreFactory extends Factory
{
    protected $model = ResumeScore::class;

    public function definition(): array
    {
        $tfidf    = fake()->randomFloat(4, 0.0, 1.0);
        $semantic = fake()->randomFloat(4, 0.0, 1.0);
        $final    = round(($tfidf * 0.4) + ($semantic * 0.6), 4); // matches your 40/60 weighting

        return [
            'resume_id'       => Resume::factory(),
            'tfidf_score'     => $tfidf,
            'semantic_score'  => $semantic,
            'final_score'     => $final,
            'status'          => 'under_review',
            'matched_keywords' => fake()->words(5),
            'ai_summary'      => null,
            'interview_questions' => null,
        ];
    }

    public function shortlisted(): static
    {
        return $this->state(fn() => ['status' => 'shortlisted']);
    }

    public function rejected(): static
    {
        return $this->state(fn() => ['status' => 'rejected']);
    }

    public function underReview(): static
    {
        return $this->state(fn() => ['status' => 'under_review']);
    }

    // High scoring candidate
    public function highScore(): static
    {
        return $this->state(fn() => [
            'tfidf_score'    => 0.85,
            'semantic_score' => 0.90,
            'final_score'    => round((0.85 * 0.4) + (0.90 * 0.6), 4),
        ]);
    }

    // Low scoring candidate
    public function lowScore(): static
    {
        return $this->state(fn() => [
            'tfidf_score'    => 0.20,
            'semantic_score' => 0.15,
            'final_score'    => round((0.20 * 0.4) + (0.15 * 0.6), 4),
        ]);
    }
}
