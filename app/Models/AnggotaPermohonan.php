<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnggotaPermohonan extends Model
{
    use HasFactory;

    protected $table = 'anggota_permohonan';

    protected $fillable = [
        'id_permohonan',
        'nama_mahasiswa',
        'nim',
        'sekolah',
        'no_hp',
    ];

    public function permohonanPkl(): BelongsTo
    {
        return $this->belongsTo(PermohonanPkl::class, 'id_permohonan');
    }
}
