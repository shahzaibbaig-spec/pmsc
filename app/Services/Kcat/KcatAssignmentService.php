<?php

namespace App\Services\Kcat;

use App\Models\KcatAssignment;
use App\Models\KcatTest;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class KcatAssignmentService
{
    public function assignToStudent(KcatTest $test, Student $student, User $counselor, array $data = []): KcatAssignment
    {
        return DB::transaction(fn (): KcatAssignment => KcatAssignment::query()->create([
            'kcat_test_id' => $test->id,
            'assigned_to_type' => 'student',
            'student_id' => $student->id,
            'session' => $data['session'] ?? $this->currentSession(),
            'assigned_by' => $counselor->id,
            'assigned_at' => now(),
            'due_date' => $data['due_date'] ?? null,
            'status' => 'assigned',
        ])->fresh(['test', 'student.classRoom', 'assignedBy']));
    }

    public function assignToClass(KcatTest $test, $class, User $counselor, array $data = []): KcatAssignment
    {
        $classId = $class instanceof SchoolClass ? $class->id : (int) $class;

        return DB::transaction(fn (): KcatAssignment => KcatAssignment::query()->create([
            'kcat_test_id' => $test->id,
            'assigned_to_type' => 'class',
            'class_id' => $classId,
            'section' => $data['section'] ?? null,
            'session' => $data['session'] ?? $this->currentSession(),
            'assigned_by' => $counselor->id,
            'assigned_at' => now(),
            'due_date' => $data['due_date'] ?? null,
            'status' => 'assigned',
        ])->fresh(['test', 'classRoom', 'assignedBy']));
    }

    public function getAssignments(array $filters = []): LengthAwarePaginator
    {
        return KcatAssignment::query()
            ->with(['test', 'student.classRoom', 'classRoom', 'assignedBy'])
            ->when($filters['session'] ?? null, fn (Builder $query, string $session) => $query->where('session', $session))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->latest('assigned_at')
            ->paginate(20)
            ->withQueryString();
    }

    private function currentSession(): string
    {
        $now = now();
        $startYear = $now->month >= 7 ? $now->year : ($now->year - 1);
        return $startYear.'-'.($startYear + 1);
    }
}
