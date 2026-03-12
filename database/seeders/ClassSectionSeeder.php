<?php

namespace Database\Seeders;

use App\Models\ClassSection;
use App\Models\SchoolClass;
use Illuminate\Database\Seeder;

class ClassSectionSeeder extends Seeder
{
    public function run(): void
    {
        $classes = SchoolClass::query()
            ->orderBy('id')
            ->get(['id', 'section']);

        foreach ($classes as $classRoom) {
            $section = $classRoom->section ?: 'A';

            ClassSection::query()->firstOrCreate([
                'class_id' => $classRoom->id,
                'section_name' => $section,
            ]);
        }
    }
}

