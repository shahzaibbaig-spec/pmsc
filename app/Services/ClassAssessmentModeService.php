<?php

namespace App\Services;

use App\Models\SchoolClass;
use Illuminate\Support\Str;

class ClassAssessmentModeService
{
    public function __construct(private readonly GradeScaleService $gradeScaleService)
    {
    }

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
     * @return array<int, array{
     *   code:string,
     *   label:string,
     *   sort_order:int,
     *   percentage_equivalent:float,
     *   grade_point:float
     * }>
     */
    public function gradeScale(): array
    {
        return collect($this->gradeScaleService->scaleRows())
            ->map(static fn (array $row): array => [
                'code' => (string) $row['grade_code'],
                'label' => (string) $row['label'],
                'sort_order' => (int) $row['sort_order'],
                'percentage_equivalent' => (float) $row['percentage_equivalent'],
                'grade_point' => (float) $row['grade_point'],
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function gradeCodes(): array
    {
        return $this->gradeScaleService->gradeCodes();
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

        return $this->gradeScaleService->getLabel($normalized);
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
        return $counts
            ->keys()
            ->sort(function (string $leftCode, string $rightCode) use ($counts): int {
                $leftCount = (int) ($counts->get($leftCode) ?? 0);
                $rightCount = (int) ($counts->get($rightCode) ?? 0);

                if ($leftCount !== $rightCount) {
                    return $rightCount <=> $leftCount;
                }

                $leftSort = $this->gradeScaleService->getSortOrder($leftCode);
                $rightSort = $this->gradeScaleService->getSortOrder($rightCode);

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
