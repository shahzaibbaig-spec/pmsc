<?php

namespace App\Modules\Results\Services;

use App\Models\ReportComment;
use App\Models\Student;
use App\Models\StudentLearningProfile;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class ReportCommentService
{
    public function generateCommentsForClass(int $classId, string $session, string $examType, ?int $generatedBy = null): array
    {
        $resolvedGeneratedBy = $this->resolveUserId($generatedBy);

        $students = Student::query()
            ->where('class_id', $classId)
            ->where('status', 'active')
            ->orderBy('name')
            ->orderBy('student_id')
            ->get(['id', 'name', 'student_id']);

        if ($students->isEmpty()) {
            return [
                'students_count' => 0,
                'comments_generated' => 0,
            ];
        }

        $profiles = StudentLearningProfile::query()
            ->where('session', $session)
            ->whereIn('student_id', $students->pluck('id')->all())
            ->get()
            ->keyBy('student_id');

        $commentsGenerated = 0;
        foreach ($students as $student) {
            $profile = $profiles->get((int) $student->id);
            $autoComment = $this->buildAutoComment($student, $profile);

            $comment = ReportComment::query()->firstOrNew([
                'student_id' => (int) $student->id,
                'session' => $session,
                'exam_type' => $examType,
            ]);

            $wasEdited = (bool) ($comment->is_edited ?? false);
            $comment->auto_comment = $autoComment;
            if (! $wasEdited || (string) ($comment->final_comment ?? '') === '') {
                $comment->final_comment = $autoComment;
                $comment->is_edited = false;
            }
            $this->persistCommentWithGeneratorFallback($comment, $resolvedGeneratedBy);

            $commentsGenerated++;
        }

        return [
            'students_count' => $students->count(),
            'comments_generated' => $commentsGenerated,
        ];
    }

    public function saveFinalComment(
        int $studentId,
        string $session,
        string $examType,
        string $finalComment,
        ?int $generatedBy = null
    ): ReportComment {
        $resolvedGeneratedBy = $this->resolveUserId($generatedBy);

        $student = Student::query()->findOrFail($studentId, ['id', 'name', 'student_id']);
        $profile = StudentLearningProfile::query()
            ->where('student_id', $studentId)
            ->where('session', $session)
            ->first();

        $comment = ReportComment::query()->firstOrNew([
            'student_id' => $studentId,
            'session' => $session,
            'exam_type' => $examType,
        ]);

        $autoComment = (string) ($comment->auto_comment ?: $this->buildAutoComment($student, $profile));
        $finalComment = trim($finalComment);

        $comment->auto_comment = $autoComment;
        $comment->final_comment = $finalComment !== '' ? $finalComment : $autoComment;
        $comment->is_edited = trim((string) $comment->final_comment) !== trim($autoComment);
        $this->persistCommentWithGeneratorFallback($comment, $resolvedGeneratedBy);

        return $comment;
    }

    private function persistCommentWithGeneratorFallback(ReportComment $comment, ?int $generatedBy): void
    {
        try {
            $comment->generated_by = $generatedBy;
            $comment->save();
        } catch (QueryException $exception) {
            if ($generatedBy !== null && $this->isGeneratedByForeignKeyViolation($exception)) {
                Log::warning('User FK failed during report comment save; retrying without generator reference.', [
                    'user_id' => $generatedBy,
                    'sql_state' => $exception->errorInfo[0] ?? null,
                    'driver_code' => $exception->errorInfo[1] ?? null,
                    'message' => $exception->getMessage(),
                ]);

                $comment->generated_by = null;
                $comment->save();

                return;
            }

            throw $exception;
        }
    }

    private function isGeneratedByForeignKeyViolation(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? null;
        $driverCode = (int) ($exception->errorInfo[1] ?? 0);
        if ($sqlState !== '23000' || $driverCode !== 1452) {
            return false;
        }

        $message = strtolower($exception->getMessage());

        return str_contains($message, 'references `users` (`id`)')
            && str_contains($message, 'report_comments')
            && str_contains($message, 'generated_by');
    }

    private function resolveUserId(?int $userId): ?int
    {
        if ($userId === null || $userId <= 0) {
            return null;
        }

        return User::query()->useWritePdo()->whereKey($userId)->exists()
            ? $userId
            : null;
    }

    private function buildAutoComment(Student $student, ?StudentLearningProfile $profile): string
    {
        $studentName = (string) $student->name;
        $overallAverage = (float) ($profile?->overall_average ?? 0.0);
        $attendance = (float) ($profile?->attendance_percentage ?? 0.0);
        $strengths = (string) ($profile?->strengths ?? '');
        $supportAreas = (string) ($profile?->support_areas ?? '');
        $bestAptitude = (string) ($profile?->best_aptitude ?? 'Undetermined');
        $learningPattern = (string) ($profile?->learning_pattern ?? '');

        $subjectScores = is_array($profile?->subject_scores) ? $profile->subject_scores : [];
        $meta = is_array($subjectScores['meta'] ?? null) ? $subjectScores['meta'] : [];
        $trend = (string) ($meta['trend'] ?? 'stable');
        $consistency = (string) ($meta['consistency'] ?? 'consistent');
        $failedCount = (int) ($meta['failed_subjects_count'] ?? 0);

        $template = $this->selectTemplate($overallAverage, $trend, $consistency, $failedCount);

        return match ($template) {
            'strong_performer' => $this->strongPerformerComment(
                $studentName,
                $strengths,
                $attendance,
                $bestAptitude
            ),
            'improving_performer' => $this->improvingPerformerComment(
                $studentName,
                $strengths,
                $attendance,
                $learningPattern
            ),
            'inconsistent_performer' => $this->inconsistentPerformerComment(
                $studentName,
                $supportAreas,
                $strengths
            ),
            default => $this->needsSupportComment(
                $studentName,
                $supportAreas,
                $learningPattern
            ),
        };
    }

    private function selectTemplate(float $overallAverage, string $trend, string $consistency, int $failedCount): string
    {
        if ($failedCount >= 2 || $overallAverage < 60) {
            return 'needs_support';
        }

        if ($consistency === 'inconsistent') {
            return 'inconsistent_performer';
        }

        if ($trend === 'improving' || ($overallAverage >= 60 && $overallAverage < 75)) {
            return 'improving_performer';
        }

        return 'strong_performer';
    }

    private function strongPerformerComment(
        string $studentName,
        string $strengths,
        float $attendance,
        string $bestAptitude
    ): string {
        $strengthClause = $strengths !== ''
            ? 'Key strengths are visible in '.$strengths.'.'
            : 'Performance is strong across assessed subjects.';

        return $studentName.' demonstrates strong academic performance this term. '
            .$strengthClause.' '
            .'Attendance remains at '.number_format($attendance, 2).'%, and the student shows clear aptitude in '.$bestAptitude.'. '
            .'Continued enrichment and advanced practice are recommended to sustain this level.';
    }

    private function improvingPerformerComment(
        string $studentName,
        string $strengths,
        float $attendance,
        string $learningPattern
    ): string {
        $strengthClause = $strengths !== ''
            ? 'Positive progress is especially visible in '.$strengths.'.'
            : 'The student is showing measurable progress across assessments.';

        $patternClause = $learningPattern !== '' ? $learningPattern : 'The current learning trajectory is encouraging.';

        return $studentName.' is showing a positive improvement trend this term. '
            .$strengthClause.' '
            .'Attendance is '.number_format($attendance, 2).'%, and '.$patternClause.' '
            .'Consistent revision and regular class participation should help maintain this momentum.';
    }

    private function inconsistentPerformerComment(
        string $studentName,
        string $supportAreas,
        string $strengths
    ): string {
        $supportClause = $supportAreas !== ''
            ? 'Focused attention is required in '.$supportAreas.'.'
            : 'Focused attention is required in selected core topics.';
        $strengthClause = $strengths !== ''
            ? 'The student continues to demonstrate potential in '.$strengths.'.'
            : 'The student demonstrates clear potential.';

        return $studentName.' demonstrates capability, but results vary across assessments. '
            .$supportClause.' '
            .$strengthClause.' '
            .'A structured weekly revision plan and regular formative feedback are recommended to improve consistency.';
    }

    private function needsSupportComment(
        string $studentName,
        string $supportAreas,
        string $learningPattern
    ): string {
        $supportClause = $supportAreas !== ''
            ? 'Priority support is needed in '.$supportAreas.'.'
            : 'Priority support is needed across key academic areas.';
        $patternClause = $learningPattern !== ''
            ? $learningPattern
            : 'Additional support and close progress monitoring are recommended.';

        return $studentName.' requires additional academic support this term. '
            .$supportClause.' '
            .$patternClause.' '
            .'Regular attendance, guided practice tasks, and parent coordination should be maintained for steady improvement.';
    }
}
