<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">Teacher Dashboard</h2>
            <p class="mt-1 text-sm text-slate-500">Attendance, marks, timetable, and reminders.</p>
            @if (!empty($stats['assignment_session']))
                <p class="mt-1 text-xs text-slate-500">Assignments session: {{ $stats['assignment_session'] }}</p>
            @endif
        </div>
    </x-slot>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <x-ui.card title="Assigned Classes"><p class="text-3xl font-semibold text-slate-900">{{ $stats['assigned_classes'] ?? 0 }}</p></x-ui.card>
        <x-ui.card title="Assigned Subjects"><p class="text-3xl font-semibold text-slate-900">{{ $stats['assigned_subjects'] ?? 0 }}</p></x-ui.card>
        <x-ui.card title="Marks Added Today"><p class="text-3xl font-semibold text-slate-900">{{ $stats['marks_today'] ?? 0 }}</p></x-ui.card>
        <x-ui.card title="Attendance Rows Today"><p class="text-3xl font-semibold text-slate-900">{{ $stats['attendance_rows_today'] ?? 0 }}</p></x-ui.card>
    </div>

    <x-ui.card class="mt-6" title="Quick Actions">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
            @can('mark_attendance')
                <x-ui.button href="{{ route('teacher.attendance.index') }}">Mark Attendance</x-ui.button>
            @endcan
            @can('enter_marks')
                <x-ui.button href="{{ route('teacher.exams.index') }}" variant="success">Enter Marks</x-ui.button>
            @endcan
            @can('view_own_mark_entries')
                <x-ui.button href="{{ route('teacher.marks.entries.index') }}" variant="secondary">My Mark Entries</x-ui.button>
            @endcan
            <x-ui.button href="{{ route('teacher.timetable.index') }}" variant="secondary">View Timetable</x-ui.button>
            <x-ui.button href="{{ route('notifications.index') }}" variant="outline">Notifications</x-ui.button>
        </div>
    </x-ui.card>
</x-app-layout>
