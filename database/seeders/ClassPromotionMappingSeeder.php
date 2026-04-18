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
            $fromClasses = $this->classesByStage($classRows, $pair['from']);
            $toClasses = $this->classesByStage($classRows, $pair['to']);

            if ($fromClasses->isEmpty() || $toClasses->isEmpty()) {
                continue;
            }

            foreach ($fromClasses as $fromClass) {
                $fromSection = $this->sectionKey($fromClass);
                $target = $toClasses->first(
                    fn (SchoolClass $toClass): bool => $this->sectionKey($toClass) === $fromSection
                ) ?? $toClasses->first();
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

    private function classesByStage($classRows, string $stage)
    {
        $expectedStage = $this->classStageKey($stage);
        if ($expectedStage === null) {
            return collect();
        }

        return $classRows->filter(function (SchoolClass $classRoom) use ($expectedStage): bool {
            return $this->classStageKey((string) $classRoom->name) === $expectedStage;
        })->values();
    }

    private function classStageKey(string $rawName): ?string
    {
        $normalized = strtolower(trim($rawName));
        if ($normalized === '') {
            return null;
        }

        if (preg_match('/\bpg\b|\bplay\s*group\b/i', $normalized) === 1) {
            return 'pg';
        }

        if (preg_match('/\bprep\b|\bpreparatory\b/i', $normalized) === 1) {
            return 'prep';
        }

        if (preg_match('/\bnursery\b/i', $normalized) === 1) {
            return 'nursery';
        }

        if (preg_match('/\b(?:class|grade)\s*(\d{1,2})\b/i', $normalized, $matches) === 1) {
            return $this->numericStageKey((int) $matches[1]);
        }

        if (preg_match('/^(\d{1,2})(?:\s*[- ]?\s*[a-z])?$/i', $normalized, $matches) === 1) {
            return $this->numericStageKey((int) $matches[1]);
        }

        if (preg_match('/\b(\d{1,2})\b/', $normalized, $matches) === 1) {
            return $this->numericStageKey((int) $matches[1]);
        }

        return null;
    }

    private function numericStageKey(int $value): ?string
    {
        if ($value < 1 || $value > 12) {
            return null;
        }

        return (string) $value;
    }

    private function sectionKey(SchoolClass $classRoom): ?string
    {
        $explicit = trim((string) ($classRoom->section ?? ''));
        if ($explicit !== '') {
            return strtoupper($explicit);
        }

        $name = trim((string) $classRoom->name);
        if ($name === '') {
            return null;
        }

        if (preg_match('/(?:^|\b)(?:pg|prep|nursery|class\s*\d{1,2}|grade\s*\d{1,2}|\d{1,2})\s*[- ]\s*([a-z])$/i', $name, $matches) === 1) {
            return strtoupper((string) $matches[1]);
        }

        if (preg_match('/^\s*\d{1,2}\s*([a-z])\s*$/i', $name, $matches) === 1) {
            return strtoupper((string) $matches[1]);
        }

        return null;
    }
}
