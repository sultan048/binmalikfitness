<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    private const CACHE_KEY = 'settings.all';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'value' => 'array',
            'is_demo' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Any write invalidates the cached key/value map
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    /**
     * Settings are read on almost every page (header, footer, WhatsApp button), so the whole
     * key/value map is cached and reloaded only after a setting changes.
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        // Eloquent's pluck() applies the 'array' cast to each value
        $all = Cache::rememberForever(self::CACHE_KEY, fn () => static::query()->pluck('value', 'key')->all());

        return array_key_exists($key, $all) && $all[$key] !== null ? $all[$key] : $default;
    }
}
