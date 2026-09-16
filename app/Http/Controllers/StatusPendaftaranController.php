<?php

namespace App\Http\Controllers;

use App\Models\PermohonanPkl;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StatusPendaftaranController extends Controller
{
    public function index(Request $request)
    {
        $pendaftaran = PermohonanPkl::with(['subInstansi.instansi', 'anggotaPermohonan'])
            ->where('id_pemohon', $request->user()->id)
            ->latest()
            ->first();

        $pendaftaranData = null;
        if ($pendaftaran) {
            $pendaftaranData = array_merge($pendaftaran->toArray(), [
                'bidang' => $pendaftaran->subInstansi?->nama_sub_instansi ?? '-',
                'instansi' => $pendaftaran->subInstansi?->instansi?->nama_instansi ?? '-',
                'catatan_revisi' => $pendaftaran->catatan_admin,
                'division' => [
                    'nama' => $pendaftaran->subInstansi?->nama_sub_instansi ?? '-',
                    'instansi' => $pendaftaran->subInstansi?->instansi?->nama_instansi ?? '-',
                ],
                'position' => [
                    'nama' => $pendaftaran->subInstansi?->nama_sub_instansi ?? '-',
                    'division' => [
                        'instansi' => $pendaftaran->subInstansi?->instansi?->nama_instansi ?? '-',
                    ],
                ],
                'download_url' => $pendaftaran->berkas_permohonan
                    ? \Illuminate\Support\Facades\URL::temporarySignedRoute('proposal.download', now()->addMinutes(15), ['permohonan' => $pendaftaran->id])
                    : null,
            ]);
        }

        return Inertia::render('Status/Index', [
            'activeNav' => 'status',
            'pendaftaran' => $pendaftaranData,
        ]);
    }
}
