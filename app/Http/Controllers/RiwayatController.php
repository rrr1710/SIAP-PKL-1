<?php

namespace App\Http\Controllers;

use App\Models\PermohonanPkl;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RiwayatController extends Controller
{
    public function index(Request $request)
    {
        $riwayat = PermohonanPkl::with('subInstansi.instansi')
            ->where('id_pemohon', $request->user()->id)
            ->latest()
            ->get()
            ->map(function (PermohonanPkl $item) {
                return [
                    'id' => $item->id,
                    'status' => $item->status,
                    'created_at' => $item->created_at?->format('d M Y'),
                    'bidang' => $item->subInstansi?->nama_sub_instansi ?? 'Data tidak tersedia',
                    'posisi' => $item->subInstansi?->nama_sub_instansi ?? 'Data tidak tersedia',
                    'instansi' => $item->subInstansi?->instansi?->nama_instansi ?? 'Data tidak tersedia',
                    'tanggal_mulai' => $item->tanggal_mulai?->toDateString(),
                    'tanggal_selesai' => $item->tanggal_selesai?->toDateString(),
                    'catatan_admin' => $item->catatan_admin,
                    'catatan_revisi' => $item->catatan_admin,
                    'berkas_permohonan' => $item->berkas_permohonan,
                    'download_url' => $item->berkas_permohonan
                        ? \Illuminate\Support\Facades\URL::temporarySignedRoute('proposal.download', now()->addMinutes(15), ['permohonan' => $item->id])
                        : null,
                ];
            });

        return Inertia::render('Riwayat/Index', [
            'activeNav' => 'riwayat',
            'riwayat' => $riwayat,
        ]);
    }
}
