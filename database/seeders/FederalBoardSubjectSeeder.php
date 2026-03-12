<?php

namespace Database\Seeders;

use App\Models\SchoolClass;
use App\Models\Subject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FederalBoardSubjectSeeder extends Seeder
{
    public function run(): void
    {
        $federalSubjects = [
            ['name' => 'English', 'code' => 'ENG'],
            ['name' => 'Urdu', 'code' => 'URD'],
            ['name' => 'Mathematics', 'code' => 'MTH'],
            ['name' => 'General Science', 'code' => 'GSC'],
            ['name' => 'Islamiat', 'code' => 'ISL'],
            ['name' => 'Nazra Quran', 'code' => 'NZQ'],
            ['name' => 'Computer Science', 'code' => 'CSC'],
            ['name' => 'Arabic', 'code' => 'ARB'],
            ['name' => 'Pakistan Studies', 'code' => 'PST'],
            ['name' => 'Physics', 'code' => 'PHY'],
            ['name' => 'Chemistry', 'code' => 'CHE'],
            ['name' => 'Biology', 'code' => 'BIO'],
            ['name' => 'General Mathematics', 'code' => 'GMT'],
            ['name' => 'Statistics', 'code' => 'STA'],
            ['name' => 'Civics', 'code' => 'CIV'],
            ['name' => 'Economics', 'code' => 'ECO'],
            ['name' => 'Accounting', 'code' => 'ACC'],
            ['name' => 'Business Mathematics', 'code' => 'BMT'],
            ['name' => 'Principles of Commerce', 'code' => 'POC'],
            ['name' => 'Principles of Accounting', 'code' => 'POA'],
            ['name' => 'Principles of Economics', 'code' => 'POE'],
            ['name' => 'Education', 'code' => 'EDU'],
            ['name' => 'History', 'code' => 'HIS'],
            ['name' => 'Geography', 'code' => 'GEO'],
            ['name' => 'Sociology', 'code' => 'SOC'],
        ];

        $subjectIds = [];
        foreach ($federalSubjects as $subject) {
            $row = Subject::query()->updateOrCreate(
                ['name' => $subject['name']],
                [
                    'code' => $subject['code'],
                    'is_default' => true,
                    'status' => 'active',
                ]
            );
            $subjectIds[] = (int) $row->id;
        }

        $subjectIds = array_values(array_unique($subjectIds));
        if (empty($subjectIds)) {
            return;
        }

        $classIds = SchoolClass::query()->pluck('id')->map(fn ($id) => (int) $id)->all();
        $now = now();
        $rows = [];

        foreach ($classIds as $classId) {
            foreach ($subjectIds as $subjectId) {
                $rows[] = [
                    'class_id' => $classId,
                    'subject_id' => $subjectId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if (! empty($rows)) {
            DB::table('class_subject')->insertOrIgnore($rows);
        }
    }
}
