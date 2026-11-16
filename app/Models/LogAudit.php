<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Request;

class LogAudit extends Model
{
    public $timestamps = false;

    protected $table = 'log_audit';

    protected $fillable = [
        'user_id',
        'username',
        'aksi',
        'ip_address',
        'user_agent',
        'detail',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'detail' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Helper untuk mencatat aktivitas ke log audit.
     */
    public static function catat(?User $user, string $aksi, ?string $username = null, ?array $detail = null): self
    {
        return self::create([
            'user_id' => $user?->id,
            'username' => $username ?? $user?->username,
            'aksi' => $aksi,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'detail' => $detail,
            'created_at' => now(),
        ]);
    }
}
