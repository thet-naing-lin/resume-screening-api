<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Resume;
use App\Models\Score;
use App\Services\AuditLogger;
use App\Services\GeminiService;
use Illuminate\Http\Request;

class AiInsightController extends Controller
{
    public function __construct(private GeminiService $gemini) {}

    /**
     * US-016 + US-017: Generate summary AND questions for a resume
     * Called when HR clicks "Generate AI Insights" button
     */
    public function generate(int $resumeId)
    {
        $resume = Resume::with(['jobDescription', 'score'])->findOrFail($resumeId);

        if (!$resume->jobDescription) {
            return response()->json(['message' => 'Job description not found.'], 422);
        }

        if (empty($resume->raw_text)) {
            return response()->json(['message' => 'Resume text not available.'], 422);
        }

        $jobTitle       = $resume->jobDescription->title;
        $jobDescription = $resume->jobDescription->description;
        $resumeText     = $resume->raw_text;

        // US-016: Generate summary
        $summary = $this->gemini->generateSummary($resumeText, $jobTitle);

        // US-017: Generate 5 questions
        $questions = $this->gemini->generateInterviewQuestions(
            $resumeText,
            $jobTitle,
            $jobDescription
        );

        // Save summary + questions_json to scores table
        Score::where('resume_id', $resumeId)->update([
            'ai_summary'     => $summary,
            'questions_json' => $questions,
        ]);

        AuditLogger::log('ai.insights_generated', $resume, [
            'job_title' => $jobTitle,
        ]);

        return response()->json([
            'message'   => 'AI insights generated successfully.',
            'summary'   => $summary,
            'questions' => $questions,
        ]);
    }

    /**
     * Fetch existing AI insights for a resume (no re-generation)
     */
    public function show(int $resumeId)
    {
        $score = Score::where('resume_id', $resumeId)
            ->select('ai_summary', 'questions_json')
            ->first();

        return response()->json([
            'summary'   => $score?->ai_summary,
            'questions' => $score?->questions_json ?? [],
        ]);
    }
}
