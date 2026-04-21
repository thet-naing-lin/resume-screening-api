<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    private string $apiKey;
    private string $apiUrl;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key');
        $this->apiUrl = config('services.gemini.url');
    }

    /**
     * Send a prompt to Gemini and get a text response back
     */
    public function generate(string $prompt): ?string
    {
        try {
            $response = Http::timeout(30)
                ->post("{$this->apiUrl}?key={$this->apiKey}", [
                    'contents' => [
                        ['parts' => [['text' => $prompt]]]
                    ],
                    'generationConfig' => [
                        'temperature'     => 0.7,
                        'maxOutputTokens' => 1024,
                    ],
                ]);

            if ($response->failed()) {
                Log::error('Gemini API failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return null;
            }

            return $response->json('candidates.0.content.parts.0.text');
        } catch (\Exception $e) {
            Log::error('Gemini Service error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * US-016: Generate a 3-4 sentence candidate summary
     */
    public function generateSummary(string $resumeText, string $jobTitle): ?string
    {
        $prompt = <<<PROMPT
            You are an HR assistant. Based on the resume below, write a concise 3-4 sentence professional summary of this candidate for the role of "{$jobTitle}". 
            Focus on their key skills, experience level, and how well they fit the role.
            Be objective and professional. Do not invent information not present in the resume.

            Resume:
            {$resumeText}
            PROMPT;

        return $this->generate($prompt);
    }

    /**
     * US-017: Generate 5 interview questions
     * Returns an array of 5 question strings
     */
    public function generateInterviewQuestions(string $resumeText, string $jobTitle, string $jobDescription): array
    {
        $prompt = <<<PROMPT
            You are an HR interviewer. Based on the candidate's resume and the job description below, generate exactly 5 targeted interview questions.

            Rules:
            - Questions should be specific to THIS candidate's background and THIS job
            - Mix of technical and behavioral questions
            - Return ONLY a JSON array of 5 strings, no extra text, no numbering
            - Example format: ["Question 1?", "Question 2?", "Question 3?", "Question 4?", "Question 5?"]

            Job Title: {$jobTitle}
            Job Description: {$jobDescription}

            Candidate Resume:
            {$resumeText}
            PROMPT;

        $raw = $this->generate($prompt);
        if (!$raw) return [];

        // Strip markdown code blocks if Gemini wraps in ```json
        $cleaned = preg_replace('/```json\s*|\s*```/', '', trim($raw));

        $questions = json_decode($cleaned, true);

        return is_array($questions) ? array_slice($questions, 0, 5) : [];
    }
}
