<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /** Obtiene un valor de configuración (cacheado). */
    public static function get(string $key, ?string $default = null): ?string
    {
        return Cache::rememberForever("settings.{$key}",
            fn () => static::where('key', $key)->value('value')) ?? $default;
    }

    /** Guarda un valor y refresca el caché. */
    public static function set(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forever("settings.{$key}", $value);
    }
}
