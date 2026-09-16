<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubInstansi extends Model
{
    use HasFactory;

    protected $table = 'sub_instansi';

    protected $fillable = [
        'id_instansi',
        'nama_sub_instansi',
        'deskripsi',
        'batas_kuota',
    ];

    public function instansi(): BelongsTo
    {
        return $this->belongsTo(Instansi::class, 'id_instansi');
    }

    public function permohonanPkl(): HasMany
    {
        return $this->hasMany(PermohonanPkl::class, 'id_sub_instansi');
    }

    public function pesertaMagang(): HasMany
    {
        return $this->hasMany(PesertaMagang::class, 'id_sub_instansi');
    }
}
