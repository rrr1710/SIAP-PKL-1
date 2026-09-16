<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'google_id',
        'nama_lengkap',
        'email',
        'avatar',
        'id_instansi',
        'nim',
        'sekolah',
        'no_hp',
    ];

    public function instansi(): BelongsTo
    {
        return $this->belongsTo(Instansi::class, 'id_instansi');
    }

    public function permohonanPkl(): HasMany
    {
        return $this->hasMany(PermohonanPkl::class, 'id_pemohon');
    }
}
