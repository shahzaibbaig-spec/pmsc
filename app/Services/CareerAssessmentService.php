<?php

namespace App\Services;

use App\Models\CareerAssessment;
use App\Models\CareerAssessmentScore;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CareerAssessmentService
{
    public const CATEGORIES = [
        'science_interest', 'math_ability', 'communication_skills', 'leadership', 'creativity',
        'problem_solving', 'technical_interest', 'business_interest', 'social_work_interest', 'medical_interest',
    ];

    public function __construct(private readonly CareerCounselorService $careerCounselorService)
    {
    }

    public function createAssessment(Student $student, array $data, User $counselor): CareerAssessment
    {
        return DB::transaction(function () use ($student, $data, $counselor): CareerAssessment {
            $recommendation = $this->generateStreamRecommendation($data['scores'] ?? []);

            $assessment = CareerAssessment::query()->create([
                'student_id' => $student->id,
                'counselor_id' => $counselor->id,
                'session' => $this->careerCounselorService->currentSession(),
                'assessment_date' => $data['assessment_date'],
                'title' => $data['title'] ?? null,
                'overall_summary' => $data['overall_summary'] ?? null,
                'recommended_stream' => $data['recommended_stream'] ?? $recommendation['recommended_stream'],
                'alternative_stream' => $data['alternative_stream'] ?? $recommendation['alternative_stream'],
                'suggested_subjects' => $data['suggested_subjects'] ?? null,
                'created_by' => $counselor->id,
                'updated_by' => $counselor->id,
            ]);

            $this->syncScores($assessment, $data['scores'] ?? [], $data['remarks'] ?? []);

            return $assessment->fresh(['student.classRoom', 'counselor', 'scores']);
        });
    }

    public function updateAssessment(CareerAssessment $assessment, array $data, User $counselor): CareerAssessment
    {
        return DB::transaction(function () use ($assessment, $data, $counselor): CareerAssessment {
            $recommendation = $this->generateStreamRecommendation($data['scores'] ?? []);
            $assessment->update([
                'assessment_date' => $data['assessment_date'],
                'title' => $data['title'] ?? null,
                'overall_summary' => $data['overall_summary'] ?? null,
                'recommended_stream' => $data['recommended_stream'] ?? $recommendation['recommended_stream'],
                'alternative_stream' => $data['alternative_stream'] ?? $recommendation['alternative_stream'],
                'suggested_subjects' => $data['suggested_subjects'] ?? null,
                'updated_by' => $counselor->id,
            ]);
            $this->syncScores($assessment, $data['scores'] ?? [], $data['remarks'] ?? []);

            return $assessment->fresh(['student.classRoom', 'counselor', 'scores']);
        });
    }

    public function generateStreamRecommendation(array $scores): array
    {
        $score = fn (string $key): int => (int) ($scores[$key] ?? 0);
        $recommended = 'Arts';
        $alternative = 'Teaching';

        if ($score('medical_interest') >= 70 && $score('science_interest') >= 60) {
            [$recommended, $alternative] = ['Pre-Medical', 'Teaching'];
        } elseif ($score('math_ability') >= 65 && $score('science_interest') >= 60 && $score('problem_solving') >= 60) {
            [$recommended, $alternative] = ['Pre-Engineering', 'Computer Science'];
        } elseif ($score('technical_interest') >= 65 && $score('math_ability') >= 55 && $score('problem_solving') >= 55) {
            [$recommended, $alternative] = ['Computer Science', 'Technical/Vocational'];
        } elseif ($score('business_interest') >= 65 && $score('communication_skills') >= 55) {
            [$recommended, $alternative] = ['Commerce', 'Law'];
        } elseif ($score('creativity') >= 65 && $score('communication_skills') >= 55) {
            [$recommended, $alternative] = ['Arts', 'Teaching'];
        } elseif ($score('leadership') >= 65 && $score('communication_skills') >= 60) {
            [$recommended, $alternative] = ['Law', 'Civil Services'];
        } elseif ($score('social_work_interest') >= 65 && $score('communication_skills') >= 55) {
            [$recommended, $alternative] = ['Teaching', 'Social Work'];
        } elseif ($score('technical_interest') >= 65) {
            [$recommended, $alternative] = ['Technical/Vocational', 'Computer Science'];
        }

        return ['recommended_stream' => $recommended, 'alternative_stream' => $alternative];
    }

    public function getStudentAssessments(Student $student): Collection
    {
        return $student->careerAssessments()->with('scores', 'counselor')->latest('assessment_date')->get();
    }

    public function getGradeWiseSummary(array $filters = []): Collection
    {
        return CareerAssessment::query()
            ->with('student.classRoom')
            ->when($filters['session'] ?? null, fn (Builder $query, string $session) => $query->where('session', $session))
            ->get()
            ->groupBy(fn (CareerAssessment $assessment): string => trim(($assessment->student?->classRoom?->name ?? 'Unassigned').' '.($assessment->student?->classRoom?->section ?? '')))
            ->map(fn (Collection $rows, string $className): array => ['class_name' => $className, 'total' => $rows->count()])
            ->values();
    }

    private function syncScores(CareerAssessment $assessment, array $scores, array $remarks): void
    {
        foreach (self::CATEGORIES as $category) {
            CareerAssessmentScore::query()->updateOrCreate(
                ['career_assessment_id' => $assessment->id, 'category' => $category],
                ['score' => (int) ($scores[$category] ?? 0), 'remarks' => $remarks[$category] ?? null]
            );
        }
    }
}
