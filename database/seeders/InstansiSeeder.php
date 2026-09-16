<?php

namespace Database\Seeders;

use App\Models\Instansi;
use App\Models\SubInstansi;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class InstansiSeeder extends Seeder
{
    public function run(): void
    {
        // --- Instansi 1 ---
        $kominfo = Instansi::create([
            'nama_instansi' => 'Dinas Komunikasi dan Informatika',
            'alamat' => 'Jl. Jenderal Sudirman No. 12, Jakarta Pusat',
            'deskripsi_singkat' => 'Dinas yang mengelola teknologi informasi, komunikasi, dan persandian daerah.',
            'status' => 'aktif',
        ]);

        SubInstansi::insert([
            [
                'id_instansi' => $kominfo->id,
                'nama_sub_instansi' => 'Bidang Pengembangan Aplikasi',
                'deskripsi' => 'Pengembangan dan pemeliharaan sistem informasi dan aplikasi layanan publik.',
                'batas_kuota' => 6,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_instansi' => $kominfo->id,
                'nama_sub_instansi' => 'Bidang Infrastruktur dan Jaringan',
                'deskripsi' => 'Pengelolaan infrastruktur teknologi, server, dan jaringan komunikasi data.',
                'batas_kuota' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_instansi' => $kominfo->id,
                'nama_sub_instansi' => 'Bidang Data dan Statistik',
                'deskripsi' => 'Pengolahan, analisis, dan publikasi data statistik daerah.',
                'batas_kuota' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Create agency_admin user for Kominfo
        $adminKominfo = User::create([
            'google_id' => 'seed_admin_kominfo_001',
            'nama_lengkap' => 'Admin Kominfo',
            'email' => 'admin@kominfo.go.id',
            'avatar' => null,
            'id_instansi' => $kominfo->id,
            'nim' => null,
            'sekolah' => null,
            'no_hp' => '081100000001',
        ]);
        $adminKominfo->assignRole('agency_admin');

        // --- Instansi 2 ---
        $bpkad = Instansi::create([
            'nama_instansi' => 'Badan Pengelolaan Keuangan dan Aset Daerah',
            'alamat' => 'Jl. Merdeka Barat No. 5, Jakarta',
            'deskripsi_singkat' => 'Badan yang mengelola keuangan, anggaran, dan aset milik pemerintah daerah.',
            'status' => 'aktif',
        ]);

        SubInstansi::insert([
            [
                'id_instansi' => $bpkad->id,
                'nama_sub_instansi' => 'Bidang Akuntansi dan Pelaporan',
                'deskripsi' => 'Penyusunan laporan keuangan dan pengelolaan akuntansi pemerintah.',
                'batas_kuota' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_instansi' => $bpkad->id,
                'nama_sub_instansi' => 'Bidang Aset dan Inventarisasi',
                'deskripsi' => 'Pengelolaan dan inventarisasi aset daerah.',
                'batas_kuota' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Create agency_admin user for BPKAD
        $adminBpkad = User::create([
            'google_id' => 'seed_admin_bpkad_001',
            'nama_lengkap' => 'Admin BPKAD',
            'email' => 'admin@bpkad.go.id',
            'avatar' => null,
            'id_instansi' => $bpkad->id,
            'nim' => null,
            'sekolah' => null,
            'no_hp' => '081100000002',
        ]);
        $adminBpkad->assignRole('agency_admin');

        // --- Super Admin ---
        $superAdmin = User::create([
            'google_id' => 'seed_super_admin_001',
            'nama_lengkap' => 'Super Administrator',
            'email' => 'superadmin@siappkl.go.id',
            'avatar' => null,
            'id_instansi' => null,
            'nim' => null,
            'sekolah' => null,
            'no_hp' => '081100000000',
        ]);
        $superAdmin->assignRole('super_admin');
    }
}
