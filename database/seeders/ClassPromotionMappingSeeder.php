<?php

namespace Database\Seeders;

use App\Models\ClassPromotionMapping;
use App\Models\SchoolClass;
use Illuminate\Database\Seeder;

class ClassPromotionMappingSeeder extends Seeder
{
    public function run(): void
    {
        $classRows = SchoolClass::query()
            ->orderBy('name')
            ->orderBy('section')
            ->get(['id', 'name', 'section']);

        $mappings = [
            ['from' => 'PG', 'to' => 'Prep'],
            ['from' => 'Prep', 'to' => 'Nursery'],
            ['from' => 'Nursery', 'to' => '1'],
            ['from' => '1', 'to' => '2'],
            ['from' => '2', 'to' => '3'],
            ['from' => '3', 'to' => '4'],
            ['from' => '4', 'to' => '5'],
            ['from' => '5', 'to' => '6'],
            ['from' => '6', 'to' => '7'],
            ['from' => '7', 'to' => '8'],
            ['from' => '8', 'to' => '9'],
            ['from' => '9', 'to' => '10'],
            ['from' => '10', 'to' => '11'],
            ['from' => '11', 'to' => '12'],
        ];

        foreach ($mappings as $pair) {
            $fromClasses = $this->classesByName($classRows, $pair['from']);
            $toClasses = $this->classesByName($classRows, $pair['to']);

            if ($fromClasses->isEmpty() || $toClasses->isEmpty()) {
                continue;
            }

            foreach ($fromClasses as $fromClass) {
                $target = $toClasses->firstWhere('section', $fromClass->section) ?? $toClasses->first();
                if (! $target) {
                    continue;
                }

                $mapping = ClassPromotionMapping::query()->updateOrCreate(
                    [
                        'from_class_id' => (int) $fromClass->id,
                    ],
                    [
                        'to_class_id' => (int) $target->id,
                    ]
                );

                ClassPromotionMapping::query()
                    ->where('from_class_id', (int) $fromClass->id)
                    ->where('id', '!=', (int) $mapping->id)
                    ->delete();
            }
        }
    }

    private function classesByName($classRows, string $name)
    {
        $needle = strtolower(trim($name));

        return $classRows->filter(function (SchoolClass $classRoom) use ($needle): bool {
            return strtolower(trim((string) $classRoom->name)) === $needle;
        })->values();
    }
}

