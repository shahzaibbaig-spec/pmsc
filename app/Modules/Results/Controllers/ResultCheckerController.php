<?php

namespace App\Modules\Results\Controllers;

use App\Http\Controllers\Controller;
use App\Models\{Mark, SchoolClass, StudentResult};
use App\Modules\Exams\Enums\ExamType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ResultCheckerController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'class_id' => ['nullable', 'integer', 'exists:school_classes,id'],
            'session' => ['nullable', 'string', 'max:20'],
            'exam_type' => ['nullable', Rule::in(array_column(ExamType::options(), 'value'))],
            'student' => ['nullable', 'string', 'max:150'],
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
        if (! empty($filters['student'])) {
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
