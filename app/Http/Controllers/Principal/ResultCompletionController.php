<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\TeacherAssignment;
use App\Modules\Exams\Enums\ExamType;
use App\Services\ResultCompletionService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ResultCompletionController extends Controller
{
    public function index(Request $request, ResultCompletionService $service): View
    {
        $filters = $request->validate([
            'session' => ['nullable', 'regex:/^\d{4}-\d{4}$/'],
            'exam_type' => ['nullable', Rule::in(array_column(ExamType::options(), 'value'))],
            'class_id' => ['nullable', 'integer', 'exists:school_classes,id'],
            'sequence_number' => ['nullable', 'integer', 'between:1,4'],
            'topic' => ['nullable', 'string', 'max:255'],
            'exam_date' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $start = now()->timezone('Asia/Karachi')->month >= 7 ? now()->timezone('Asia/Karachi')->year : now()->timezone('Asia/Karachi')->year - 1;
        $filters['session'] ??= $start.'-'.($start + 1);
        $filters['exam_type'] ??= 'first_term';
        $filters['sequence_number'] ??= 1;
        $filters['topic'] = preg_replace('/\s+/', ' ', trim($filters['topic'] ?? ''));
        $needsTestContext = $filters['exam_type'] === 'class_test' && ($filters['topic'] === '' || empty($filters['exam_date']));
        return view('principal.results.completion', [
            'filters' => $filters, 'needsTestContext' => $needsTestContext,
            'report' => $needsTestContext ? null : $service->report($filters),
            'sessions' => TeacherAssignment::distinct()->pluck('session')->push($filters['session'])->filter()->unique()->sortDesc()->values(),
            'examTypes' => ExamType::options(),
            'classes' => \App\Models\SchoolClass::orderBy('name')->orderBy('section')->get(),
        ]);
    }
}
