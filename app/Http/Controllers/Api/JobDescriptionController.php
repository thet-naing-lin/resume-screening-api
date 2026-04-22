<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobDescription;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class JobDescriptionController extends Controller
{
    // GET /api/jobs
    public function index()
    {
        $jobs = JobDescription::with('creator')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($job) => $this->formatJob($job));

        return response()->json(['jobs' => $jobs]);
    }

    // GET /api/jobs/{job}
    public function show(JobDescription $job)
    {
        return response()->json(['job' => $this->formatJob($job)]);
    }

    // POST /api/jobs
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'             => 'required|string|max:255',
            'description'       => 'required|string|min:20',
            'required_skills'   => 'required|array|min:1',
            'required_skills.*' => 'string|max:50',
            'required_qualification' => 'nullable|string|max:1000',
            'experience_level'  => 'required|in:junior,mid,senior',
            'experience_years'       => 'nullable|integer|min:0|max:50',
            'employment_type'   => 'required|in:full-time,part-time,contract,internship,freelance',
            'location'          => 'nullable|string|max:255',
            'status'            => 'sometimes|in:active,closed',
        ]);

        $job = JobDescription::create([
            ...$validated,
            'user_id' => auth()->id(),
        ]);

        AuditLogger::log('job.created', $job, ['title' => $job->title]);

        return response()->json([
            'message' => 'Job description created successfully.',
            'job'     => $this->formatJob($job),
        ], 201);
    }

    // PUT /api/jobs/{job}
    public function update(Request $request, JobDescription $job)
    {
        $validated = $request->validate([
            'title'             => 'required|string|max:255',
            'description'       => 'required|string|min:20',
            'required_skills'   => 'required|array|min:1',
            'required_skills.*' => 'string|max:50',
            'required_qualification' => 'nullable|string|max:1000',
            'experience_level'  => 'required|in:junior,mid,senior',
            'experience_years'       => 'nullable|integer|min:0|max:50',
            'employment_type'   => 'required|in:full-time,part-time,contract,internship,freelance',
            'location'          => 'nullable|string|max:255',
            'status'            => 'required|in:active,closed',
        ]);

        $job->update($validated);

        AuditLogger::log('job.updated', $job, ['title' => $job->title]);

        return response()->json([
            'message' => 'Job description updated successfully.',
            'job'     => $this->formatJob($job->fresh()),
        ]);
    }

    // DELETE /api/jobs/{job}
    public function destroy(JobDescription $job)
    {
        $title = $job->title;
        $job->delete();

        AuditLogger::log('job.deleted', null, ['title' => $title]);

        return response()->json([
            'message' => "\"{$title}\" has been deleted.",
        ]);
    }

    // ── Reusable formatter ──
    private function formatJob(JobDescription $job): array
    {
        return [
            'id'               => $job->id,
            'title'            => $job->title,
            'description'      => $job->description,
            'required_skills'  => $job->required_skills,
            'required_qualification' => $job->required_qualification,
            'experience_level' => $job->experience_level,
            'experience_years' => $job->experience_years,
            'employment_type'  => $job->employment_type,
            'location'         => $job->location,
            'status'           => $job->status,
            'created_by'       => $job->creator?->name ?? 'Unknown',
            'created_at'       => $job->created_at->format('d M Y'),
            'updated_at'       => $job->updated_at->format('d M Y'),
        ];
    }
}
