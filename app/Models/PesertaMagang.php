<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PesertaMagang extends Model
{
    use HasFactory;

    protected $table = 'peserta_magang';

    protected $fillable = [
        'id_sub_instansi',
        'id_permohonan',
        'nama_peserta',
        'nim',
        'sekolah',
        'no_hp',
        'tanggal_mulai',
        'tanggal_selesai',
        'status_magang',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
        ];
    }

    public function subInstansi(): BelongsTo
    {
        return $this->belongsTo(SubInstansi::class, 'id_sub_instansi');
    }

    public function permohonanPkl(): BelongsTo
    {
        return $this->belongsTo(PermohonanPkl::class, 'id_permohonan');
    }
}
