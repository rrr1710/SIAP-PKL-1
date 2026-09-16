<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;
use Inertia\Inertia;

class AuditLogController extends Controller
{
    public function index()
    {
        $logs = Activity::with('causer')->latest()->get()->map(fn ($log) => [
            'id'        => $log->id,
            'waktu'     => $log->created_at?->format('Y-m-d H:i:s') ?? '-',
            'user'      => $log->causer?->nama_lengkap ?? $log->causer?->email ?? 'System',
            'aksi'      => $log->description,
            'instansi'  => $log->causer?->instansi?->nama_instansi ?? '-',
            'ip'        => $log->properties['ip'] ?? '-',
            'perubahan' => $log->properties ?? [],
        ]);

        return Inertia::render('SuperAdmin/AuditLog/Index', [
            'logList' => $logs,
        ]);
    }
}