<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Seating Plan #{{ (int) $plan->id }}</h2>
                <p class="mt-1 text-sm text-slate-500">
                    {{ $plan->examSession?->name ?? 'Exam Session' }} ({{ $plan->examSession?->session ?? '-' }})
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a
                    href="{{ route('principal.exams.seating-plans.index', ['exam_session_id' => $plan->exam_session_id]) }}"
                    class="inline-flex min-h-10 items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                >
                    Back
                </a>
                <a
                    href="{{ route('principal.exams.seating-plans.print', $plan) }}"
                    target="_blank"
                    class="inline-flex min-h-10 items-center rounded-lg border border-indigo-300 bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-100"
                >
                    Printable Page
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                {{ session('error') }}
            </div>
        @endif

        <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total Students</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">{{ (int) $plan->total_students }}</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total Rooms Used</p>
                <p class="mt-2 text-2xl font-semibold text-indigo-700">{{ (int) $plan->total_rooms }}</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Allocation Mode</p>
                @if($plan->is_randomized)
                    <p class="mt-2 text-2xl font-semibold text-amber-700">Randomized</p>
                @else
                    <p class="mt-2 text-2xl font-semibold text-emerald-700">Roll Order</p>
                @endif
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Generated</p>
                <p class="mt-2 text-sm font-semibold text-slate-900">{{ optional($plan->generated_at)->format('d M Y h:i A') ?: '-' }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ $plan->generator?->name ?: 'System' }}</p>
            </article>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-slate-900">Selected Classes</h3>
            <div class="mt-3 flex flex-wrap gap-2">
                @forelse($classLabels as $label)
                    <span class="inline-flex rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-medium text-slate-700">{{ $label }}</span>
                @empty
                    <span class="text-sm text-slate-500">No class metadata available.</span>
                @endforelse
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-4 py-3">
                <h3 class="text-sm font-semibold text-slate-900">Room Summary</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Room</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Used Seats</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Capacity</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Occupancy</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($roomGroups as $group)
                            @php
                                $occupancy = $group['capacity'] > 0 ? round(($group['used_seats'] / $group['capacity']) * 100, 2) : 0;
                            @endphp
                            <tr>
                                <td class="px-4 py-3 text-sm font-semibold text-slate-900">{{ $group['room']?->name ?? 'Room' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ (int) $group['used_seats'] }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ (int) $group['capacity'] }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ $occupancy }}%</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-10 text-center text-sm text-slate-500">No assignments found for this plan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <div class="space-y-4">
            @foreach($roomGroups as $group)
                <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-4 py-3">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900">{{ $group['room']?->name ?? 'Room' }}</h3>
                            <p class="mt-1 text-xs text-slate-500">
                                Seats Used: {{ (int) $group['used_seats'] }} / {{ (int) $group['capacity'] }}
                            </p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Seat #</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Student</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Class</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Seat Slip</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @foreach($group['assignments'] as $assignment)
                                    @php
                                        $student = $assignment->student;
                                        $classLabel = trim(($assignment->classRoom?->name ?? '').' '.($assignment->classRoom?->section ?? ''));
                                        $classLabel = $classLabel !== '' ? $classLabel : '-';
                                    @endphp
                                    <tr>
                                        <td class="px-4 py-3 text-sm font-semibold text-slate-900">{{ (int) $assignment->seat_number }}</td>
                                        <td class="px-4 py-3 text-sm text-slate-700">
                                            <div class="font-semibold text-slate-900">{{ $student?->name ?? 'Student' }}</div>
                                            <div class="text-xs text-slate-500">{{ $student?->student_id ?: ($student?->id ?? '-') }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-slate-700">{{ $classLabel }}</td>
                                        <td class="px-4 py-3 text-sm text-slate-700">
                                            <a
                                                href="{{ route('principal.exams.seating-plans.seat-slip', ['examSeatingPlan' => $plan, 'examSeatAssignment' => $assignment]) }}"
                                                target="_blank"
                                                class="inline-flex min-h-10 items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                            >
                                                Print Seat Slip
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endforeach
        </div>
    </div>
</x-app-layout>
