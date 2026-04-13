<?php

namespace App\Services;

use App\Models\AuditLog;

class AuditLogService
{
    /**
     * Record an action in the audit_logs table.
     *
     * @param int    $userId   — who did it
     * @param string $action   — what they did (e.g. 'login', 'upload_resume')
     * @param string $entity   — which model was affected (e.g. 'User', 'Resume')
     * @param int    $entityId — the ID of the affected record
     */
    public static function log(int $userId, string $action, string $entity, int $entityId): void
    {
        AuditLog::create([
            'user_id'   => $userId,
            'action'    => $action,
            'entity'    => $entity,
            'entity_id' => $entityId,
            'timestamp' => now(),
        ]);
    }
}
