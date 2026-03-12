<?php

namespace Database\Seeders;

use App\Models\SchoolSetting;
use Illuminate\Database\Seeder;

class SchoolSettingSeeder extends Seeder
{
    public function run(): void
    {
        SchoolSetting::query()->firstOrCreate(
            ['id' => 1],
            [
                'school_name' => 'National School',
                'logo_path' => null,
                'address' => null,
                'phone' => null,
                'email' => null,
            ]
        );
    }
}

