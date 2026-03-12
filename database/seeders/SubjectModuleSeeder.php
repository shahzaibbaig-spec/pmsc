<?php

namespace Database\Seeders;

use App\Models\Subject;
use Illuminate\Database\Seeder;

class SubjectModuleSeeder extends Seeder
{
    public function run(): void
    {
        $defaultSubjects = [
            ['name' => 'English', 'code' => 'ENG'],
            ['name' => 'Urdu', 'code' => 'URD'],
            ['name' => 'Mathematics', 'code' => 'MTH'],
            ['name' => 'Islamiyat', 'code' => 'ISL'],
            ['name' => 'Pakistan Studies', 'code' => 'PST'],
            ['name' => 'Physics', 'code' => 'PHY'],
            ['name' => 'Chemistry', 'code' => 'CHE'],
            ['name' => 'Biology', 'code' => 'BIO'],
            ['name' => 'Computer Science', 'code' => 'CSC'],
        ];

        foreach ($defaultSubjects as $subject) {
            Subject::query()->updateOrCreate(
                ['name' => $subject['name']],
                [
                    'code' => $subject['code'],
                    'is_default' => true,
                    'status' => 'active',
                ]
            );
        }
    }
}

