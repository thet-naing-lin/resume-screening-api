<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobDescription;
use App\Models\Resume;
use App\Models\Score;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function stats()
    {
        $userId = auth()->id();

        $activeJobs = JobDescription::where('status', 'active')->count();

        $totalResumes = Resume::where('uploaded_by', $userId)->count();

        $candidatesScreened = Resume::where('uploaded_by', $userId)
            ->where('status', 'scored')
            ->count();

        $avgScore = Score::whereHas('resume', function ($q) use ($userId) {
            $q->where('uploaded_by', $userId);
        })
            ->avg('final_score');

        // Last 5 audit log entries for this user
        $recentActivity = AuditLog::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn($log) => [
                'action'     => $log->action,
                'metadata'   => $log->metadata,
                'created_at' => $log->created_at->diffForHumans(),
            ]);

        return response()->json([
            'active_jobs'         => $activeJobs,
            'total_resumes'       => $totalResumes,
            'candidates_screened' => $candidatesScreened,
            'avg_score'           => $avgScore ? round($avgScore, 1) : null,
            'recent_activity'     => $recentActivity,
        ]);
    }
}
