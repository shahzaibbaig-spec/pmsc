<?php

namespace Database\Seeders;

use App\Models\SchoolClass;
use Illuminate\Database\Seeder;

class StudentModuleSeeder extends Seeder
{
    public function run(): void
    {
        $classes = [
            ['name' => 'Nursery', 'section' => 'A'],
            ['name' => 'Nursery', 'section' => 'B'],
            ['name' => 'Class 1', 'section' => null],
            ['name' => 'Class 2', 'section' => null],
            ['name' => 'Class 3', 'section' => null],
            ['name' => 'Class 4', 'section' => null],
            ['name' => 'Class 5', 'section' => null],
            ['name' => 'Class 6', 'section' => null],
            ['name' => 'Class 7', 'section' => null],
            ['name' => 'Class 8', 'section' => null],
            ['name' => 'Class 9', 'section' => null],
            ['name' => 'Class 10', 'section' => null],
        ];

        foreach ($classes as $class) {
            SchoolClass::query()->firstOrCreate(
                ['name' => $class['name'], 'section' => $class['section']],
                ['status' => 'active']
            );
        }
    }
}
