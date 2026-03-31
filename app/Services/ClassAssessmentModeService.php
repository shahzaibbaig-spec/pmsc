<?php

namespace App\Services;

use App\Models\SchoolClass;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ClassAssessmentModeService
{
    /**
     * @var array<int, array{code:string,label:string,sort_order:int}>
     */
    private const GRADE_SCALE = [
        ['code' => 'A*', 'label' => 'Excellent', 'sort_order' => 1],
        ['code' => 'A', 'label' => 'Very Good', 'sort_order' => 2],
        ['code' => 'B', 'label' => 'Good', 'sort_order' => 3],
        ['code' => 'C', 'label' => 'Satisfactory', 'sort_order' => 4],
        ['code' => 'D', 'label' => 'Basic', 'sort_order' => 5],
        ['code' => 'E', 'label' => 'Needs Improvement', 'sort_order' => 6],
        ['code' => 'F', 'label' => 'Weak', 'sort_order' => 7],
        ['code' => 'G', 'label' => 'Very Weak', 'sort_order' => 8],
        ['code' => 'U', 'label' => 'Ungraded / Not Assessed', 'sort_order' => 9],
    ];

    public function classUsesGradeSystem(SchoolClass|int|string|null $class): bool
    {
        $name = $this->resolveClassName($class);
        if ($name === null) {
            return false;
        }

        $normalized = Str::of($name)
            ->lower()
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->value();

        return in_array($normalized, ['pg', 'prep', 'nursery', '1', 'class 1'], true);
    }

    /**
     * @return array<int, array{code:string,label:string,sort_order:int}>
     */
    public function gradeScale(): array
    {
        return self::GRADE_SCALE;
    }

    /**
     * @return array<int, string>
     */
    public function gradeCodes(): array
    {
        return array_column(self::GRADE_SCALE, 'code');
    }

    public function normalizeGrade(?string $grade): ?string
    {
        $resolved = Str::upper(trim((string) $grade));

        if ($resolved === '') {
            return null;
        }

        return $resolved === 'A*' ? 'A*' : $resolved;
    }

    public function isValidGrade(?string $grade): bool
    {
        $normalized = $this->normalizeGrade($grade);

        return $normalized !== null && in_array($normalized, $this->gradeCodes(), true);
    }

    public function gradeLabel(?string $grade): ?string
    {
        $normalized = $this->normalizeGrade($grade);
        if ($normalized === null) {
            return null;
        }

        $item = collect(self::GRADE_SCALE)->firstWhere('code', $normalized);

        return $item['label'] ?? null;
    }

    public function dominantGrade(iterable $grades): ?string
    {
        $normalizedGrades = collect($grades)
            ->map(fn ($grade): ?string => $this->normalizeGrade(is_string($grade) ? $grade : null))
            ->filter()
            ->values();

        if ($normalizedGrades->isEmpty()) {
            return null;
        }

        $counts = $normalizedGrades->countBy();
        $metadata = collect(self::GRADE_SCALE)->keyBy('code');

        return $counts
            ->keys()
            ->sort(function (string $leftCode, string $rightCode) use ($counts, $metadata): int {
                $leftCount = (int) ($counts->get($leftCode) ?? 0);
                $rightCount = (int) ($counts->get($rightCode) ?? 0);

                if ($leftCount !== $rightCount) {
                    return $rightCount <=> $leftCount;
                }

                $leftSort = (int) ($metadata->get($leftCode)['sort_order'] ?? PHP_INT_MAX);
                $rightSort = (int) ($metadata->get($rightCode)['sort_order'] ?? PHP_INT_MAX);

                if ($leftSort !== $rightSort) {
                    return $leftSort <=> $rightSort;
                }

                return strcmp($leftCode, $rightCode);
            })
            ->first();
    }

    public function overallPerformanceLabel(iterable $grades): ?string
    {
        $dominantGrade = $this->dominantGrade($grades);

        return $this->gradeLabel($dominantGrade);
    }

    private function resolveClassName(SchoolClass|int|string|null $class): ?string
    {
        if ($class instanceof SchoolClass) {
            return $class->name;
        }

        if (is_int($class)) {
            return SchoolClass::query()->find($class, ['name'])?->name;
        }

        if (is_string($class)) {
            return $class;
        }

        return null;
    }
}
