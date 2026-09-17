<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;
use Inertia\Inertia;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = Activity::with('causer.instansi')->latest();

        if ($request->filled('aksiFilter')) {
            $query->where('log_name', $request->aksiFilter);
        }

        if ($request->filled('instansiFilter')) {
            $query->whereHas('causer.instansi', function ($q) use ($request) {
                $q->where('nama_instansi', $request->instansiFilter);
            });
        }

        if ($request->filled('tanggalMulai')) {
            $query->whereDate('created_at', '>=', $request->tanggalMulai);
        }
        
        if ($request->filled('tanggalSelesai')) {
            $query->whereDate('created_at', '<=', $request->tanggalSelesai);
        }

        $logs = $query->paginate(50)->through(fn ($log) => [
            'id'        => $log->id,
            'waktu'     => $log->created_at?->format('Y-m-d H:i:s') ?? '-',
            'user'      => $log->causer?->nama_lengkap ?? $log->causer?->email ?? 'System',
            'aksi'      => $log->log_name ?? $log->description,
            'instansi'  => $log->causer?->instansi?->nama_instansi ?? '-',
            'ip'        => $log->properties['ip'] ?? '-',
            'perubahan' => $log->properties->except(['ip', 'instansi_id']) ?? [],
        ]);

        $instansiOptions = \App\Models\Instansi::orderBy('nama_instansi')->pluck('nama_instansi');
        $aksiOptions = Activity::select('log_name')->distinct()->whereNotNull('log_name')->pluck('log_name');

        return Inertia::render('SuperAdmin/AuditLog/Index', [
            'logList' => $logs,
            'instansiOptions' => $instansiOptions,
            'aksiOptions' => $aksiOptions,
            'filters' => $request->only(['instansiFilter', 'aksiFilter', 'tanggalMulai', 'tanggalSelesai']),
        ]);
    }
}