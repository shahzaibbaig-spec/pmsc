<?php

namespace App\Modules\Results\Services;

use App\Models\{Mark, ReportComment, StudentResult};
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class ResultCheckerSheetService
{
    public function build(Collection $records, bool $published): Collection
    {
        if ($records->isEmpty()) {
            return collect();
        }
        $cohortQuery = $published ? StudentResult::query() : Mark::query();
        $cohort = $cohortQuery->whereIn('exam_id', $records->pluck('exam_id')->filter()->unique())
            ->whereIn('session', $records->pluck('session')->unique())->get();
        $comments = Schema::hasTable('report_comments')
            ? ReportComment::with('generatedBy.roles')->whereIn('student_id', $records->pluck('student_id')->unique())
                ->whereIn('session', $records->pluck('session')->unique())->get()
            : collect();
        $service = app(ResultCheckerCardService::class);
        $groups = $records->groupBy(function ($record) use ($published) {
            return json_encode([
                $record->session,
                $published ? $record->class_id : $record->exam?->class_id,
                $record->exam?->exam_type?->value,
                $published ? $record->exam_name : $record->exam?->exam_label,
                // Separate individual tests; term subjects share a single row.
                in_array($record->exam?->exam_type?->value, ['class_test', 'bimonthly_test'], true) ? $record->exam_id : null,
            ]);
        });

        return $groups->map(function ($group) use ($published, $service, $cohort, $comments) {
            $cards = $group->groupBy('student_id')->map(fn ($studentRecords) =>
                $service->build($published ? 'published' : 'marks', $studentRecords->first()->id, $studentRecords, $cohort, $comments))
                ->sortBy(fn ($card) => $card['student']?->name ?? '')->values();
            return [
                'cards' => $cards,
                'subjects' => $cards->flatMap(fn ($card) => $card['rows']->map(fn ($row) => ['id' => $row['subject_id'], 'name' => $row['subject']]))
                    ->unique('id')->sortBy('name')->values(),
                'first' => $cards->first(),
            ];
        })->values();
    }
}
