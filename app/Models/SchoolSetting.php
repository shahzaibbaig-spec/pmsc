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
        $payload = Cache::remember(self::CACHE_KEY, now()->addMinutes(60), function (): ?array {
            if (! Schema::hasTable('school_settings')) {
                return null;
            }

            $columns = ['id', 'school_name', 'logo_path', 'address', 'phone', 'email'];

            foreach ([
                'block_results_for_defaulters',
                'block_admit_card_for_defaulters',
                'block_id_card_for_defaulters',
            ] as $column) {
                if (Schema::hasColumn('school_settings', $column)) {
                    $columns[] = $column;
                }
            }

            return self::query()->first($columns)?->toArray();
        });

        // Backward-compatibility for old cache entries that stored full model instances.
        if ($payload instanceof self) {
            return $payload;
        }

        if (! is_array($payload) || $payload === []) {
            return null;
        }

        $setting = new self();
        $setting->setRawAttributes($payload, true);
        $setting->exists = true;

        return $setting;
    }
}
