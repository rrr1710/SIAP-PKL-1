<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PesertaMagang;
use App\Models\SubInstansi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AdminBidangController extends Controller
{
    public function index(Request $request)
    {
        $subInstansiList = $this->queryFor($request->user())
            ->withCount([
                'pesertaMagang as peserta_aktif_count' => fn ($q) => $q->where('status_magang', 'aktif'),
            ])
            ->latest()
            ->get()
            ->map(fn (SubInstansi $sub) => $this->withQuota($sub));

        return Inertia::render('Admin/Bidang/Index', [
            'activeNav' => 'admin.bidang',
            'subInstansiList' => $subInstansiList,
            'divisions' => $subInstansiList,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Bidang/Create', ['activeNav' => 'admin.bidang']);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($request, $data) {
            SubInstansi::create([
                'id_instansi' => $request->user()->id_instansi,
                'nama_sub_instansi' => $data['nama_sub_instansi'],
                'deskripsi' => $data['deskripsi'],
                'batas_kuota' => $data['batas_kuota'],
            ]);
        });

        return to_route('admin.bidang.index')->with('success', 'Bidang berhasil dibuat.');
    }

    public function show(Request $request, SubInstansi $bidang)
    {
        $this->authorizeSubInstansi($bidang, $request->user());

        $bidang->loadCount([
            'pesertaMagang as peserta_aktif_count' => fn ($q) => $q->where('status_magang', 'aktif'),
        ]);

        return Inertia::render('Admin/Bidang/Show', [
            'activeNav' => 'admin.bidang',
            'bidang' => $this->withQuota($bidang),
        ]);
    }

    public function edit(Request $request, SubInstansi $bidang)
    {
        $this->authorizeSubInstansi($bidang, $request->user());

        $bidangData = array_merge($bidang->toArray(), [
            'nama' => $bidang->nama_sub_instansi,
            'nama_sub_instansi' => $bidang->nama_sub_instansi,
            'quota' => $bidang->batas_kuota,
            'kuota_total' => $bidang->batas_kuota,
            'batas_kuota' => $bidang->batas_kuota,
            'jurusan' => [],
        ]);

        return Inertia::render('Admin/Bidang/Edit', [
            'activeNav' => 'admin.bidang',
            'bidang' => $bidangData,
        ]);
    }

    public function update(Request $request, SubInstansi $bidang)
    {
        $this->authorizeSubInstansi($bidang, $request->user());
        $data = $this->validated($request);

        DB::transaction(function () use ($bidang, $data) {
            $bidang->update([
                'nama_sub_instansi' => $data['nama_sub_instansi'],
                'deskripsi' => $data['deskripsi'],
                'batas_kuota' => $data['batas_kuota'],
            ]);
        });

        return to_route('admin.bidang.show', $bidang)->with('success', 'Bidang berhasil diperbarui.');
    }

    public function destroy(Request $request, SubInstansi $bidang)
    {
        $this->authorizeSubInstansi($bidang, $request->user());

        abort_if(
            $bidang->permohonanPkl()->exists(),
            422,
            'Bidang yang sudah memiliki pengajuan tidak dapat dihapus.'
        );

        $bidang->delete();

        return to_route('admin.bidang.index')->with('success', 'Bidang berhasil dihapus.');
    }

    private function queryFor($user)
    {
        return SubInstansi::query()->where('id_instansi', $user->id_instansi);
    }

    private function authorizeSubInstansi(SubInstansi $sub, $user): void
    {
        abort_unless($sub->id_instansi === $user->id_instansi, 403);
    }

    private function withQuota(SubInstansi $sub): SubInstansi
    {
        $kuota = (int) $sub->batas_kuota;
        $terisi = (int) ($sub->peserta_aktif_count ?? 0);
        $sisa = max(0, $kuota - $terisi);
        $sisaPct = $kuota > 0 ? ($sisa / $kuota) * 100 : 0;

        $sub->setAttribute('nama', $sub->nama_sub_instansi);
        $sub->setAttribute('kuota_total', $kuota);
        $sub->setAttribute('terisi_total', $terisi);
        $sub->setAttribute('sisa_total', $sisa);
        $sub->setAttribute('status', $sisa <= 0 ? 'penuh' : ($sisaPct > 50 ? 'tersedia' : ($sisaPct >= 20 ? 'menipis' : 'hampir-penuh')));

        return $sub;
    }

    private function validated(Request $request): array
    {
        $input = $request->all();
        if (!isset($input['nama_sub_instansi']) && isset($input['nama'])) {
            $input['nama_sub_instansi'] = $input['nama'];
        }
        if (!isset($input['batas_kuota'])) {
            if (isset($input['kuota_total'])) {
                $input['batas_kuota'] = $input['kuota_total'];
            } elseif (isset($input['kuota'])) {
                $input['batas_kuota'] = $input['kuota'];
            }
        }
        $request->merge($input);

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'nama_sub_instansi' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'batas_kuota' => ['required', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            if ($errors->has('nama_sub_instansi')) {
                $errors->add('nama', $errors->first('nama_sub_instansi'));
            }
            if ($errors->has('batas_kuota')) {
                $errors->add('kuota_total', $errors->first('batas_kuota'));
            }
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        return $validator->validated();
    }
}