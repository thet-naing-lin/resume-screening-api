<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\CandidateRankingResource;
use App\Models\Resume;
use App\Models\Score;
use Illuminate\Support\Facades\Log;

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

        return response()->json([
            'message' => 'Status updated successfully.',
            'status'  => $score->status,
        ]);
    }
}
