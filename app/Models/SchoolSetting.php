<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SchoolSetting extends Model
{
    public const CACHE_KEY = 'school_settings_single';

    protected $fillable = [
        'school_name',
        'logo_path',
        'address',
        'phone',
        'email',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    public static function cached(): ?self
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(60), function (): ?self {
            return self::query()->first(['id', 'school_name', 'logo_path', 'address', 'phone', 'email']);
        });
    }
}
