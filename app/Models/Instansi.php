<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Instansi extends Model
{
    use HasFactory;

    protected $table = 'instansi';

    protected $fillable = [
        'nama_instansi',
        'alamat',
        'deskripsi_singkat',
        'status',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'id_instansi');
    }

    public function subInstansi(): HasMany
    {
        return $this->hasMany(SubInstansi::class, 'id_instansi');
    }
}
