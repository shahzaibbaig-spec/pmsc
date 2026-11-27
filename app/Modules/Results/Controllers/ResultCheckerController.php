<?php

namespace App\Modules\Results\Controllers;

use App\Http\Controllers\Controller;
use App\Models\{Mark, SchoolClass, Student, StudentResult};
use App\Modules\Exams\Enums\ExamType;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ResultCheckerController extends Controller
{
    public function card(string $source, int $record, \App\Modules\Results\Services\ResultCheckerCardService $service): View
    {
        return view('modules.results.checker-card', $service->build($source, $record));
    }

    public function students(Request $request): JsonResponse
    {
        $data = $request->validate(['q' => ['nullable', 'string', 'max:150']]);
        $term = trim($data['q'] ?? '');
        if (mb_strlen($term) < 2) {
            return response()->json(['students' => []]);
        }

        $students = Student::query()->with('classRoom:id,name,section')
            ->where(fn ($query) => $query->where('name', 'like', '%'.$term.'%')
                ->orWhere('student_id', 'like', '%'.$term.'%'))
            ->orderBy('name')->orderBy('id')->limit(20)
            ->get(['id', 'name', 'student_id', 'class_id'])
            ->map(fn (Student $student) => [
                'id' => $student->id,
                'name' => $student->name,
                'student_id' => $student->student_id,
                'class' => trim(($student->classRoom?->name ?? '').' '.($student->classRoom?->section ?? '')),
            ]);

        return response()->json(['students' => $students]);
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'class_id' => ['nullable', 'integer', 'exists:school_classes,id'],
            'session' => ['nullable', 'string', 'max:20'],
            'exam_type' => ['nullable', Rule::in(array_column(ExamType::options(), 'value'))],
            'student' => ['nullable', 'string', 'max:150'],
            'student_id' => ['nullable', 'integer', 'exists:students,id'],
            'source' => ['nullable', 'in:marks,published'],
        ]);
        $published = ($filters['source'] ?? 'marks') === 'published';
        $query = $published
            ? StudentResult::query()->with(['student', 'classRoom', 'subject', 'exam'])
            : Mark::query()->with(['student', 'exam.classRoom', 'exam.subject']);

        if (! empty($filters['session'])) {
            $query->where('session', $filters['session']);
        }
        if (! empty($filters['class_id'])) {
            $published
                ? $query->where('class_id', $filters['class_id'])
                : $query->whereHas('exam', fn ($exam) => $exam->where('class_id', $filters['class_id']));
        }
        if (! empty($filters['exam_type'])) {
            $query->whereHas('exam', fn ($exam) => $exam->where('exam_type', $filters['exam_type']));
        }
        if (! empty($filters['student_id'])) {
            $query->where('student_id', $filters['student_id']);
        } elseif (! empty($filters['student'])) {
            $query->whereHas('student', fn ($student) => $student
                ->where(fn ($search) => $search->where('name', 'like', '%'.$filters['student'].'%')
                    ->orWhere('student_id', 'like', '%'.$filters['student'].'%')));
        }

        return view('modules.results.checker', [
            'filters' => $filters,
            'published' => $published,
            'records' => $query->orderByDesc('id')->paginate(50)->withQueryString(),
            'classes' => SchoolClass::query()->orderBy('name')->orderBy('section')->get(),
            'sessions' => Mark::query()->distinct()->pluck('session')
                ->merge(StudentResult::query()->distinct()->pluck('session'))
                ->filter()->unique()->sortDesc()->values(),
            'examTypes' => ExamType::options(),
        ]);
    }
}
