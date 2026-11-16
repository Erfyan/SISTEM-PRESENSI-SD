<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HariLibur extends Model
{
    use HasFactory;

    protected $table = 'hari_libur';

    protected $fillable = [
        'tanggal',
        'keterangan',
        'berlaku_untuk',
        'kelas_id',
        'dicatat_oleh',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date:Y-m-d',
        ];
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function pencatat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }

    /**
     * Scope untuk mengambil hari libur yang berlaku untuk kelas tertentu (atau semua kelas)
     */
    public function scopeForKelas(Builder $query, ?int $kelasId): Builder
    {
        return $query->where(function ($q) use ($kelasId) {
            $q->where('berlaku_untuk', 'semua');
            if ($kelasId) {
                $q->orWhere(function ($sub) use ($kelasId) {
                    $sub->where('berlaku_untuk', 'kelas')
                        ->where('kelas_id', $kelasId);
                });
            }
        });
    }
}
