<?php

namespace App\Services;

use App\Models\GradeScale;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class GradeScaleService
{
    /**
     * @var array<int, array{
     *   grade_code:string,
     *   label:string,
     *   percentage_equivalent:float,
     *   grade_point:float,
     *   sort_order:int,
     *   is_active:bool
     * }>
     */
    private const DEFAULT_SCALES = [
        ['grade_code' => 'A*', 'label' => 'Excellent', 'percentage_equivalent' => 95.00, 'grade_point' => 6.00, 'sort_order' => 1, 'is_active' => true],
        ['grade_code' => 'A', 'label' => 'Very Good', 'percentage_equivalent' => 88.00, 'grade_point' => 5.50, 'sort_order' => 2, 'is_active' => true],
        ['grade_code' => 'B', 'label' => 'Good', 'percentage_equivalent' => 80.00, 'grade_point' => 5.00, 'sort_order' => 3, 'is_active' => true],
        ['grade_code' => 'C', 'label' => 'Satisfactory', 'percentage_equivalent' => 72.00, 'grade_point' => 4.50, 'sort_order' => 4, 'is_active' => true],
        ['grade_code' => 'D', 'label' => 'Basic', 'percentage_equivalent' => 64.00, 'grade_point' => 4.00, 'sort_order' => 5, 'is_active' => true],
        ['grade_code' => 'E', 'label' => 'Needs Improvement', 'percentage_equivalent' => 56.00, 'grade_point' => 3.50, 'sort_order' => 6, 'is_active' => true],
        ['grade_code' => 'F', 'label' => 'Weak', 'percentage_equivalent' => 48.00, 'grade_point' => 3.00, 'sort_order' => 7, 'is_active' => true],
        ['grade_code' => 'G', 'label' => 'Very Weak', 'percentage_equivalent' => 38.00, 'grade_point' => 2.00, 'sort_order' => 8, 'is_active' => true],
        ['grade_code' => 'U', 'label' => 'Ungraded / Unsatisfactory', 'percentage_equivalent' => 0.00, 'grade_point' => 0.00, 'sort_order' => 9, 'is_active' => true],
    ];

    private ?Collection $cachedScales = null;

    public function getGradePoint(string $grade): float
    {
        return (float) ($this->scaleForGrade($grade)['grade_point'] ?? 0.0);
    }

    public function getPercentageEquivalent(string $grade): float
    {
        return (float) ($this->scaleForGrade($grade)['percentage_equivalent'] ?? 0.0);
    }

    public function getOverallLabelFromCgpa(float $cgpa): string
    {
        $normalized = max(0.0, round($cgpa, 2));

        $match = $this->activeScales()
            ->sortBy('sort_order')
            ->first(fn (array $row): bool => $normalized >= (float) $row['grade_point']);

        return (string) ($match['label'] ?? 'Ungraded / Unsatisfactory');
    }

    /**
     * @return array<int, array{
     *   grade_code:string,
     *   label:string,
     *   percentage_equivalent:float,
     *   grade_point:float,
     *   sort_order:int,
     *   is_active:bool
     * }>
     */
    public function scaleRows(): array
    {
        return $this->activeScales()->values()->all();
    }

    /**
     * @return array<int, string>
     */
    public function gradeCodes(): array
    {
        return $this->activeScales()
            ->pluck('grade_code')
            ->values()
            ->all();
    }

    public function getLabel(string $grade): ?string
    {
        return $this->scaleForGrade($grade)['label'] ?? null;
    }

    public function getSortOrder(string $grade): int
    {
        return (int) ($this->scaleForGrade($grade)['sort_order'] ?? PHP_INT_MAX);
    }

    /**
     * @return array{
     *   grade_code:string,
     *   label:string,
     *   percentage_equivalent:float,
     *   grade_point:float,
     *   sort_order:int,
     *   is_active:bool
     * }|null
     */
    private function scaleForGrade(string $grade): ?array
    {
        return $this->activeScales()
            ->keyBy('grade_code')
            ->get(strtoupper(trim($grade)));
    }

    private function activeScales(): Collection
    {
        if ($this->cachedScales !== null) {
            return $this->cachedScales;
        }

        if (Schema::hasTable('grade_scales')) {
            $rows = GradeScale::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get()
                ->map(fn (GradeScale $scale): array => [
                    'grade_code' => (string) $scale->grade_code,
                    'label' => (string) $scale->label,
                    'percentage_equivalent' => round((float) $scale->percentage_equivalent, 2),
                    'grade_point' => round((float) $scale->grade_point, 2),
                    'sort_order' => (int) $scale->sort_order,
                    'is_active' => (bool) $scale->is_active,
                ]);

            if ($rows->isNotEmpty()) {
                $this->cachedScales = $rows->values();

                return $this->cachedScales;
            }
        }

        $this->cachedScales = collect(self::DEFAULT_SCALES);

        return $this->cachedScales;
    }
}
