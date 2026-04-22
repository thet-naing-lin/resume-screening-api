<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    //  “one line to record who did what to which model, with extra details.”

    /**
     * Log an action.
     *
     * @param string $action     e.g. "resume.uploaded", "candidate.shortlisted"
     * @param Model|null $target The Eloquent model being acted on
     * @param array $metadata    Any extra context to store
     */
    public static function log(
        string $action,
        ?Model $target  = null,
        array  $metadata = []
    ): void {
        AuditLog::create([
            'user_id'     => auth()->id(),
            'action'      => $action,
            'target_type' => $target ? class_basename($target) : null,
            'target_id'   => $target?->id,
            'metadata'    => empty($metadata) ? null : $metadata,
            'ip_address'  => request()->ip(),
        ]);
    }
}
