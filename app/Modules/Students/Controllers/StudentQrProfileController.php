<?php

namespace App\Modules\Students\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\FeeChallan;
use App\Models\Student;
use App\Models\StudentAttendance;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class StudentQrProfileController extends Controller
{
    public function show(Request $request, string $code): View
    {
        $student = $this->resolveStudentByCode($code);

        $this->ensureValidQrToken($request, $student);

        [$attendanceStats, $attendanceSource] = $this->attendanceSummary((int) $student->id);
        $feeSummary = $this->feeSummary((int) $student->id);

        return view('modules.students.qr-profile', [
            'student' => $student,
            'attendanceStats' => $attendanceStats,
            'attendanceSource' => $attendanceSource,
            'feeSummary' => $feeSummary,
        ]);
    }

    private function resolveStudentByCode(string $code): Student
    {
        $trimmedCode = trim($code);
        $columns = ['id', 'student_id', 'name', 'father_name', 'class_id', 'photo_path', 'status'];

        if ($this->hasQrTokenColumn()) {
            $columns[] = 'qr_token';
        }

        return Student::query()
            ->with('classRoom:id,name,section')
            ->where(function (Builder $query) use ($trimmedCode): void {
                $query->where('student_id', $trimmedCode);

                if (ctype_digit($trimmedCode)) {
                    $query->orWhere('id', (int) $trimmedCode);
                }
            })
            ->firstOrFail($columns);
    }

    private function ensureValidQrToken(Request $request, Student $student): void
    {
        if (! $this->hasQrTokenColumn()) {
            return;
        }

        $expectedToken = trim((string) ($student->getAttribute('qr_token') ?? ''));
        if ($expectedToken === '') {
            return;
        }

        $providedToken = trim((string) $request->query('token', ''));
        if ($providedToken === '' || ! hash_equals($expectedToken, $providedToken)) {
            abort(403, 'Invalid QR token.');
        }
    }

    /**
     * @return array{0: array{total:int,present:int,absent:int,leave:int,attendance_percentage:float}, 1: string}
     */
    private function attendanceSummary(int $studentId): array
    {
        $stats = $this->attendanceAggregate(
            Attendance::query()->where('student_id', $studentId)
        );
        $source = 'attendance';

        if ($stats['total'] === 0) {
            $stats = $this->attendanceAggregate(
                StudentAttendance::query()->where('student_id', $studentId)
            );
            $source = 'student_attendance';
        }

        $attendancePercentage = $stats['total'] > 0
            ? round(($stats['present'] / $stats['total']) * 100, 2)
            : 0.0;

        $stats['attendance_percentage'] = $attendancePercentage;

        return [$stats, $source];
    }

    /**
     * @param Builder<\Illuminate\Database\Eloquent\Model> $query
     * @return array{total:int,present:int,absent:int,leave:int}
     */
    private function attendanceAggregate(Builder $query): array
    {
        $stats = $query
            ->selectRaw(
                "COUNT(*) as total_count,
                SUM(CASE WHEN lower(status) IN ('present', 'p') THEN 1 ELSE 0 END) as present_count,
                SUM(CASE WHEN lower(status) IN ('absent', 'a') THEN 1 ELSE 0 END) as absent_count,
                SUM(CASE WHEN lower(status) IN ('leave', 'l') THEN 1 ELSE 0 END) as leave_count"
            )
            ->first();

        return [
            'total' => (int) ($stats?->total_count ?? 0),
            'present' => (int) ($stats?->present_count ?? 0),
            'absent' => (int) ($stats?->absent_count ?? 0),
            'leave' => (int) ($stats?->leave_count ?? 0),
        ];
    }

    /**
     * @return array{
     *   total_billed:float,
     *   total_paid:float,
     *   due_amount:float,
     *   pending_challans:int,
     *   overdue_challans:int,
     *   has_challans:bool,
     *   status_label:string,
     *   status_tone:string
     * }
     */
    private function feeSummary(int $studentId): array
    {
        $challans = FeeChallan::query()
            ->where('student_id', $studentId)
            ->withSum('payments as paid_total', 'amount_paid')
            ->orderByDesc('due_date')
            ->orderByDesc('id')
            ->get([
                'id',
                'issue_date',
                'due_date',
                'total_amount',
                'status',
            ]);

        $today = now()->startOfDay();
        $totals = $challans->reduce(function (array $carry, FeeChallan $challan) use ($today): array {
            $totalAmount = round((float) $challan->total_amount, 2);
            $paidAmount = min(round((float) ($challan->paid_total ?? 0), 2), $totalAmount);
            $dueAmount = round(max($totalAmount - $paidAmount, 0), 2);

            $isOverdue = $dueAmount > 0
                && $challan->due_date !== null
                && $challan->due_date->copy()->startOfDay()->lt($today);

            $carry['total_billed'] += $totalAmount;
            $carry['total_paid'] += $paidAmount;
            $carry['due_amount'] += $dueAmount;
            $carry['pending_challans'] += $dueAmount > 0 ? 1 : 0;
            $carry['overdue_challans'] += $isOverdue ? 1 : 0;

            return $carry;
        }, [
            'total_billed' => 0.0,
            'total_paid' => 0.0,
            'due_amount' => 0.0,
            'pending_challans' => 0,
            'overdue_challans' => 0,
        ]);

        $dueAmount = round((float) $totals['due_amount'], 2);
        $statusLabel = $dueAmount <= 0
            ? 'No Due'
            : ((int) $totals['overdue_challans'] > 0 ? 'Overdue' : 'Pending');

        $statusTone = $dueAmount <= 0
            ? 'success'
            : ((int) $totals['overdue_challans'] > 0 ? 'danger' : 'warning');

        return [
            'total_billed' => round((float) $totals['total_billed'], 2),
            'total_paid' => round((float) $totals['total_paid'], 2),
            'due_amount' => $dueAmount,
            'pending_challans' => (int) $totals['pending_challans'],
            'overdue_challans' => (int) $totals['overdue_challans'],
            'has_challans' => $challans->isNotEmpty(),
            'status_label' => $statusLabel,
            'status_tone' => $statusTone,
        ];
    }

    private function hasQrTokenColumn(): bool
    {
        static $hasColumn = null;

        if ($hasColumn !== null) {
            return $hasColumn;
        }

        $hasColumn = Schema::hasTable('students') && Schema::hasColumn('students', 'qr_token');

        return $hasColumn;
    }
}
