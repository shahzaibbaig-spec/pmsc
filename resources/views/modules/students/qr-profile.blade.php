<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $student->name }} - Student QR Profile</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    @php
        $className = trim((string) ($student->classRoom?->name ?? '').' '.(string) ($student->classRoom?->section ?? ''));
        $resolvedClassName = $className !== '' ? $className : '-';

        $photoPath = trim((string) ($student->photo_path ?? ''));
        $photoUrl = $photoPath !== '' ? asset('storage/'.$photoPath) : null;

        $initials = collect(preg_split('/\s+/', trim((string) $student->name)))
            ->filter()
            ->take(2)
            ->map(fn ($part) => strtoupper(substr((string) $part, 0, 1)))
            ->implode('');

        $attendancePercentage = max(min((float) ($attendanceStats['attendance_percentage'] ?? 0), 100), 0);
        $feeToneClasses = match ($feeSummary['status_tone'] ?? 'warning') {
            'success' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
            'danger' => 'bg-rose-100 text-rose-800 border-rose-200',
            default => 'bg-amber-100 text-amber-800 border-amber-200',
        };
    @endphp

    <main class="mx-auto w-full max-w-md px-4 py-6 sm:py-10">
        <section class="overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-600 via-sky-600 to-cyan-500 p-5 text-white shadow-lg">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-white/80">Student QR Profile</p>
            <div class="mt-4 flex items-center gap-4">
                @if($photoUrl)
                    <img
                        src="{{ $photoUrl }}"
                        alt="{{ $student->name }} photo"
                        class="h-20 w-20 rounded-xl border border-white/40 object-cover"
                    >
                @else
                    <div class="flex h-20 w-20 items-center justify-center rounded-xl border border-white/40 bg-white/20 text-2xl font-bold">
                        {{ $initials !== '' ? $initials : 'ST' }}
                    </div>
                @endif

                <div class="min-w-0">
                    <h1 class="truncate text-xl font-semibold">{{ $student->name }}</h1>
                    <p class="mt-1 text-sm text-white/85">Student ID: {{ $student->student_id }}</p>
                    <p class="text-sm text-white/85">Class: {{ $resolvedClassName }}</p>
                </div>
            </div>
        </section>

        <section class="mt-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Student Details</h2>
            <dl class="mt-3 space-y-3 text-sm">
                <div class="flex items-start justify-between gap-3">
                    <dt class="text-slate-500">Father Name</dt>
                    <dd class="text-right font-medium text-slate-900">{{ $student->father_name ?: '-' }}</dd>
                </div>
                <div class="flex items-start justify-between gap-3">
                    <dt class="text-slate-500">Class</dt>
                    <dd class="text-right font-medium text-slate-900">{{ $resolvedClassName }}</dd>
                </div>
                <div class="flex items-start justify-between gap-3">
                    <dt class="text-slate-500">Status</dt>
                    <dd class="text-right font-medium text-slate-900">{{ ucfirst((string) ($student->status ?? 'active')) }}</dd>
                </div>
            </dl>
        </section>

        <section class="mt-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-2">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Attendance</h2>
                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">
                    {{ $attendanceSource === 'attendance' ? 'Current Data' : 'Legacy Data' }}
                </span>
            </div>

            <div class="mt-3 flex items-end justify-between">
                <p class="text-3xl font-bold text-slate-900">{{ number_format($attendancePercentage, 2) }}%</p>
                <p class="text-xs text-slate-500">Total Days: {{ (int) ($attendanceStats['total'] ?? 0) }}</p>
            </div>

            <div class="mt-3 h-2.5 rounded-full bg-slate-200">
                <div
                    class="h-2.5 rounded-full bg-emerald-500 transition-all"
                    style="width: {{ number_format($attendancePercentage, 2, '.', '') }}%;"
                ></div>
            </div>

            <div class="mt-4 grid grid-cols-3 gap-2 text-center text-xs sm:text-sm">
                <div class="rounded-xl bg-emerald-50 px-2 py-3">
                    <p class="font-semibold text-emerald-700">{{ (int) ($attendanceStats['present'] ?? 0) }}</p>
                    <p class="text-emerald-600">Present</p>
                </div>
                <div class="rounded-xl bg-rose-50 px-2 py-3">
                    <p class="font-semibold text-rose-700">{{ (int) ($attendanceStats['absent'] ?? 0) }}</p>
                    <p class="text-rose-600">Absent</p>
                </div>
                <div class="rounded-xl bg-amber-50 px-2 py-3">
                    <p class="font-semibold text-amber-700">{{ (int) ($attendanceStats['leave'] ?? 0) }}</p>
                    <p class="text-amber-600">Leave</p>
                </div>
            </div>
        </section>

        <section class="mt-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Fee Status</h2>
                <span class="inline-flex rounded-full border px-3 py-1 text-xs font-semibold {{ $feeToneClasses }}">
                    {{ $feeSummary['status_label'] ?? 'Pending' }}
                </span>
            </div>

            <p class="mt-3 text-3xl font-bold text-slate-900">
                Rs. {{ number_format((float) ($feeSummary['due_amount'] ?? 0), 2) }}
            </p>
            <p class="mt-1 text-xs text-slate-500">Outstanding amount</p>

            <div class="mt-4 grid grid-cols-2 gap-2 text-xs sm:text-sm">
                <div class="rounded-xl bg-slate-100 px-3 py-3">
                    <p class="text-slate-500">Total Billed</p>
                    <p class="mt-1 font-semibold text-slate-900">Rs. {{ number_format((float) ($feeSummary['total_billed'] ?? 0), 2) }}</p>
                </div>
                <div class="rounded-xl bg-slate-100 px-3 py-3">
                    <p class="text-slate-500">Total Paid</p>
                    <p class="mt-1 font-semibold text-slate-900">Rs. {{ number_format((float) ($feeSummary['total_paid'] ?? 0), 2) }}</p>
                </div>
                <div class="rounded-xl bg-slate-100 px-3 py-3">
                    <p class="text-slate-500">Pending Challans</p>
                    <p class="mt-1 font-semibold text-slate-900">{{ (int) ($feeSummary['pending_challans'] ?? 0) }}</p>
                </div>
                <div class="rounded-xl bg-slate-100 px-3 py-3">
                    <p class="text-slate-500">Overdue Challans</p>
                    <p class="mt-1 font-semibold text-slate-900">{{ (int) ($feeSummary['overdue_challans'] ?? 0) }}</p>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
