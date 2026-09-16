<?php

namespace Database\Seeders;

use App\Models\AnggotaPermohonan;
use App\Models\PesertaMagang;
use App\Models\PermohonanPkl;
use App\Models\SubInstansi;
use App\Models\User;
use Illuminate\Database\Seeder;

class PermohonanPklSeeder extends Seeder
{
    public function run(): void
    {
        $subInstansiAll = SubInstansi::with('instansi')->get();

        if ($subInstansiAll->isEmpty()) {
            $this->command->warn('No sub_instansi found. Run InstansiSeeder first.');
            return;
        }

        // --- Student 1: individu, menunggu ---
        $student1 = User::create([
            'google_id' => 'seed_student_001',
            'nama_lengkap' => 'Budi Santoso',
            'email' => 'budi.santoso@student.test',
            'avatar' => null,
            'id_instansi' => null,
            'nim' => '2021001001',
            'sekolah' => 'Universitas Indonesia',
            'no_hp' => '081234560001',
        ]);
        $student1->assignRole('student');

        $sub1 = $subInstansiAll->first();
        PermohonanPkl::create([
            'id_pemohon' => $student1->id,
            'id_sub_instansi' => $sub1->id,
            'jenis_pengajuan' => 'individu',
            'status' => 'menunggu',
            'sekolah' => 'Universitas Indonesia',
            'no_hp' => '081234560001',
            'tanggal_mulai' => '2026-10-01',
            'tanggal_selesai' => '2026-12-31',
            'berkas_permohonan' => 'applications/sample_budi.pdf',
            'catatan_admin' => null,
        ]);

        // --- Student 2: kelompok, diterima → creates PesertaMagang ---
        $student2 = User::create([
            'google_id' => 'seed_student_002',
            'nama_lengkap' => 'Siti Rahayu',
            'email' => 'siti.rahayu@student.test',
            'avatar' => null,
            'id_instansi' => null,
            'nim' => '2021002001',
            'sekolah' => 'Institut Teknologi Bandung',
            'no_hp' => '081234560002',
        ]);
        $student2->assignRole('student');

        $sub2 = $subInstansiAll->get(1) ?? $sub1;
        $permohonan2 = PermohonanPkl::create([
            'id_pemohon' => $student2->id,
            'id_sub_instansi' => $sub2->id,
            'jenis_pengajuan' => 'kelompok',
            'status' => 'diterima',
            'sekolah' => 'Institut Teknologi Bandung',
            'no_hp' => '081234560002',
            'tanggal_mulai' => '2026-09-01',
            'tanggal_selesai' => '2026-11-30',
            'berkas_permohonan' => 'applications/sample_siti.pdf',
            'catatan_admin' => null,
        ]);

        // Anggota kelompok
        AnggotaPermohonan::insert([
            [
                'id_permohonan' => $permohonan2->id,
                'nama_mahasiswa' => 'Siti Rahayu',
                'nim' => '2021002001',
                'sekolah' => 'Institut Teknologi Bandung',
                'no_hp' => '081234560002',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_permohonan' => $permohonan2->id,
                'nama_mahasiswa' => 'Andi Wijaya',
                'nim' => '2021002002',
                'sekolah' => 'Institut Teknologi Bandung',
                'no_hp' => '081234560003',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // PesertaMagang for accepted application (mirrors anggota)
        PesertaMagang::insert([
            [
                'id_sub_instansi' => $sub2->id,
                'id_permohonan' => $permohonan2->id,
                'nama_peserta' => 'Siti Rahayu',
                'nim' => '2021002001',
                'sekolah' => 'Institut Teknologi Bandung',
                'no_hp' => '081234560002',
                'tanggal_mulai' => '2026-09-01',
                'tanggal_selesai' => '2026-11-30',
                'status_magang' => 'aktif',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_sub_instansi' => $sub2->id,
                'id_permohonan' => $permohonan2->id,
                'nama_peserta' => 'Andi Wijaya',
                'nim' => '2021002002',
                'sekolah' => 'Institut Teknologi Bandung',
                'no_hp' => '081234560003',
                'tanggal_mulai' => '2026-09-01',
                'tanggal_selesai' => '2026-11-30',
                'status_magang' => 'aktif',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // --- Student 3: individu, ditolak ---
        $student3 = User::create([
            'google_id' => 'seed_student_003',
            'nama_lengkap' => 'Ahmad Fauzi',
            'email' => 'ahmad.fauzi@student.test',
            'avatar' => null,
            'id_instansi' => null,
            'nim' => '2020003001',
            'sekolah' => 'Universitas Gadjah Mada',
            'no_hp' => '081234560004',
        ]);
        $student3->assignRole('student');

        PermohonanPkl::create([
            'id_pemohon' => $student3->id,
            'id_sub_instansi' => $sub1->id,
            'jenis_pengajuan' => 'individu',
            'status' => 'ditolak',
            'sekolah' => 'Universitas Gadjah Mada',
            'no_hp' => '081234560004',
            'tanggal_mulai' => '2026-08-01',
            'tanggal_selesai' => '2026-10-31',
            'berkas_permohonan' => 'applications/sample_ahmad.pdf',
            'catatan_admin' => 'Kuota untuk periode ini sudah penuh.',
        ]);
    }
}
