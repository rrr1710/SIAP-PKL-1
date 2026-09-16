<?php

namespace App\Http\Controllers;

use App\Models\PermohonanPkl;
use App\Models\SubInstansi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PengajuanPklController extends Controller
{
    public function index(Request $request)
    {
        $activePermohonan = PermohonanPkl::where('id_pemohon', $request->user()->id)
            ->whereIn('status', ['menunggu', 'diterima'])
            ->with('subInstansi.instansi')
            ->latest()
            ->first();

        $subInstansiList = SubInstansi::with('instansi')
            ->orderBy('nama_sub_instansi')
            ->get()
            ->map(function (SubInstansi $sub) {
                $occupied = \App\Models\PesertaMagang::where('id_sub_instansi', $sub->id)
                    ->where('status_magang', 'aktif')
                    ->count();

                return [
                    'id' => $sub->id,
                    'nama' => $sub->nama_sub_instansi,
                    'nama_sub_instansi' => $sub->nama_sub_instansi,
                    'nama_instansi' => $sub->instansi?->nama_instansi ?? '-',
                    'instansi' => $sub->instansi?->nama_instansi ?? '-',
                    'deskripsi' => $sub->deskripsi,
                    'batas_kuota' => $sub->batas_kuota,
                    'kuota' => $sub->batas_kuota,
                    'kuota_sisa' => max(0, $sub->batas_kuota - $occupied),
                ];
            });

        $applicationData = $activePermohonan ? [
            'id' => $activePermohonan->id,
            'status' => $activePermohonan->status,
            'nama_sub_instansi' => $activePermohonan->subInstansi?->nama_sub_instansi ?? '-',
            'division_nama' => $activePermohonan->subInstansi?->nama_sub_instansi ?? '-',
            'division_name' => $activePermohonan->subInstansi?->nama_sub_instansi ?? '-',
            'nama_instansi' => $activePermohonan->subInstansi?->instansi?->nama_instansi ?? '-',
            'instansi' => $activePermohonan->subInstansi?->instansi?->nama_instansi ?? '-',
            'agency_name' => $activePermohonan->subInstansi?->instansi?->nama_instansi ?? '-',
            'created_at' => $activePermohonan->created_at?->format('d M Y'),
        ] : null;

        return Inertia::render('Pengajuan/Index', [
            'activeNav' => 'pengajuan',
            'subInstansiList' => $subInstansiList,
            'divisions' => $subInstansiList,
            'hasActivePermohonan' => $activePermohonan !== null,
            'hasActiveApplication' => $activePermohonan !== null,
            'activePermohonan' => $applicationData,
            'activeApplication' => $applicationData,
        ]);
    }

    public function store(Request $request)
    {
        $existingActive = PermohonanPkl::where('id_pemohon', $request->user()->id)
            ->whereIn('status', ['menunggu', 'diterima'])
            ->first();

        if ($existingActive) {
            return back()->withErrors([
                'message' => 'Anda sudah memiliki permohonan aktif, silakan selesaikan atau tunggu prosesnya sebelum mengajukan permohonan baru.',
            ]);
        }

        // Normalize incoming request keys for seamless frontend compatibility
        $input = $request->all();
        if (!isset($input['id_sub_instansi']) && isset($input['division_id'])) {
            $input['id_sub_instansi'] = $input['division_id'];
        }
        if (!isset($input['division_id']) && isset($input['id_sub_instansi'])) {
            $input['division_id'] = $input['id_sub_instansi'];
        }
        if (!isset($input['tanggal_mulai']) && isset($input['start_date'])) {
            $input['tanggal_mulai'] = $input['start_date'];
        }
        if (!isset($input['tanggal_selesai']) && isset($input['end_date'])) {
            $input['tanggal_selesai'] = $input['end_date'];
        }
        if (isset($input['ketua'])) {
            if (!isset($input['ketua']['nama_mahasiswa']) && isset($input['ketua']['name'])) {
                $input['ketua']['nama_mahasiswa'] = $input['ketua']['name'];
            }
            if (!isset($input['ketua']['name']) && isset($input['ketua']['nama_mahasiswa'])) {
                $input['ketua']['name'] = $input['ketua']['nama_mahasiswa'];
            }
            if (!isset($input['ketua']['sekolah']) && isset($input['ketua']['school'])) {
                $input['ketua']['sekolah'] = $input['ketua']['school'];
            }
            if (!isset($input['ketua']['school']) && isset($input['ketua']['sekolah'])) {
                $input['ketua']['school'] = $input['ketua']['sekolah'];
            }
            if (!isset($input['ketua']['no_hp']) && isset($input['ketua']['phone'])) {
                $input['ketua']['no_hp'] = $input['ketua']['phone'];
            }
            if (!isset($input['ketua']['phone']) && isset($input['ketua']['no_hp'])) {
                $input['ketua']['phone'] = $input['ketua']['no_hp'];
            }
        }
        if (($input['tipe'] ?? 'individu') === 'kelompok') {
            if (!isset($input['anggota']) && isset($input['members'])) {
                $input['anggota'] = array_map(function ($m) {
                    return [
                        'nama_mahasiswa' => $m['nama_mahasiswa'] ?? $m['name'] ?? '',
                        'name' => $m['name'] ?? $m['nama_mahasiswa'] ?? '',
                        'nim' => $m['nim'] ?? null,
                        'sekolah' => $m['sekolah'] ?? $m['school'] ?? '',
                        'school' => $m['school'] ?? $m['sekolah'] ?? '',
                        'no_hp' => $m['no_hp'] ?? $m['phone'] ?? '',
                        'phone' => $m['phone'] ?? $m['no_hp'] ?? '',
                    ];
                }, (array) $input['members']);
            }
        } else {
            // Unset anggota and members when individu so required_if and min:1 don't trigger on empty array
            unset($input['anggota'], $input['members']);
        }
        $request->merge($input);

        $rules = [
            'id_sub_instansi' => ['required', 'integer', 'exists:sub_instansi,id'],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
            'tipe' => ['required', 'in:individu,kelompok'],
            'ketua.nama_mahasiswa' => ['required', 'string', 'max:255'],
            'ketua.nim' => ['nullable', 'string', 'max:50'],
            'ketua.sekolah' => ['required', 'string', 'max:255'],
            'ketua.no_hp' => ['required', 'string', 'max:20'],
            'anggota' => ['required_if:tipe,kelompok', 'array', 'min:1'],
            'anggota.*.nama_mahasiswa' => ['required_if:tipe,kelompok', 'string', 'max:255'],
            'anggota.*.nim' => ['nullable', 'string', 'max:50'],
            'anggota.*.sekolah' => ['required_if:tipe,kelompok', 'string', 'max:255'],
            'anggota.*.no_hp' => ['required_if:tipe,kelompok', 'string', 'max:20'],
        ];

        if ($request->hasFile('berkas_permohonan')) {
            $rules['berkas_permohonan'] = ['required', 'file', 'mimes:pdf', 'max:5120'];
        } else {
            $rules['document'] = ['required', 'file', 'mimes:pdf', 'max:5120'];
        }

        $data = $request->validate($rules);

        $idSubInstansi = $data['id_sub_instansi'];
        $tanggalMulai = $data['tanggal_mulai'];
        $tanggalSelesai = $data['tanggal_selesai'];
        $requestedSize = 1 + count($data['anggota'] ?? []);

        DB::transaction(function () use ($request, $data, $idSubInstansi, $tanggalMulai, $tanggalSelesai, $requestedSize) {
            // Anti-Overbooking: Pessimistic lock on active user application to prevent race condition
            $activeSubmission = PermohonanPkl::where('id_pemohon', $request->user()->id)
                ->whereIn('status', ['menunggu', 'diterima'])
                ->lockForUpdate()
                ->first();

            if ($activeSubmission) {
                throw ValidationException::withMessages([
                    'id_sub_instansi' => 'Anda masih memiliki permohonan PKL yang aktif atau menunggu verifikasi.',
                    'division_id' => 'Anda masih memiliki permohonan PKL yang aktif atau menunggu verifikasi.',
                ]);
            }

            // Pessimistic lock on SubInstansi division row
            $sub = SubInstansi::where('id', $idSubInstansi)->lockForUpdate()->firstOrFail();

            // Occupancy count leveraging idx_kuota_peserta composite index
            $occupied = \App\Models\PesertaMagang::where('id_sub_instansi', $idSubInstansi)
                ->where('status_magang', 'aktif')
                ->where('tanggal_mulai', '<=', $tanggalSelesai)
                ->where('tanggal_selesai', '>=', $tanggalMulai)
                ->count();

            $availableSlots = $sub->batas_kuota - $occupied;

            if ($requestedSize > $availableSlots) {
                throw ValidationException::withMessages([
                    'id_sub_instansi' => 'Kuota tidak mencukupi untuk periode tersebut.',
                    'division_id' => 'Kuota tidak mencukupi untuk periode tersebut.',
                ]);
            }

            $file = $request->file('berkas_permohonan') ?? $request->file('document');
            if (!$file) {
                throw ValidationException::withMessages([
                    'document' => 'File berkas permohonan tidak ditemukan.',
                    'berkas_permohonan' => 'File berkas permohonan tidak ditemukan.',
                ]);
            }
            $nama = sprintf(
                'berkas_pkl_user%d_%s_%s.pdf',
                $request->user()->id,
                now()->format('YmdHis'),
                Str::random(6)
            );
            // Store in isolated private storage
            $berkasPath = $file->storeAs('proposals', $nama, 'local');

            $permohonan = PermohonanPkl::create([
                'id_pemohon' => $request->user()->id,
                'id_sub_instansi' => $idSubInstansi,
                'jenis_pengajuan' => $data['tipe'],
                'status' => 'menunggu',
                'sekolah' => $data['ketua']['sekolah'],
                'no_hp' => $data['ketua']['no_hp'],
                'tanggal_mulai' => $tanggalMulai,
                'tanggal_selesai' => $tanggalSelesai,
                'berkas_permohonan' => $berkasPath,
                'catatan_admin' => null,
            ]);

            // Store ketua as first anggota
            $permohonan->anggotaPermohonan()->create([
                'nama_mahasiswa' => $data['ketua']['nama_mahasiswa'],
                'nim' => $data['ketua']['nim'] ?? null,
                'sekolah' => $data['ketua']['sekolah'],
                'no_hp' => $data['ketua']['no_hp'],
            ]);

            foreach ($data['anggota'] ?? [] as $anggota) {
                $permohonan->anggotaPermohonan()->create([
                    'nama_mahasiswa' => $anggota['nama_mahasiswa'],
                    'nim' => $anggota['nim'] ?? null,
                    'sekolah' => $anggota['sekolah'],
                    'no_hp' => $anggota['no_hp'],
                ]);
            }
        });

        return redirect()->route('pengajuan.index')->with('success', 'Pengajuan PKL berhasil dikirim dan menunggu verifikasi.');
    }

    public function reupload(Request $request, PermohonanPkl $permohonan)
    {
        abort_unless(
            $permohonan->id_pemohon === $request->user()->id && in_array($permohonan->status, ['menunggu', 'revisi']),
            403
        );

        if (!$request->hasFile('berkas_permohonan') && $request->hasFile('document')) {
            $request->files->set('berkas_permohonan', $request->file('document'));
        }

        $request->validate([
            'berkas_permohonan' => ['required', 'file', 'mimes:pdf', 'max:5120'],
        ]);

        if ($permohonan->berkas_permohonan) {
            if (Storage::disk('local')->exists($permohonan->berkas_permohonan)) {
                Storage::disk('local')->delete($permohonan->berkas_permohonan);
            } elseif (Storage::disk('public')->exists($permohonan->berkas_permohonan)) {
                Storage::disk('public')->delete($permohonan->berkas_permohonan);
            }
        }

        $file = $request->file('berkas_permohonan');
        $nama = sprintf(
            'berkas_pkl_user%d_%s_%s.pdf',
            $request->user()->id,
            now()->format('YmdHis'),
            Str::random(6)
        );
        $berkasPath = $file->storeAs('proposals', $nama, 'local');

        $permohonan->update([
            'berkas_permohonan' => $berkasPath,
            'status' => 'menunggu',
        ]);

        return redirect()->route('riwayat.index')->with('success', 'Berkas revisi berhasil diunggah ulang.');
    }

    public function checkAvailability(Request $request)
    {
        $input = $request->all();
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

        $request->validate([
            'id_sub_instansi' => ['required', 'integer', 'exists:sub_instansi,id'],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
        ]);

        $idSubInstansi = $request->input('id_sub_instansi');
        $tanggalMulai = $request->input('tanggal_mulai');
        $tanggalSelesai = $request->input('tanggal_selesai');

        $sub = SubInstansi::findOrFail($idSubInstansi);

        $occupied = \App\Models\PesertaMagang::where('id_sub_instansi', $idSubInstansi)
            ->where('status_magang', 'aktif')
            ->where('tanggal_mulai', '<=', $tanggalSelesai)
            ->where('tanggal_selesai', '>=', $tanggalMulai)
            ->count();

        $availableSlots = $sub->batas_kuota - $occupied;

        if ($availableSlots >= 1) {
            return response()->json(['available' => true, 'sisa' => $availableSlots]);
        }

        $earliestEnd = \App\Models\PesertaMagang::where('id_sub_instansi', $idSubInstansi)
            ->where('status_magang', 'aktif')
            ->where('tanggal_selesai', '>=', $tanggalMulai)
            ->min('tanggal_selesai');

        $nextAvailableDate = $earliestEnd
            ? \Carbon\Carbon::parse($earliestEnd)->addDay()->format('Y-m-d')
            : $tanggalMulai;

        return response()->json([
            'available' => false,
            'message' => 'Kuota penuh untuk periode ini',
            'next_available_date' => $nextAvailableDate,
        ]);
    }

    public function downloadSignedDocument(Request $request, PermohonanPkl $permohonan)
    {
        $user = $request->user();
        $isOwner = $permohonan->id_pemohon === $user?->id;
        $isAgencyAdmin = $user && (
            $user->hasRole(['Admin Instansi', 'Super Admin']) ||
            ($user->id_instansi && $permohonan->subInstansi?->id_instansi === $user->id_instansi)
        );

        abort_unless($isOwner || $isAgencyAdmin, 403, 'Akses dokumen tidak diizinkan.');

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
}