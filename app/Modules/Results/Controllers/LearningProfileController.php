<?php

namespace App\Modules\Results\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Modules\Exams\Enums\ExamType;
use App\Modules\Results\Services\LearningProfileService;
use App\Modules\Results\Services\ReportCommentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LearningProfileController extends Controller
{
    public function __construct(
        private readonly LearningProfileService $learningProfileService,
        private readonly ReportCommentService $reportCommentService,
    ) {
    }

    public function index(Request $request): View
    {
        [$isPrincipal, $teacher, $classes] = $this->resolveAccess($request);

        $sessions = $this->availableSessions();
        $examTypes = ExamType::options();
        $examTypeValues = array_column($examTypes, 'value');

        $selectedSession = $request->filled('session') && in_array((string) $request->input('session'), $sessions, true)
            ? (string) $request->input('session')
            : ($sessions[0] ?? now()->year.'-'.(now()->year + 1));

        $selectedExamType = $request->filled('exam_type') && in_array((string) $request->input('exam_type'), $examTypeValues, true)
            ? (string) $request->input('exam_type')
            : (in_array('final_term', $examTypeValues, true) ? 'final_term' : ($examTypeValues[0] ?? 'first_term'));

        $selectedClassId = $request->filled('class_id') ? (int) $request->input('class_id') : null;
        if ($selectedClassId !== null && ! $classes->contains('id', $selectedClassId)) {
            abort(403, 'You are not allowed to access this class.');
        }

        if ($selectedClassId === null && $classes->isNotEmpty()) {
            $selectedClassId = (int) $classes->first()->id;
        }

        $selectedClass = $selectedClassId !== null
            ? $classes->firstWhere('id', $selectedClassId)
            : null;

        if (
            $selectedClass !== null
            && ! $isPrincipal
            && (int) ($teacher?->id ?? 0) !== (int) ($selectedClass->class_teacher_id ?? 0)
        ) {
            abort(403, 'You can access this page only for classes where you are class teacher.');
        }

        $rows = $selectedClassId !== null
            ? $this->learningProfileService->tableRowsForClass((int) $selectedClassId, $selectedSession, $selectedExamType)
            : [];

        return view('modules.results.learning-profiles.index', [
            'classes' => $classes,
            'sessions' => $sessions,
            'examTypes' => $examTypes,
            'selectedSession' => $selectedSession,
            'selectedExamType' => $selectedExamType,
            'selectedClassId' => $selectedClassId,
            'rows' => $rows,
        ]);
    }

    public function generate(Request $request): RedirectResponse
    {
        $examTypeValues = array_map(
            fn (array $row): string => (string) $row['value'],
            ExamType::options()
        );

        $validated = $request->validate([
            'session' => ['required', 'string', 'max:20'],
            'class_id' => ['required', 'integer', 'exists:school_classes,id'],
            'exam_type' => ['required', 'string', 'in:'.implode(',', $examTypeValues)],
        ]);

        [, , $classes] = $this->resolveAccess($request);
        $classId = (int) $validated['class_id'];
        if (! $classes->contains('id', $classId)) {
            abort(403, 'You are not allowed to generate profiles for this class.');
        }

        $profileSummary = $this->learningProfileService->generateProfilesForClass(
            $classId,
            (string) $validated['session']
        );
        $commentSummary = $this->reportCommentService->generateCommentsForClass(
            $classId,
            (string) $validated['session'],
            (string) $validated['exam_type'],
            auth()->id()
        );

        return redirect()
            ->route('results.learning-profiles', [
                'session' => (string) $validated['session'],
                'class_id' => $classId,
                'exam_type' => (string) $validated['exam_type'],
            ])
            ->with(
                'status',
                sprintf(
                    'Learning profiles generated for %d/%d students and %d comments prepared.',
                    (int) $profileSummary['profiles_generated'],
                    (int) $profileSummary['students_count'],
                    (int) $commentSummary['comments_generated']
                )
            );
    }

    public function saveComment(Request $request): JsonResponse
    {
        $examTypeValues = array_map(
            fn (array $row): string => (string) $row['value'],
            ExamType::options()
        );

        $validated = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'session' => ['required', 'string', 'max:20'],
            'exam_type' => ['required', 'string', 'in:'.implode(',', $examTypeValues)],
            'final_comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $student = Student::query()->findOrFail((int) $validated['student_id'], ['id', 'class_id']);
        [, , $classes] = $this->resolveAccess($request);
        if (! $classes->contains('id', (int) $student->class_id)) {
            abort(403, 'You are not allowed to edit this comment.');
        }

        $comment = $this->reportCommentService->saveFinalComment(
            (int) $validated['student_id'],
            (string) $validated['session'],
            (string) $validated['exam_type'],
            (string) ($validated['final_comment'] ?? ''),
            auth()->id()
        );

        return response()->json([
            'message' => 'Final comment saved successfully.',
            'comment_status' => (bool) $comment->is_edited ? 'Edited' : 'Auto',
            'is_edited' => (bool) $comment->is_edited,
            'final_comment' => (string) ($comment->final_comment ?? ''),
        ]);
    }

    private function resolveAccess(Request $request): array
    {
        $user = $request->user();
        $isPrincipal = $user?->hasRole('Principal') ?? false;
        $isTeacher = $user?->hasRole('Teacher') ?? false;

        if (! $isPrincipal && ! $isTeacher) {
            abort(403, 'You are not authorized to access this module.');
        }

        $teacher = null;
        if ($isTeacher) {
            $teacher = Teacher::query()->where('user_id', (int) $user->id)->first();
            if (! $teacher) {
                abort(403, 'Teacher profile not found.');
            }
        }

        $classes = $this->classesForUser($isPrincipal, $teacher?->id);
        if (! $isPrincipal && $classes->isEmpty()) {
            abort(403, 'Only class teachers can access this module.');
        }

        return [$isPrincipal, $teacher, $classes];
    }

    private function classesForUser(bool $isPrincipal, ?int $teacherId)
    {
        $query = SchoolClass::query()
            ->orderBy('name')
            ->orderBy('section');

        if (! $isPrincipal && $teacherId !== null) {
            $query->where('class_teacher_id', (int) $teacherId);
        }

        return $query->get(['id', 'name', 'section', 'class_teacher_id']);
    }

    private function availableSessions(): array
    {
        $sessions = Exam::query()
            ->select('session')
            ->distinct()
            ->orderByDesc('session')
            ->pluck('session')
            ->values()
            ->all();

        if ($sessions !== []) {
            return $sessions;
        }

        $now = now();
        $startYear = $now->month >= 7 ? $now->year : ($now->year - 1);
        $fallback = [];
        for ($year = $startYear - 1; $year <= $startYear + 3; $year++) {
            $fallback[] = $year.'-'.($year + 1);
        }

        return array_reverse($fallback);
    }
}

