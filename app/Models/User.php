<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'username',
        'nama',
        'password',
        'peran',
        'aktif',
        'wajib_ganti_password',
        'template_wa',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'aktif' => 'boolean',
            'wajib_ganti_password' => 'boolean',
        ];
    }

    public function kelasDiampu(): HasOne
    {
        return $this->hasOne(Kelas::class, 'wali_id');
    }

    public function anak(): BelongsToMany
    {
        return $this->belongsToMany(Siswa::class, 'ortu_siswa', 'pengguna_id', 'siswa_id')->withTimestamps();
    }

    public function absensiDicatat(): HasMany
    {
        return $this->hasMany(Absensi::class, 'dicatat_oleh');
    }

    public function isAdmin(): bool
    {
        return $this->peran === 'admin';
    }

    public function isGuru(): bool
    {
        return $this->peran === 'guru';
    }

    public function isOrtu(): bool
    {
        return $this->peran === 'ortu';
    }
}
