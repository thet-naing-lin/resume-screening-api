<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\JobDescription;
use App\Models\Resume;
use App\Models\User;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * US-019: Admin views all audit logs with filters + pagination
     */
    public function index(Request $request)
    {
        $request->validate([
            'user_id'    => 'nullable|exists:users,id',
            'action'     => 'nullable|string|max:100',
            'date_from'  => 'nullable|date',
            'date_to'    => 'nullable|date',
            'per_page'   => 'nullable|integer|min:5|max:100',
        ]);

        $query = AuditLog::with('user')
            ->orderByDesc('created_at');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('action')) {
            $query->where('action', 'like', '%' . $request->action . '%');
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->paginate($request->input('per_page', 20));

        // Resolve target names for each log
        $logs->getCollection()->transform(function ($log) {
            $log->target_label = $this->resolveTargetLabel(
                $log->target_type,
                $log->target_id
            );
            return $log;
        });

        return response()->json($logs);
    }

    /**
     * Resolve a human-readable label for the target
     */
    private function resolveTargetLabel(?string $type, ?int $id): ?string
    {
        if (!$type || !$id) return null;

        return match ($type) {
            'User'           => User::find($id)?->name ?? "Deleted User #{$id}",
            'Resume'         => Resume::find($id)?->original_filename ?? "Resume #{$id}",
            'JobDescription' => JobDescription::find($id)?->title ?? "Job #{$id}",
            default          => "{$type} #{$id}",
        };
    }
}
