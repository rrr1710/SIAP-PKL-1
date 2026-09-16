<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PermohonanPkl;
use App\Models\PesertaMagang;
use App\Models\SubInstansi;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminDashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $idInstansi = $request->user()->id_instansi;

        $subInstansiQuery = SubInstansi::where('id_instansi', $idInstansi);
        $subInstansiIds = (clone $subInstansiQuery)->pluck('id');

        $permohonanQuery = PermohonanPkl::whereIn('id_sub_instansi', $subInstansiIds);

        return Inertia::render('Admin/Dashboard', [
            'activeNav' => 'admin.dashboard',
            'stats' => [
                'total_bidang' => (clone $subInstansiQuery)->count(),
                'pengajuan_baru' => (clone $permohonanQuery)->where('status', 'menunggu')->count(),
                'menunggu_verifikasi' => (clone $permohonanQuery)->where('status', 'menunggu')->count(),
                'peserta_aktif' => PesertaMagang::whereIn('id_sub_instansi', $subInstansiIds)
                    ->where('status_magang', 'aktif')
                    ->count(),
            ],
            'pengajuanTerbaru' => (clone $permohonanQuery)
                ->with(['pemohon', 'subInstansi', 'anggotaPermohonan'])
                ->latest()
                ->limit(5)
                ->get()
                ->map(function (PermohonanPkl $p) {
                    $nama = $p->pemohon?->nama_lengkap 
                        ?? $p->anggotaPermohonan->first()?->nama_mahasiswa 
                        ?? '-';

                    return [
                        'id' => $p->id,
                        'nama' => $nama,
                        'nama_pemohon' => $nama,
                        'bidang' => $p->subInstansi?->nama_sub_instansi ?? '-',
                        'posisi' => $p->subInstansi?->nama_sub_instansi ?? '-',
                        'status' => $p->status,
                        'created_at' => $p->created_at?->format('d M Y'),
                    ];
                }),
            'bidangAktif' => (clone $subInstansiQuery)
                ->withCount([
                    'pesertaMagang as terisi' => fn ($q) => $q->where('status_magang', 'aktif'),
                ])
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn (SubInstansi $s) => [
                    'id' => $s->id,
                    'nama' => $s->nama_sub_instansi,
                    'nama_sub_instansi' => $s->nama_sub_instansi,
                    'terisi' => (int) $s->terisi,
                    'kuota' => (int) $s->batas_kuota,
                    'batas_kuota' => (int) $s->batas_kuota,
                ]),
        ]);
    }
}