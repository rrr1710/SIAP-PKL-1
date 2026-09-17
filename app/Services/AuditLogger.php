<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;

/**
 * Thin wrapper around Spatie activity-log for SIAP-PKL audit trail.
 *
 * Usage:
 *   AuditLogger::log('Terima Pengajuan', $permohonan, $request);
 *   AuditLogger::log('Assign Admin', $user, $request, ['instansi_id' => 5]);
 */
class AuditLogger
{
    /**
     * Write an audit entry.
     *
     * @param  string       $actionType  Human-readable label (stored in log_name)
     * @param  Model|null   $subject     The model being acted upon
     * @param  Request|null $request     Current HTTP request (for IP)
     * @param  array        $extra       Extra properties merged into the JSON payload
     */
    public static function log(
        string $actionType,
        ?Model $subject = null,
        ?Request $request = null,
        array $extra = []
    ): void {
        try {
            $causer = $request?->user();

            $properties = array_merge([
                'ip'         => $request?->ip(),
                'instansi_id' => $causer?->id_instansi,
            ], $extra);

            $activity = activity()
                ->withProperties($properties)
                ->causedBy($causer);

            if ($subject !== null) {
                $activity = $activity->performedOn($subject);
            }

            $activity->log($actionType);

            // Spatie stores description as log_name; also set log_name for easy filtering
            // We use a second call to set log_name since the fluent API sets description
            \Spatie\Activitylog\Models\Activity::latest()->first()?->update([
                'log_name' => $actionType,
            ]);
        } catch (\Throwable $e) {
            // Never let audit logging break the main request
            logger()->error('AuditLogger failed: ' . $e->getMessage());
        }
    }
}
