<?php

namespace App\Http\Controllers;

use App\Models\PermohonanPkl;
use App\Models\SubInstansi;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $permohonanAktif = PermohonanPkl::with('subInstansi.instansi')
            ->where('id_pemohon', $userId)
            ->whereIn('status', ['menunggu', 'diterima'])
            ->latest()
            ->first();

        return Inertia::render('Home', [
            'activeNav' => 'home',
            'stats' => [
                'lowongan_tersedia' => SubInstansi::count(),
                'pendaftaran' => PermohonanPkl::where('id_pemohon', $userId)->count(),
                'menunggu_verifikasi' => PermohonanPkl::where('id_pemohon', $userId)
                    ->where('status', 'menunggu')->count(),
                'diterima' => PermohonanPkl::where('id_pemohon', $userId)
                    ->where('status', 'diterima')->count(),
            ],
            'pendaftaranAktif' => $permohonanAktif ? [
                'judul' => $permohonanAktif->subInstansi?->nama_sub_instansi ?? 'Pengajuan PKL',
                'instansi' => $permohonanAktif->subInstansi?->instansi?->nama_instansi ?? '-',
                'tanggal' => $permohonanAktif->created_at?->translatedFormat('d M Y') ?? '-',
                'status' => $permohonanAktif->status,
            ] : null,
            'pengumuman' => [],
        ]);
    }
}
