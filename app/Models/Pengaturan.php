<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengaturan extends Model
{
    protected $table = 'pengaturan';
    protected $primaryKey = 'kunci';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'kunci',
        'nilai',
        'deskripsi',
    ];

    public static function get(string $key, $default = null)
    {
        $setting = static::find($key);
        return $setting ? $setting->nilai : $default;
    }

    public static function set(string $key, $value, ?string $deskripsi = null): void
    {
        static::updateOrCreate(
            ['kunci' => $key],
            ['nilai' => $value, 'deskripsi' => $deskripsi]
        );
    }
}
