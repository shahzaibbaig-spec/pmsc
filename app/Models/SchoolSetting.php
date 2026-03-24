<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class SchoolSetting extends Model
{
    public const CACHE_KEY = 'school_settings_single';

    protected $fillable = [
        'school_name',
        'logo_path',
        'address',
        'phone',
        'email',
        'block_results_for_defaulters',
        'block_admit_card_for_defaulters',
        'block_id_card_for_defaulters',
    ];

    protected function casts(): array
    {
        return [
            'block_results_for_defaulters' => 'boolean',
            'block_admit_card_for_defaulters' => 'boolean',
            'block_id_card_for_defaulters' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    public static function cached(): ?self
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(60), function (): ?self {
            $columns = ['id', 'school_name', 'logo_path', 'address', 'phone', 'email'];

            if (Schema::hasTable('school_settings') && Schema::hasColumn('school_settings', 'block_results_for_defaulters')) {
                $columns[] = 'block_results_for_defaulters';
            }

            if (Schema::hasTable('school_settings') && Schema::hasColumn('school_settings', 'block_admit_card_for_defaulters')) {
                $columns[] = 'block_admit_card_for_defaulters';
            }

            if (Schema::hasTable('school_settings') && Schema::hasColumn('school_settings', 'block_id_card_for_defaulters')) {
                $columns[] = 'block_id_card_for_defaulters';
            }

            return self::query()->first($columns);
        });
    }
}
