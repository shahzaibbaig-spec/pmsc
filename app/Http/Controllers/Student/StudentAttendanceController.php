<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\StudentAttendance;
use App\Services\StudentUserResolverService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentAttendanceController extends Controller
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
            return view('student.attendance.index', [
                'student' => null,
                'records' => collect(),
                'source' => 'attendance',
                'summary' => ['total' => 0, 'present' => 0, 'absent' => 0, 'leave' => 0, 'attendance_percentage' => 0.0],
                'filters' => $filters,
                'message' => 'Student profile is not linked to this login yet. Please ask Admin to align student name or email with a student record.',
            ]);
        }

        $modernQuery = Attendance::query()
            ->where('student_id', (int) $student->id)
            ->when(($filters['status'] ?? null) !== null && trim((string) $filters['status']) !== '', function ($query) use ($filters): void {
                $query->whereRaw('LOWER(status) = ?', [mb_strtolower(trim((string) $filters['status']))]);
            })
            ->when(($filters['date_from'] ?? null) !== null, fn ($query) => $query->whereDate('date', '>=', (string) $filters['date_from']))
            ->when(($filters['date_to'] ?? null) !== null, fn ($query) => $query->whereDate('date', '<=', (string) $filters['date_to']));

        $source = 'attendance';
        $summary = ['total' => 0, 'present' => 0, 'absent' => 0, 'leave' => 0, 'attendance_percentage' => 0.0];

        if ($modernQuery->exists()) {
            $records = (clone $modernQuery)
                ->orderByDesc('date')
                ->orderByDesc('id')
                ->paginate(30)
                ->withQueryString();

            $stats = (clone $modernQuery)->selectRaw(
                "COUNT(*) as total_count,
                SUM(CASE WHEN lower(status) IN ('present', 'p') THEN 1 ELSE 0 END) as present_count,
                SUM(CASE WHEN lower(status) IN ('absent', 'a') THEN 1 ELSE 0 END) as absent_count,
                SUM(CASE WHEN lower(status) IN ('leave', 'l') THEN 1 ELSE 0 END) as leave_count"
            )->first();
        } else {
            $source = 'student_attendance';
            $legacyQuery = StudentAttendance::query()
                ->where('student_id', (int) $student->id)
                ->when(($filters['status'] ?? null) !== null && trim((string) $filters['status']) !== '', function ($query) use ($filters): void {
                    $query->whereRaw('LOWER(status) = ?', [mb_strtolower(trim((string) $filters['status']))]);
                })
                ->when(($filters['date_from'] ?? null) !== null, fn ($query) => $query->whereDate('date', '>=', (string) $filters['date_from']))
                ->when(($filters['date_to'] ?? null) !== null, fn ($query) => $query->whereDate('date', '<=', (string) $filters['date_to']));

            $records = (clone $legacyQuery)
                ->orderByDesc('date')
                ->orderByDesc('id')
                ->paginate(30)
                ->withQueryString();

            $stats = (clone $legacyQuery)->selectRaw(
                "COUNT(*) as total_count,
                SUM(CASE WHEN lower(status) IN ('present', 'p') THEN 1 ELSE 0 END) as present_count,
                SUM(CASE WHEN lower(status) IN ('absent', 'a') THEN 1 ELSE 0 END) as absent_count,
                SUM(CASE WHEN lower(status) IN ('leave', 'l') THEN 1 ELSE 0 END) as leave_count"
            )->first();
        }

        $total = (int) ($stats?->total_count ?? 0);
        $present = (int) ($stats?->present_count ?? 0);
        $absent = (int) ($stats?->absent_count ?? 0);
        $leave = (int) ($stats?->leave_count ?? 0);
        $summary = [
            'total' => $total,
            'present' => $present,
            'absent' => $absent,
            'leave' => $leave,
            'attendance_percentage' => $total > 0 ? round(($present / $total) * 100, 2) : 0.0,
        ];

        return view('student.attendance.index', [
            'student' => $student,
            'records' => $records,
            'source' => $source,
            'summary' => $summary,
            'filters' => $filters,
            'message' => null,
        ]);
    }
}
