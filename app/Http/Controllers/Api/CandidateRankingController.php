<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\CandidateRankingResource;
use App\Models\Resume;
use App\Models\Score;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CandidateRankingController extends Controller
{
    /**
     * US-014 + US-015: Get ranked candidates for a job, with optional filters.
     */
    public function index(Request $request)
    {
        // job_description_id is required
        $request->validate([
            'job_description_id' => 'required|exists:job_descriptions,id',
            'min_score'          => 'nullable|numeric|min:0|max:100',
            'max_score'          => 'nullable|numeric|min:0|max:100',
            'status'             => 'nullable|in:shortlisted,under_review,rejected',
            // 'skill'              => 'nullable|string|max:100',
            'per_page'           => 'nullable|integer|min:5|max:100',
        ]);

        $query = Resume::with(['candidate', 'score'])
            ->where('resumes.job_description_id', $request->job_description_id)
            ->where('resumes.status', 'scored')              // only scored resumes
            ->join('scores', 'resumes.id', '=', 'scores.resume_id')
            ->select('resumes.*');

        // ── US-015: Filter by minimum score ─────────────────
        if ($request->filled('min_score')) {
            $query->where('scores.final_score', '>=', $request->min_score);
        }

        // ── US-015: Filter by maximum score ─────────────────
        if ($request->filled('max_score')) {
            $query->where('scores.final_score', '<=', $request->max_score);
        }

        // ── US-015: Filter by status (shortlisted / rejected / under_review)
        if ($request->filled('status')) {
            $query->where('scores.status', $request->status);
        }

        // ── US-015: Filter by skill (searches JSON array in candidates table)
        // if ($request->filled('skill')) {
        //     $skill = $request->skill;
        //     $query->whereHas('candidate', function ($q) use ($skill) {
        //         // JSON_SEARCH works in MySQL — searches inside extracted_skills JSON array
        //         $q->whereRaw(
        //             "JSON_SEARCH(LOWER(extracted_skills), 'one', ?) IS NOT NULL",
        //             ['%' . strtolower($skill) . '%']
        //         );
        //     });
        // }

        // ── US-014: Sort by final_score descending (highest ranked first)
        $query->orderByDesc('scores.final_score');

        $perPage = $request->input('per_page', 15);
        $results = $query->paginate($perPage);

        // Log::info('Candidate ranking retrieved', [
        //     'results' => $results->items(),
        // ]);

        return CandidateRankingResource::collection($results);
    }

    /**
     * Update a candidate's screening status (shortlist / reject)
     * Bonus feature — HR can act on a candidate directly from the ranking page
     */
    public function updateStatus(Request $request, int $resumeId)
    {
        $request->validate([
            'status' => 'required|in:shortlisted,under_review,rejected',
        ]);

        $score = Score::where('resume_id', $resumeId)->firstOrFail();
        $score->update(['status' => $request->status]);

        AuditLogger::log('candidate.status_changed', $score->resume, [
            'new_status' => $request->status,
            'resume_id'  => $resumeId,
        ]);

        return response()->json([
            'message' => 'Status updated successfully.',
            'status'  => $score->status,
        ]);
    }

    // “Take the same ranked list you see in the UI, turn it into an Excel/CSV file, and stream it to the browser as a download.”
    /**
     * US-018: Export ranked candidates as CSV
     * GET /api/rankings/export?job_description_id=1&status=shortlisted (status optional)
     */
    public function export(Request $request): StreamedResponse
    {
        $request->validate([
            'job_description_id' => 'required|exists:job_descriptions,id',
            'status'             => 'nullable|in:shortlisted,under_review,rejected',
            'min_score'          => 'nullable|numeric|min:0|max:100',
            'max_score'          => 'nullable|numeric|min:0|max:100',
        ]);

        // Same query logic as index() — reuse it
        $query = Resume::with(['candidate', 'score', 'jobDescription'])
            ->where('resumes.job_description_id', $request->job_description_id)
            ->where('resumes.status', 'scored')
            ->join('scores', 'resumes.id', '=', 'scores.resume_id')
            ->select('resumes.*')
            ->orderByDesc('scores.final_score');

        if ($request->filled('status')) {
            $query->where('scores.status', $request->status);
        }
        if ($request->filled('min_score')) {
            $query->where('scores.final_score', '>=', $request->min_score);
        }
        if ($request->filled('max_score')) {
            $query->where('scores.final_score', '<=', $request->max_score);
        }

        $resumes = $query->get();

        // Get job title for the filename
        $jobTitle = $resumes->first()?->jobDescription->title ?? 'candidates';
        $safeTitle = str_replace([' ', '/'], '_', strtolower($jobTitle));
        $filename = "rankings_{$safeTitle}_" . now()->format('Ymd_His') . ".csv";

        // Stream the CSV response
        return response()->streamDownload(function () use ($resumes) {

            $handle = fopen('php://output', 'w');

            // CSV Header row
            fputcsv($handle, [
                'Rank',
                'Candidate Name',
                'Email',
                'Phone',
                'Resume File',
                'TF-IDF Score',
                'Semantic Score',
                'Final Score',
                'Status',
                'AI Summary',
                'Interview Question 1',
                'Interview Question 2',
                'Interview Question 3',
                'Interview Question 4',
                'Interview Question 5',
                'Uploaded At',
            ]);

            // Data rows
            foreach ($resumes as $index => $resume) {

                // questions_json is already cast to array in Score model
                $questions = $resume->score?->questions_json ?? [];

                fputcsv($handle, [
                    $index + 1,
                    $resume->candidate?->name       ?? 'N/A',
                    $resume->candidate?->email      ?? 'N/A',
                    $resume->candidate?->phone      ?? 'N/A',
                    $resume->original_filename,
                    number_format($resume->score?->tfidf_score    ?? 0, 2),
                    number_format($resume->score?->semantic_score ?? 0, 2),
                    number_format($resume->score?->final_score    ?? 0, 2),
                    $resume->score?->status         ?? 'N/A',
                    $resume->score?->ai_summary     ?? 'Not generated',
                    $questions[0] ?? 'Not generated',
                    $questions[1] ?? 'Not generated',
                    $questions[2] ?? 'Not generated',
                    $questions[3] ?? 'Not generated',
                    $questions[4] ?? 'Not generated',
                    $resume->created_at->format('d M Y H:i'),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
