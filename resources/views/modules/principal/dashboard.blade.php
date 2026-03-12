<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">Principal Dashboard</h2>
            <p class="mt-1 text-sm text-slate-500">Daily operations, academics, and analytics in one view.</p>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <x-ui.card title="Total Students"><p class="text-3xl font-semibold text-slate-900">{{ $attendanceSummary['total_students'] ?? 0 }}</p></x-ui.card>
        <x-ui.card title="Present Today"><p class="text-3xl font-semibold text-emerald-700">{{ $attendanceSummary['present'] ?? 0 }}</p></x-ui.card>
        <x-ui.card title="Absent Today"><p class="text-3xl font-semibold text-rose-700">{{ $attendanceSummary['absent'] ?? 0 }}</p></x-ui.card>
        <x-ui.card title="Leave Today">
            <p class="text-3xl font-semibold text-amber-700">{{ $attendanceSummary['leave'] ?? 0 }}</p>
            <p class="mt-1 text-xs text-slate-500">Date: {{ $attendanceSummary['date'] ?? now()->toDateString() }}</p>
        </x-ui.card>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <x-ui.card title="Classes"><p class="text-2xl font-semibold text-slate-900">{{ $stats['classes'] ?? 0 }}</p></x-ui.card>
        <x-ui.card title="Subjects"><p class="text-2xl font-semibold text-slate-900">{{ $stats['subjects'] ?? 0 }}</p></x-ui.card>
        <x-ui.card title="Teachers"><p class="text-2xl font-semibold text-slate-900">{{ $stats['teachers'] ?? 0 }}</p></x-ui.card>
        <x-ui.card title="Pending Medical"><p class="text-2xl font-semibold text-slate-900">{{ $stats['pending_medical'] ?? 0 }}</p></x-ui.card>
    </div>

    <x-ui.card class="mt-6" title="Quick Actions">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <x-ui.button href="{{ route('principal.students.index') }}" variant="outline">Students List</x-ui.button>
            <x-ui.button href="{{ route('principal.teachers.index') }}" variant="outline">Teachers List</x-ui.button>
            @can('manage_subject_assignments')
                <x-ui.button href="{{ route('principal.subject-matrix.index') }}" variant="outline">Subject Matrix</x-ui.button>
            @endcan
            <x-ui.button href="{{ route('principal.teacher-assignments.index') }}" variant="outline">Teacher Assignments</x-ui.button>
            <x-ui.button href="{{ route('principal.results.generator') }}" variant="outline">Results Module</x-ui.button>
            <x-ui.button href="{{ route('principal.analytics.teachers.index') }}" variant="outline">Teacher Analytics</x-ui.button>
            @can('view_fee_structure')
                <x-ui.button href="{{ route('principal.fees.structures.index') }}" variant="outline">Fee Structures</x-ui.button>
            @endcan
            @can('view_fee_reports')
                <x-ui.button href="{{ route('principal.fees.reports.index') }}" variant="outline">Fee Reports</x-ui.button>
            @endcan
            @can('view_payroll')
                <x-ui.button href="{{ route('principal.payroll.profiles.index') }}" variant="outline">Payroll Profiles</x-ui.button>
            @endcan
            @can('generate_salary_sheet')
                <x-ui.button href="{{ route('principal.payroll.generate.index') }}" variant="outline">Generate Payroll</x-ui.button>
            @endcan
        </div>
    </x-ui.card>
</x-app-layout>
