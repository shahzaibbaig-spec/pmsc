<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\DisciplineComplaint;
use App\Services\StudentUserResolverService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentDisciplineController extends Controller
{
    public function __construct(
        private readonly StudentUserResolverService $studentUserResolver
    ) {
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', 'string', 'max:20'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $student = $request->user() ? $this->studentUserResolver->resolveForUser($request->user()) : null;
        if (! $student) {
            return view('student.discipline.index', [
                'student' => null,
                'reports' => collect(),
                'summary' => ['total' => 0, 'open' => 0, 'resolved' => 0],
                'filters' => $filters,
                'message' => 'Student profile is not linked to this login yet. Please ask Admin to align student name or email with a student record.',
            ]);
        }

        $query = DisciplineComplaint::query()
            ->where('student_id', (int) $student->id)
            ->when(($filters['status'] ?? null) !== null && trim((string) $filters['status']) !== '', function ($builder) use ($filters): void {
                $builder->whereRaw('LOWER(status) = ?', [mb_strtolower(trim((string) $filters['status']))]);
            })
            ->when(($filters['date_from'] ?? null) !== null, fn ($builder) => $builder->whereDate('complaint_date', '>=', (string) $filters['date_from']))
            ->when(($filters['date_to'] ?? null) !== null, fn ($builder) => $builder->whereDate('complaint_date', '<=', (string) $filters['date_to']));

        $reports = (clone $query)
            ->orderByDesc('complaint_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $allRows = (clone $query)->get(['id', 'status']);
        $openCount = $allRows
            ->filter(fn (DisciplineComplaint $row): bool => in_array(mb_strtolower((string) $row->status), ['open', 'pending'], true))
            ->count();

        return view('student.discipline.index', [
            'student' => $student,
            'reports' => $reports,
            'summary' => [
                'total' => $allRows->count(),
                'open' => $openCount,
                'resolved' => max($allRows->count() - $openCount, 0),
            ],
            'filters' => $filters,
            'message' => null,
        ]);
    }
}
