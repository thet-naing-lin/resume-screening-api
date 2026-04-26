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
        $user         = auth()->user();
        $userId       = $user->id;
        $isFullAccess = $user->hasAnyRole(['admin', 'super_admin']);

        // ── Active Jobs ───────────────────────────────────────
        // Admin and HR see all active jobs
        $activeJobs = JobDescription::where('status', 'active')->count();

        // ── Total Resumes ─────────────────────────────────────
        $totalResumes = $isFullAccess
            ? Resume::count()
            : Resume::where('uploaded_by', $userId)->count();

        // ── Candidates Screened ───────────────────────────────
        $candidatesScreened = $isFullAccess
            ? Resume::where('status', 'scored')->count()
            : Resume::where('uploaded_by', $userId)
            ->where('status', 'scored')
            ->count();

        // ── Avg Score ─────────────────────────────────────────
        $avgScoreQuery = Score::whereHas('resume', function ($q) use ($userId, $isFullAccess) {
            if (!$isFullAccess) {
                $q->where('uploaded_by', $userId);
            }
        });
        $avgScore = $avgScoreQuery->avg('final_score');

        // ── Recent Activity ───────────────────────────────────
        // Admin sees all users' activity, HR sees only their own
        $activityQuery = AuditLog::orderByDesc('created_at')->limit(5);
        if (!$isFullAccess) {
            $activityQuery->where('user_id', $userId);
        }

        $recentActivity = $activityQuery->get()->map(fn($log) => [
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
