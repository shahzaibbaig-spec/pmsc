<?php

namespace App\Services;

use App\Models\DailyDiary;
use App\Models\DisciplineComplaint;
use App\Models\HostelLeaveRequest;
use App\Models\HostelNightAttendance;
use App\Models\HostelRoom;
use App\Models\HostelRoomAllocation;
use App\Models\Student;
use App\Models\User;
use App\Models\WardenAttendance;
use App\Models\WardenDailyReport;
use App\Models\WardenDisciplineLog;
use App\Models\WardenHealthLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use RuntimeException;

class WardenDashboardService
{
    /**
     * @return array{
     *     date:string,
     *     total_daily_diary_entries_today:int,
     *     total_discipline_reports:int,
     *     open_discipline_reports:int,
     *     total_students:int,
     *     total_hostel_rooms:int,
     *     active_room_allocations:int,
     *     pending_hostel_leave_requests:int,
     *     night_attendance_marked_today:int,
     *     warden_reports_today:int,
     *     warden_present_today:int,
     *     warden_absent_today:int,
     *     warden_discipline_incidents_today:int,
     *     warden_health_cases_today:int,
     *     recent_discipline_cases:array<int, array{
     *         id:int,
     *         student_name:string,
     *         student_code:string,
     *         class_name:string,
     *         complaint_date:string,
     *         status:string,
     *         description_preview:string
     *     }>
     * }
     */
    public function getDashboardData(?string $date = null, ?User $user = null): array
    {
        $resolvedDate = Carbon::parse(trim((string) $date) !== '' ? (string) $date : now()->toDateString())
            ->toDateString();

        $user = $user ?? auth()->user();
        if (! $user instanceof User) {
            throw new RuntimeException('Authenticated user context is required.');
        }
        $hostelClassIds = Student::query()
            ->forWarden($user)
            ->distinct()
            ->pluck('class_id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();

        $recentCases = DisciplineComplaint::query()
            ->with([
                'student:id,name,student_id,class_id',
                'student.classRoom:id,name,section',
            ])
            ->whereHas('student', fn (Builder $query) => $query->forWarden($user))
            ->orderByDesc('complaint_date')
            ->orderByDesc('id')
            ->limit(8)
            ->get()
            ->map(function (DisciplineComplaint $complaint): array {
                return [
                    'id' => (int) $complaint->id,
                    'student_name' => (string) ($complaint->student?->name ?? 'Student'),
                    'student_code' => (string) ($complaint->student?->student_id ?? '-'),
                    'class_name' => trim((string) ($complaint->student?->classRoom?->name ?? '').' '.(string) ($complaint->student?->classRoom?->section ?? '')),
                    'complaint_date' => optional($complaint->complaint_date)->toDateString() ?? (string) $complaint->created_at?->toDateString(),
                    'status' => (string) ($complaint->status ?? 'pending'),
                    'description_preview' => str((string) $complaint->description)->squish()->limit(110)->value(),
                ];
            })
            ->values()
            ->all();

        return [
            'date' => $resolvedDate,
            'total_daily_diary_entries_today' => DailyDiary::query()
                ->whereDate('diary_date', $resolvedDate)
                ->when($hostelClassIds !== [], fn (Builder $query) => $query->whereIn('class_id', $hostelClassIds))
                ->when($hostelClassIds === [], fn (Builder $query) => $query->whereRaw('1 = 0'))
                ->count(),
            'total_discipline_reports' => DisciplineComplaint::query()
                ->whereHas('student', fn (Builder $query) => $query->forWarden($user))
                ->count(),
            'open_discipline_reports' => DisciplineComplaint::query()
                ->whereHas('student', fn (Builder $query) => $query->forWarden($user))
                ->whereNotIn('status', ['closed', 'resolved'])
                ->count(),
            'total_students' => Student::query()->forWarden($user)->count(),
            'total_hostel_rooms' => HostelRoom::query()->forWarden($user)->count(),
            'active_room_allocations' => HostelRoomAllocation::query()
                ->forWarden($user)
                ->where('status', HostelRoomAllocation::STATUS_ACTIVE)
                ->count(),
            'pending_hostel_leave_requests' => HostelLeaveRequest::query()
                ->forWarden($user)
                ->where('status', HostelLeaveRequest::STATUS_PENDING)
                ->count(),
            'night_attendance_marked_today' => HostelNightAttendance::query()
                ->forWarden($user)
                ->whereDate('attendance_date', $resolvedDate)
                ->count(),
            'warden_reports_today' => WardenDailyReport::query()
                ->where('hostel_id', (int) ($user->hostel_id ?? 0))
                ->whereDate('report_date', $resolvedDate)
                ->count(),
            'warden_present_today' => WardenAttendance::query()
                ->whereHas('report', function (Builder $query) use ($user, $resolvedDate): void {
                    $query->where('hostel_id', (int) ($user->hostel_id ?? 0))
                        ->whereDate('report_date', $resolvedDate);
                })
                ->where('status', 'present')
                ->count(),
            'warden_absent_today' => WardenAttendance::query()
                ->whereHas('report', function (Builder $query) use ($user, $resolvedDate): void {
                    $query->where('hostel_id', (int) ($user->hostel_id ?? 0))
                        ->whereDate('report_date', $resolvedDate);
                })
                ->where('status', 'absent')
                ->count(),
            'warden_discipline_incidents_today' => WardenDisciplineLog::query()
                ->whereHas('report', function (Builder $query) use ($user, $resolvedDate): void {
                    $query->where('hostel_id', (int) ($user->hostel_id ?? 0))
                        ->whereDate('report_date', $resolvedDate);
                })
                ->count(),
            'warden_health_cases_today' => WardenHealthLog::query()
                ->whereHas('report', function (Builder $query) use ($user, $resolvedDate): void {
                    $query->where('hostel_id', (int) ($user->hostel_id ?? 0))
                        ->whereDate('report_date', $resolvedDate);
                })
                ->count(),
            'recent_discipline_cases' => $recentCases,
        ];
    }
}
