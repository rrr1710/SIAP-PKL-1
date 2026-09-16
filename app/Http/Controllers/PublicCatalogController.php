<?php

namespace App\Http\Controllers;

use App\Models\Instansi;
use App\Models\PesertaMagang;
use App\Models\SubInstansi;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PublicCatalogController extends Controller
{
    public function index(Request $request)
    {
        $subInstansiList = $this->getSubInstansiWithQuota();

        $instansiList = $subInstansiList->pluck('nama_instansi')->unique()->sort()->values()->all();

        $filters = $request->only('search', 'instansi', 'status');

        $filtered = $this->applyFilters($subInstansiList, $filters);

        $stats = [
            'total_bidang' => $subInstansiList->count(),
            'total_instansi' => count($instansiList),
            'total_slot_tersisa' => $subInstansiList->sum('kuota_sisa'),
            'total_slot_terisi' => $subInstansiList->sum('kuota_terisi'),
        ];

        return Inertia::render('Katalog/Index', [
            'subInstansiList' => $filtered->values(),
            'divisions' => $filtered->values(),
            'instansiList' => $instansiList,
            'instansi' => $instansiList,
            'stats' => $stats,
            'filters' => $filters,
        ]);
    }

    public function show(SubInstansi $subInstansi)
    {
        $subInstansi->load('instansi');

        $terisi = PesertaMagang::where('id_sub_instansi', $subInstansi->id)
            ->where('status_magang', 'aktif')
            ->count();

        $sisa = max(0, $subInstansi->batas_kuota - $terisi);
        $persentase = $subInstansi->batas_kuota > 0
            ? (int) round(($terisi / $subInstansi->batas_kuota) * 100)
            : 0;

        $data = array_merge($subInstansi->toArray(), [
            'id' => $subInstansi->id,
            'nama' => $subInstansi->nama_sub_instansi,
            'nama_sub_instansi' => $subInstansi->nama_sub_instansi,
            'instansi' => $subInstansi->instansi?->nama_instansi ?? '-',
            'nama_instansi' => $subInstansi->instansi?->nama_instansi ?? '-',
            'deskripsi' => $subInstansi->deskripsi,
            'kuota_total' => $subInstansi->batas_kuota,
            'batas_kuota' => $subInstansi->batas_kuota,
            'quota' => $subInstansi->batas_kuota,
            'kuota_terisi' => $terisi,
            'terisi_total' => $terisi,
            'kuota_sisa' => $sisa,
            'sisa_total' => $sisa,
            'persentase' => $persentase,
            'status' => $this->statusKey($sisa, $subInstansi->batas_kuota),
            'positions' => [
                [
                    'id' => $subInstansi->id,
                    'nama' => $subInstansi->nama_sub_instansi,
                    'deskripsi' => $subInstansi->deskripsi ?? 'Praktik kerja lapangan pada ' . $subInstansi->nama_sub_instansi,
                    'kuota' => $subInstansi->batas_kuota,
                    'terisi' => $terisi,
                    'kualifikasi' => ['Mahasiswa / Siswa aktif', 'Memiliki komitmen dan integritas', 'Mampu bekerja secara mandiri dan tim'],
                    'jurusan' => ['Informatika', 'Sistem Informasi', 'Ilmu Komunikasi', 'Administrasi / Terkait'],
                ],
            ],
        ]);

        return Inertia::render('Katalog/Show', [
            'division' => $data,
            'subInstansi' => $data,
        ]);
    }

    private function getSubInstansiWithQuota(): \Illuminate\Support\Collection
    {
        $subInstansiAll = SubInstansi::with('instansi')->get();

        // Batch count active peserta per sub_instansi
        $counts = PesertaMagang::where('status_magang', 'aktif')
            ->selectRaw('id_sub_instansi, COUNT(*) as total')
            ->groupBy('id_sub_instansi')
            ->pluck('total', 'id_sub_instansi');

        return $subInstansiAll->map(function (SubInstansi $sub) use ($counts) {
            $terisi = (int) ($counts[$sub->id] ?? 0);
            $sisa = max(0, $sub->batas_kuota - $terisi);
            $persentase = $sub->batas_kuota > 0
                ? (int) round(($terisi / $sub->batas_kuota) * 100)
                : 0;

            return (object) [
                'id' => $sub->id,
                'slug' => (string) $sub->id,
                'nama' => $sub->nama_sub_instansi,
                'nama_sub_instansi' => $sub->nama_sub_instansi,
                'nama_instansi' => $sub->instansi?->nama_instansi ?? '-',
                'instansi' => $sub->instansi?->nama_instansi ?? '-',
                'deskripsi' => $sub->deskripsi,
                'batas_kuota' => $sub->batas_kuota,
                'kuota_total' => $sub->batas_kuota,
                'kuota_terisi' => $terisi,
                'terisi_total' => $terisi,
                'kuota_sisa' => $sisa,
                'sisa_total' => $sisa,
                'persentase' => $persentase,
                'status' => $this->statusKey($sisa, $sub->batas_kuota),
            ];
        });
    }

    private function statusKey(int $sisa, int $kuota): string
    {
        if ($sisa <= 0) return 'penuh';
        $pct = $kuota > 0 ? ($sisa / $kuota) * 100 : 0;
        if ($pct > 50) return 'tersedia';
        if ($pct >= 20) return 'menipis';
        return 'hampir-penuh';
    }

    private function applyFilters(\Illuminate\Support\Collection $list, array $filters): \Illuminate\Support\Collection
    {
        $search = trim(strtolower((string) ($filters['search'] ?? '')));
        $instansi = trim((string) ($filters['instansi'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));

        return $list->filter(function ($item) use ($search, $instansi, $status) {
            if ($instansi !== '' && $item->nama_instansi !== $instansi) return false;
            if ($status !== '') {
                if ($status === 'penuh' && !in_array($item->status, ['penuh', 'hampir-penuh'], true)) return false;
                elseif ($status !== 'penuh' && $item->status !== $status) return false;
            }
            if ($search !== '') {
                $haystack = strtolower($item->nama_sub_instansi . ' ' . $item->nama_instansi . ' ' . $item->deskripsi);
                if (!str_contains($haystack, $search)) return false;
            }
            return true;
        });
    }
}