<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PermohonanPkl;
use App\Models\PesertaMagang;
use App\Models\SubInstansi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AdminApplicationController extends Controller
{
    public function index(Request $request)
    {
        $permohonan = $this->queryFor($request->user())
            ->with(['pemohon', 'subInstansi.instansi'])
            ->latest()
            ->get()
            ->map(function (PermohonanPkl $item) {
                return array_merge($item->toArray(), [
                    'nama' => $item->pemohon?->nama_lengkap ?? '-',
                    'email' => $item->pemohon?->email ?? '-',
                    'bidang' => $item->subInstansi?->nama_sub_instansi ?? '-',
                    'asal_instansi_pendidikan' => $item->asal_instansi_pendidikan ?? '-',
                    'instansi' => $item->asal_instansi_pendidikan ?? $item->subInstansi?->instansi?->nama_instansi ?? '-',
                    'user' => [
                        'name' => $item->pemohon?->nama_lengkap ?? '-',
                        'email' => $item->pemohon?->email ?? '-',
                        'agency' => [
                            'name' => $item->subInstansi?->instansi?->nama_instansi ?? '-',
                        ],
                    ],
                    'division' => [
                        'nama' => $item->subInstansi?->nama_sub_instansi ?? '-',
                    ],
                ]);
            });

        return Inertia::render('Admin/Pengajuan/Index', [
            'activeNav' => 'admin.pengajuan',
            'pengajuan' => $permohonan,
        ]);
    }

    public function show(Request $request, PermohonanPkl $pengajuan)
    {
        $permohonan = $this->queryFor($request->user())
            ->with(['pemohon', 'subInstansi.instansi', 'anggotaPermohonan'])
            ->findOrFail($pengajuan->id);

        $members = $permohonan->anggotaPermohonan->map(fn ($a) => [
            'id' => $a->id,
            'name' => $a->nama_mahasiswa,
            'nama_mahasiswa' => $a->nama_mahasiswa,
            'nim' => $a->nim ?? '-',
            'school' => $a->sekolah,
            'sekolah' => $a->sekolah,
            'phone' => $a->no_hp,
            'no_hp' => $a->no_hp,
        ]);

        $pengajuanData = array_merge($permohonan->toArray(), [
            'members' => $members,
            'document_path' => $permohonan->berkas_permohonan,
            'catatan_revisi' => $permohonan->catatan_admin,
            'asal_instansi_pendidikan' => $permohonan->asal_instansi_pendidikan ?? '-',
            'user' => [
                'name' => $permohonan->pemohon?->nama_lengkap ?? '-',
                'email' => $permohonan->pemohon?->email ?? '-',
                'agency' => [
                    'name' => $permohonan->asal_instansi_pendidikan ?? $permohonan->subInstansi?->instansi?->nama_instansi ?? '-',
                ],
            ],
            'division' => [
                'nama' => $permohonan->subInstansi?->nama_sub_instansi ?? '-',
                'instansi' => $permohonan->subInstansi?->instansi?->nama_instansi ?? '-',
            ],
        ]);

        return Inertia::render('Admin/Pengajuan/Show', [
            'activeNav' => 'admin.pengajuan',
            'pengajuan' => $pengajuanData,
        ]);
    }

    public function updateStatus(Request $request, PermohonanPkl $pengajuan)
    {
        $permohonan = $this->queryFor($request->user())
            ->with(['subInstansi', 'anggotaPermohonan'])
            ->findOrFail($pengajuan->id);

        abort_unless(in_array($permohonan->status, ['menunggu', 'pending']), 422, 'Pengajuan ini sudah diproses.');

        // Normalize status & notes
        $statusInput = $request->input('status');
        $statusMap = [
            'accepted' => 'diterima',
            'rejected' => 'ditolak',
            'revision' => 'revisi',
            'diterima' => 'diterima',
            'ditolak' => 'ditolak',
            'revisi' => 'revisi',
        ];
        $normalizedStatus = $statusMap[$statusInput] ?? $statusInput;

        $catatanAdmin = $request->input('catatan_admin') ?? $request->input('catatan_revisi');

        $data = [
            'status' => $normalizedStatus,
            'catatan_admin' => $catatanAdmin,
        ];

        DB::transaction(function () use ($permohonan, $data) {
            $permohonan->update([
                'status' => $data['status'],
                'catatan_admin' => $data['catatan_admin'] ?? null,
            ]);

            // When accepted, create PesertaMagang records for each anggota
            if ($data['status'] === 'diterima') {
                foreach ($permohonan->anggotaPermohonan as $anggota) {
                    PesertaMagang::create([
                        'id_sub_instansi' => $permohonan->id_sub_instansi,
                        'id_permohonan' => $permohonan->id,
                        'nama_peserta' => $anggota->nama_mahasiswa,
                        'nim' => $anggota->nim,
                        'sekolah' => $anggota->sekolah,
                        'no_hp' => $anggota->no_hp,
                        'tanggal_mulai' => $permohonan->tanggal_mulai,
                        'tanggal_selesai' => $permohonan->tanggal_selesai,
                        'status_magang' => 'aktif',
                    ]);
                }
            }
        });

        return back()->with('success', 'Status pengajuan berhasil diperbarui.');
    }

    public function completeParticipant(Request $request, PesertaMagang $peserta)
    {
        $sub = SubInstansi::where('id_instansi', $request->user()->id_instansi)
            ->findOrFail($peserta->id_sub_instansi);

        abort_unless($peserta->status_magang === 'aktif', 422, 'Peserta sudah tidak aktif.');

        $peserta->update(['status_magang' => 'selesai']);

        return back()->with('success', 'Status peserta berhasil diubah menjadi Selesai.');
    }

    public function document(Request $request, PermohonanPkl $pengajuan)
    {
        $permohonan = $this->queryFor($request->user())->findOrFail($pengajuan->id);
        $filePath = $permohonan->berkas_permohonan;

        abort_unless($filePath, 404, 'Dokumen berkas tidak ditemukan.');

        if (Storage::disk('local')->exists($filePath)) {
            return response()->file(Storage::disk('local')->path($filePath), [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . basename($filePath) . '"',
            ]);
        }

        if (Storage::disk('public')->exists($filePath)) {
            return response()->file(Storage::disk('public')->path($filePath), [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . basename($filePath) . '"',
            ]);
        }

        abort(404, 'File dokumen tidak ditemukan pada penyimpanan server.');
    }

    public function participants(Request $request)
    {
        $user = $request->user();

        $peserta = PesertaMagang::with('subInstansi.instansi')
            ->whereHas('subInstansi', function ($q) use ($user) {
                $q->where('id_instansi', $user->id_instansi);
            })
            ->latest()
            ->get()
            ->map(fn (PesertaMagang $p) => [
                'id' => $p->id,
                'nama' => $p->nama_peserta,
                'nim' => $p->nim ?? '-',
                'sekolah' => $p->sekolah,
                'bidang' => $p->subInstansi?->nama_sub_instansi ?? '-',
                'instansi' => $p->subInstansi?->instansi?->nama_instansi ?? '-',
                'tanggal_mulai' => $p->tanggal_mulai?->toDateString(),
                'tanggal_selesai' => $p->tanggal_selesai?->toDateString(),
                'status_magang' => $p->status_magang,
                'status' => $p->status_magang,
            ]);

        $newThisMonth = PesertaMagang::whereHas('subInstansi', fn ($q) => $q->where('id_instansi', $user->id_instansi))
            ->whereMonth('created_at', now()->month)
            ->count();

        return Inertia::render('Admin/Peserta/Index', [
            'activeNav' => 'admin.peserta',
            'peserta' => $peserta,
            'stats' => [
                'total_aktif' => $peserta->where('status_magang', 'aktif')->count(),
                'selesai' => $peserta->where('status_magang', 'selesai')->count(),
                'baru_bulan_ini' => $newThisMonth,
            ],
        ]);
    }

    public function walkInCreate(Request $request)
    {
        $subInstansiList = SubInstansi::where('id_instansi', $request->user()->id_instansi)
            ->orderBy('nama_sub_instansi')
            ->get()
            ->map(fn ($sub) => [
                'id' => $sub->id,
                'nama' => $sub->nama_sub_instansi,
                'nama_sub_instansi' => $sub->nama_sub_instansi,
                'quota' => $sub->batas_kuota,
                'batas_kuota' => $sub->batas_kuota,
            ]);

        return Inertia::render('Admin/Peserta/WalkIn', [
            'activeNav' => 'admin.peserta.walk-in',
            'subInstansiList' => $subInstansiList,
            'divisions' => $subInstansiList,
        ]);
    }

    public function walkInStore(Request $request)
    {
        $input = $request->all();
        if (!isset($input['nama_peserta']) && isset($input['name'])) {
            $input['nama_peserta'] = $input['name'];
        }
        if (!isset($input['sekolah']) && isset($input['school'])) {
            $input['sekolah'] = $input['school'];
        }
        if (!isset($input['no_hp']) && isset($input['phone'])) {
            $input['no_hp'] = $input['phone'];
        }
        if (!isset($input['id_sub_instansi']) && isset($input['division_id'])) {
            $input['id_sub_instansi'] = $input['division_id'];
        }
        if (!isset($input['tanggal_mulai']) && isset($input['start_date'])) {
            $input['tanggal_mulai'] = $input['start_date'];
        }
        if (!isset($input['tanggal_selesai']) && isset($input['end_date'])) {
            $input['tanggal_selesai'] = $input['end_date'];
        }
        $request->merge($input);

        $validator = Validator::make($request->all(), [
            'nama_peserta' => ['required', 'string', 'max:255'],
            'nim' => ['nullable', 'string', 'max:50'],
            'sekolah' => ['required', 'string', 'max:255'],
            'no_hp' => ['required', 'string', 'max:20'],
            'id_sub_instansi' => ['required', 'integer', 'exists:sub_instansi,id'],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            if ($errors->has('nama_peserta')) $errors->add('name', $errors->first('nama_peserta'));
            if ($errors->has('sekolah')) $errors->add('school', $errors->first('sekolah'));
            if ($errors->has('no_hp')) $errors->add('phone', $errors->first('no_hp'));
            if ($errors->has('id_sub_instansi')) $errors->add('division_id', $errors->first('id_sub_instansi'));
            if ($errors->has('tanggal_mulai')) $errors->add('start_date', $errors->first('tanggal_mulai'));
            if ($errors->has('tanggal_selesai')) $errors->add('end_date', $errors->first('tanggal_selesai'));
            throw new ValidationException($validator);
        }

        $data = $validator->validated();

        $sub = SubInstansi::where('id_instansi', $request->user()->id_instansi)
            ->findOrFail($data['id_sub_instansi']);

        // Admin walk-in registration bypasses capacity check intentionally
        PesertaMagang::create([
            'id_sub_instansi' => $sub->id,
            'id_permohonan' => null,
            'nama_peserta' => $data['nama_peserta'],
            'nim' => $data['nim'] ?? null,
            'sekolah' => $data['sekolah'],
            'no_hp' => $data['no_hp'],
            'tanggal_mulai' => $data['tanggal_mulai'],
            'tanggal_selesai' => $data['tanggal_selesai'],
            'status_magang' => 'aktif',
        ]);

        return to_route('admin.peserta.index')->with('success', 'Peserta walk-in berhasil diregistrasikan.');
    }

    private function queryFor($user)
    {
        return PermohonanPkl::query()
            ->whereHas('subInstansi', fn ($q) => $q->where('id_instansi', $user->id_instansi));
    }
}