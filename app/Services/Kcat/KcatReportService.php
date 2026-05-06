<?php

namespace App\Services\Kcat;

use App\Models\CareerProfile;
use App\Models\KcatAttempt;
use App\Models\KcatReportNote;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class KcatReportService
{
    public function generateStudentReport(KcatAttempt $attempt): array
    {
        $attempt = $attempt->load([
            'student.classRoom',
            'test',
            'scores.section',
            'latestReportNote.counselor',
            'streamRecommendations',
            'overrideBy',
        ]);

        $recommendations = $attempt->streamRecommendations->sortBy('rank')->values();
        $finalStream = $attempt->counselor_override_stream ?: $attempt->recommended_stream;
        $finalSummary = $attempt->counselor_override_reason ?: $attempt->recommendation_summary;

        return [
            'attempt' => $attempt,
            'scores' => $attempt->scores,
            'note' => $attempt->latestReportNote,
            'recommendations' => $recommendations,
            'final_stream' => $finalStream,
            'final_summary' => $finalSummary,
        ];
    }

    public function getPrincipalReportData(array $filters = []): LengthAwarePaginator
    {
        return KcatAttempt::query()
            ->with(['student.classRoom', 'test', 'counselor', 'streamRecommendations'])
            ->whereIn('status', ['submitted', 'reviewed'])
            ->when($filters['session'] ?? null, fn (Builder $query, string $session) => $query->where('session', $session))
            ->when($filters['student'] ?? null, function (Builder $query, string $student): void {
                $like = '%'.trim($student).'%';
                $query->whereHas('student', fn (Builder $studentQuery) => $studentQuery->where('name', 'like', $like)->orWhere('student_id', 'like', $like));
            })
            ->latest('submitted_at')
            ->paginate(20)
            ->withQueryString();
    }

    public function getGradeWiseSummary(array $filters = []): Collection
    {
        return KcatAttempt::query()
            ->with('student.classRoom')
            ->whereIn('status', ['submitted', 'reviewed'])
            ->when($filters['session'] ?? null, fn (Builder $query, string $session) => $query->where('session', $session))
            ->get()
            ->groupBy(fn (KcatAttempt $attempt): string => trim(($attempt->student?->classRoom?->name ?? 'Unassigned').' '.($attempt->student?->classRoom?->section ?? '')))
            ->map(fn (Collection $rows, string $className): array => [
                'class_name' => $className,
                'attempts' => $rows->count(),
                'average' => round((float) $rows->avg('percentage'), 2),
                'needs_support' => $rows->where('band', 'needs_support')->count(),
            ])
            ->values();
    }

    public function attachReportToCareerProfile(KcatAttempt $attempt, CareerProfile $profile): CareerProfile
    {
        return DB::transaction(function () use ($attempt, $profile): CareerProfile {
            $summary = trim((string) $profile->counselor_notes);
            $stream = $attempt->counselor_override_stream ?: $attempt->recommended_stream;
            $addition = 'KCAT '.$attempt->test?->title.': '.$attempt->percentage.'% ('.$attempt->band.'). Suggested direction: '.$stream.'.';
            $profile->update(['counselor_notes' => trim($summary.PHP_EOL.$addition)]);

            return $profile->fresh();
        });
    }

    public function createCounselorNotes(KcatAttempt $attempt, array $data, User $counselor): KcatReportNote
    {
        return DB::transaction(function () use ($attempt, $data, $counselor): KcatReportNote {
            $note = KcatReportNote::query()->create([
                ...collect($data)->only(['strengths', 'development_areas', 'counselor_recommendation', 'parent_summary', 'private_notes', 'visibility'])->all(),
                'kcat_attempt_id' => $attempt->id,
                'counselor_id' => $counselor->id,
                'created_by' => $counselor->id,
                'updated_by' => $counselor->id,
            ]);

            $attempt->update(['status' => 'reviewed', 'updated_by' => $counselor->id]);

            return $note->fresh(['attempt.student.classRoom', 'counselor']);
        });
    }

    public function overrideRecommendation(KcatAttempt $attempt, array $data, User $counselor): KcatAttempt
    {
        return DB::transaction(function () use ($attempt, $data, $counselor): KcatAttempt {
            $attempt->update([
                'counselor_override_stream' => $data['counselor_override_stream'],
                'counselor_override_reason' => $data['counselor_override_reason'],
                'override_by' => $counselor->id,
                'override_at' => now(),
                'updated_by' => $counselor->id,
            ]);

            return $attempt->fresh(['streamRecommendations', 'overrideBy']) ?? $attempt;
        });
    }
}
