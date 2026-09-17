<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Instansi;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SuperAdminInstansiController extends Controller
{
    public function index()
    {
        $instansi = Instansi::withCount(['subInstansi', 'users' => function($q) {
            $q->whereHas('roles', fn($r) => $r->where('name', 'agency_admin'));
        }])->get()->map(function($i) {
            return [
                'id' => $i->id,
                'nama' => $i->nama_instansi,
                'tipe' => 'pemerintah', // fallback or real if it existed
                'alamat' => $i->alamat,
                'email' => '-',
                'jumlah_bidang_pkl' => $i->sub_instansi_count,
                'jumlah_admin' => $i->users_count,
            ];
        });

        return Inertia::render('SuperAdmin/Instansi/Index', [
            'activeNav' => 'superadmin.instansi',
            'instansiList' => $instansi,
        ]);
    }

    public function show(Instansi $instansi)
    {
        $instansiData = [
            'id' => $instansi->id,
            'nama' => $instansi->nama_instansi,
            'tipe' => 'pemerintah',
            'alamat' => $instansi->alamat,
            'email' => '-',
            'deskripsi' => $instansi->deskripsi_singkat,
            'created_at' => $instansi->created_at->format('Y-m-d'),
        ];

        $bidangPkl = $instansi->subInstansi()->withCount(['pesertaMagang' => function($q) {
            $q->where('status_magang', 'aktif');
        }])->get()->map(function($sub) {
            return [
                'id' => $sub->id,
                'nama' => $sub->nama_sub_instansi,
                'kuota' => $sub->batas_kuota,
                'terisi' => $sub->peserta_magang_count,
                'status' => $sub->batas_kuota > $sub->peserta_magang_count ? 'aktif' : 'penuh',
            ];
        });

        $adminList = User::where('id_instansi', $instansi->id)->role('agency_admin')->get()->map(function($user) {
            return [
                'id' => $user->id,
                'nama' => $user->nama_lengkap,
                'email' => $user->email,
                'oauth_connected' => !empty($user->google_id) && !str_starts_with($user->google_id, 'seed_'),
                'status' => true,
            ];
        });

        return Inertia::render('SuperAdmin/Instansi/Show', [
            'activeNav' => 'superadmin.instansi',
            'instansi' => $instansiData,
            'bidangPkl' => $bidangPkl,
            'adminList' => $adminList,
        ]);
    }
}
