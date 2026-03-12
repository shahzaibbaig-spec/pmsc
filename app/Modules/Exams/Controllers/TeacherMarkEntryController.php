<?php

namespace App\Modules\Exams\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Mark;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Modules\Exams\Enums\ExamType;
use App\Modules\Exams\Services\TeacherMarkAuditService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class TeacherMarkEntryController extends Controller
{
    public function __construct(private readonly TeacherMarkAuditService $auditService)
    {
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'session' => ['nullable', 'string', 'max:20'],
            'class_id' => ['nullable', 'integer', 'exists:school_classes,id'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'exam_type' => ['nullable', 'string', 'in:'.implode(',', array_column(ExamType::options(), 'value'))],
            'student_name' => ['nullable', 'string', 'max:255'],
        ]);

        $teacher = $this->auditService->resolveTeacher((int) auth()->id());
        $examTypes = ExamType::options();
        $examTypeLabels = collect($examTypes)->pluck('label', 'value')->all();
        $emptyPaginator = Mark::query()->whereRaw('1 = 0')->paginate(15);

        if (! $teacher) {
            return view('modules.teacher.marks.index', [
                'entries' => $emptyPaginator,
                'filters' => $this->normalizedFilters($filters),
                'sessions' => collect(),
                'classes' => collect(),
                'subjects' => collect(),
                'examTypes' => $examTypes,
                'examTypeLabels' => $examTypeLabels,
                'profileError' => 'Teacher profile not found.',
            ]);
        }

        $entriesQuery = Mark::query()
            ->with([
                'student:id,student_id,name',
                'exam:id,class_id,subject_id,exam_type',
                'exam.classRoom:id,name,section',
                'exam.subject:id,name',
            ])
            ->where('teacher_id', $teacher->id)
            ->when(($filters['session'] ?? null) !== null && $filters['session'] !== '', function ($query) use ($filters): void {
                $query->where('session', (string) $filters['session']);
            })
            ->when(($filters['class_id'] ?? null) !== null && $filters['class_id'] !== '', function ($query) use ($filters): void {
                $query->whereHas('exam', function ($subQuery) use ($filters): void {
                    $subQuery->where('class_id', (int) $filters['class_id']);
                });
            })
            ->when(($filters['subject_id'] ?? null) !== null && $filters['subject_id'] !== '', function ($query) use ($filters): void {
                $query->whereHas('exam', function ($subQuery) use ($filters): void {
                    $subQuery->where('subject_id', (int) $filters['subject_id']);
                });
            })
            ->when(($filters['exam_type'] ?? null) !== null && $filters['exam_type'] !== '', function ($query) use ($filters): void {
                $query->whereHas('exam', function ($subQuery) use ($filters): void {
                    $subQuery->where('exam_type', (string) $filters['exam_type']);
                });
            })
            ->when(($filters['student_name'] ?? null) !== null && trim((string) $filters['student_name']) !== '', function ($query) use ($filters): void {
                $studentName = trim((string) $filters['student_name']);
                $query->whereHas('student', function ($subQuery) use ($studentName): void {
                    $subQuery->where('name', 'like', '%'.$studentName.'%');
                });
            })
            ->latest('created_at');

        $entries = $entriesQuery->paginate(15)->withQueryString();
        $entries->getCollection()->transform(function (Mark $mark): Mark {
            $mark->setAttribute('can_edit', $this->auditService->canEdit($mark));

            return $mark;
        });

        $baseExamQuery = Mark::query()
            ->join('exams', 'marks.exam_id', '=', 'exams.id')
            ->where('marks.teacher_id', $teacher->id);

        $classIds = (clone $baseExamQuery)
            ->select('exams.class_id')
            ->distinct()
            ->pluck('exams.class_id');

        $subjectIds = (clone $baseExamQuery)
            ->select('exams.subject_id')
            ->distinct()
            ->pluck('exams.subject_id');

        $sessions = Mark::query()
            ->where('teacher_id', $teacher->id)
            ->select('session')
            ->distinct()
            ->orderByDesc('session')
            ->pluck('session');

        $classes = SchoolClass::query()
            ->whereIn('id', $classIds)
            ->orderBy('name')
            ->orderBy('section')
            ->get(['id', 'name', 'section']);

        $subjects = Subject::query()
            ->whereIn('id', $subjectIds)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('modules.teacher.marks.index', [
            'entries' => $entries,
            'filters' => $this->normalizedFilters($filters),
            'sessions' => $sessions,
            'classes' => $classes,
            'subjects' => $subjects,
            'examTypes' => $examTypes,
            'examTypeLabels' => $examTypeLabels,
            'profileError' => null,
        ]);
    }

    public function edit(Mark $mark): View|RedirectResponse
    {
        try {
            $teacher = $this->auditService->resolveTeacherOrFail((int) auth()->id());
            if ((int) $mark->teacher_id !== (int) $teacher->id) {
                throw new AuthorizationException('You can edit only your own mark entries.');
            }
        } catch (AuthorizationException $exception) {
            abort(403, $exception->getMessage());
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('teacher.marks.entries.index')
                ->with('error', $exception->getMessage());
        }

        if (! $this->auditService->canEdit($mark)) {
            return redirect()
                ->route('teacher.marks.entries.index')
                ->with('error', 'Editing window has expired. You can edit marks only within 7 days of entry.');
        }

        $mark->load([
            'student:id,student_id,name',
            'exam:id,class_id,subject_id,exam_type',
            'exam.classRoom:id,name,section',
            'exam.subject:id,name',
        ]);

        return view('modules.teacher.marks.edit', [
            'mark' => $mark,
            'examTypeLabel' => $this->examTypeLabel($mark->exam?->exam_type),
        ]);
    }

    public function update(Request $request, Mark $mark): RedirectResponse
    {
        $validated = $request->validate([
            'obtained_marks' => ['required', 'integer', 'min:0'],
            'edit_reason' => ['required', 'string', 'max:1000'],
        ]);

        try {
            $this->auditService->updateMarkEntry(
                (int) auth()->id(),
                $mark,
                (int) $validated['obtained_marks'],
                trim((string) $validated['edit_reason'])
            );
        } catch (AuthorizationException $exception) {
            abort(403, $exception->getMessage());
        } catch (RuntimeException $exception) {
            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('teacher.marks.entries.index')
            ->with('status', 'Mark entry updated successfully.');
    }

    public function destroy(Request $request, Mark $mark): RedirectResponse
    {
        $validated = $request->validate([
            'edit_reason' => ['required', 'string', 'max:1000'],
        ]);

        try {
            $this->auditService->deleteMarkEntry(
                (int) auth()->id(),
                $mark,
                trim((string) $validated['edit_reason'])
            );
        } catch (AuthorizationException $exception) {
            abort(403, $exception->getMessage());
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('teacher.marks.entries.index')
            ->with('status', 'Mark entry deleted successfully.');
    }

    private function normalizedFilters(array $filters): array
    {
        return [
            'session' => $filters['session'] ?? '',
            'class_id' => $filters['class_id'] ?? '',
            'subject_id' => $filters['subject_id'] ?? '',
            'exam_type' => $filters['exam_type'] ?? '',
            'student_name' => $filters['student_name'] ?? '',
        ];
    }

    private function examTypeLabel(mixed $examType): string
    {
        if ($examType instanceof ExamType) {
            return $examType->label();
        }

        $raw = (string) $examType;
        $type = ExamType::tryFrom($raw);
        if ($type) {
            return $type->label();
        }

        return str_replace('_', ' ', ucfirst($raw));
    }
}
