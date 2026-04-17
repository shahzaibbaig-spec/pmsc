<?php

namespace App\Services;

use App\Models\DailyDiary;
use App\Models\DisciplineComplaint;
use App\Models\HostelLeaveRequest;
use App\Models\HostelNightAttendance;
use App\Models\HostelRoom;
use App\Models\HostelRoomAllocation;
use App\Models\Student;
use Illuminate\Support\Carbon;

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
    public function getDashboardData(?string $date = null): array
    {
        $resolvedDate = Carbon::parse(trim((string) $date) !== '' ? (string) $date : now()->toDateString())
            ->toDateString();

        $recentCases = DisciplineComplaint::query()
            ->with([
                'student:id,name,student_id,class_id',
                'student.classRoom:id,name,section',
            ])
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
                ->count(),
            'total_discipline_reports' => DisciplineComplaint::query()->count(),
            'open_discipline_reports' => DisciplineComplaint::query()
                ->whereNotIn('status', ['closed', 'resolved'])
                ->count(),
            'total_students' => Student::query()->count(),
            'total_hostel_rooms' => HostelRoom::query()->count(),
            'active_room_allocations' => HostelRoomAllocation::query()
                ->where('status', HostelRoomAllocation::STATUS_ACTIVE)
                ->count(),
            'pending_hostel_leave_requests' => HostelLeaveRequest::query()
                ->where('status', HostelLeaveRequest::STATUS_PENDING)
                ->count(),
            'night_attendance_marked_today' => HostelNightAttendance::query()
                ->whereDate('attendance_date', $resolvedDate)
                ->count(),
            'recent_discipline_cases' => $recentCases,
        ];
    }
}
