<?php

namespace App\Modules\Students\Controllers;

use App\Models\Student;
use App\Models\FeeChallan;
use App\Modules\Fees\Services\FeeDefaulterService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class StudentDashboardController extends Controller
{
    public function __construct(private readonly FeeDefaulterService $feeDefaulterService)
    {
    }

    public function __invoke(): View
    {
        $user = auth()->user();
        $student = $user ? $this->resolveStudentForUser((string) $user->name, (string) $user->email) : null;

        if (! $student) {
            return view('modules.student.dashboard', [
                'student' => null,
                'feeStatus' => null,
                'latestChallan' => null,
                'feeMessage' => 'Student profile is not linked to this login yet. Please ask Admin to align student name or email with a student record.',
            ]);
        }

        if (! Schema::hasTable('fee_defaulters')) {
            return view('modules.student.dashboard', [
                'student' => $student,
                'feeStatus' => null,
                'latestChallan' => null,
                'feeMessage' => 'Fee status is currently unavailable.',
            ]);
        }

        $session = $this->feeDefaulterService->sessionFromDate();
        $defaulter = $this->feeDefaulterService->syncStudentForSession((int) $student->id, $session);
        $breakdown = $this->feeDefaulterService->dueBreakdownForStudent((int) $student->id, $session);
        $totalDue = round((float) ($breakdown['total_due'] ?? 0), 2);
        $latestChallan = $this->latestChallan((int) $student->id, $session);

        return view('modules.student.dashboard', [
            'student' => $student,
            'feeStatus' => [
                'session' => $session,
                'total_due' => $totalDue,
                'is_defaulter' => (bool) ($defaulter?->is_active ?? false) && $totalDue > 0,
                'oldest_due_date' => $breakdown['oldest_due_date'] ?? null,
            ],
            'latestChallan' => $latestChallan,
            'feeMessage' => null,
        ]);
    }

    private function latestChallan(int $studentId, string $session): ?array
    {
        $challan = FeeChallan::query()
            ->withSum('payments as paid_total', 'amount_paid')
            ->where('student_id', $studentId)
            ->where('session', $session)
            ->orderByDesc('id')
            ->first([
                'id',
                'challan_number',
                'session',
                'month',
                'issue_date',
                'due_date',
                'total_amount',
                'status',
                'created_at',
            ]);

        if (! $challan) {
            return null;
        }

        $paid = round((float) ($challan->paid_total ?? 0), 2);
        $total = round((float) $challan->total_amount, 2);
        $due = round(max($total - $paid, 0), 2);
        $createdAt = $challan->created_at;
        $isRecentlyGenerated = $createdAt !== null && $createdAt->greaterThanOrEqualTo(now()->subHours(24));

        return [
            'id' => (int) $challan->id,
            'challan_number' => (string) $challan->challan_number,
            'session' => (string) $challan->session,
            'month' => (string) ($challan->month ?? '-'),
            'issue_date' => optional($challan->issue_date)->toDateString(),
            'due_date' => optional($challan->due_date)->toDateString(),
            'status' => (string) $challan->status,
            'total_amount' => $total,
            'paid_amount' => $paid,
            'due_amount' => $due,
            'is_recently_generated' => $isRecentlyGenerated,
        ];
    }

    private function resolveStudentForUser(string $userName, string $email): ?Student
    {
        $normalizedName = mb_strtolower(trim($userName));
        $emailLocal = mb_strtolower(trim(Str::before($email, '@')));

        if ($emailLocal !== '') {
            $byStudentId = Student::query()
                ->whereRaw('LOWER(student_id) = ?', [$emailLocal])
                ->first();

            if ($byStudentId) {
                return $byStudentId;
            }
        }

        if ($normalizedName !== '') {
            $byName = Student::query()
                ->whereRaw('LOWER(name) = ?', [$normalizedName])
                ->orderByDesc('id')
                ->get();

            if ($byName->count() === 1) {
                return $byName->first();
            }
        }

        return null;
    }
}
