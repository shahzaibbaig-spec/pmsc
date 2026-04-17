<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Leave Request Detail</h2>
                <p class="mt-1 text-sm text-slate-500">Review request details and update leave status.</p>
            </div>
            <a href="{{ route('warden.hostel.leaves.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Back to Leave List
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->has('leave'))
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    {{ $errors->first('leave') }}
                </div>
            @endif

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <h3 class="text-lg font-semibold text-slate-900">Request #{{ $leave->id }}</h3>
                    @if ($leave->status === 'pending')
                        <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700">Pending</span>
                    @elseif ($leave->status === 'approved')
                        <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Approved</span>
                    @elseif ($leave->status === 'rejected')
                        <span class="inline-flex rounded-full bg-rose-100 px-2.5 py-1 text-xs font-semibold text-rose-700">Rejected</span>
                    @else
                        <span class="inline-flex rounded-full bg-cyan-100 px-2.5 py-1 text-xs font-semibold text-cyan-700">Returned</span>
                    @endif
                </div>

                <div class="mt-4 grid grid-cols-1 gap-3 text-sm text-slate-700 md:grid-cols-2">
                    <p><span class="font-semibold text-slate-900">Student:</span> {{ $leave->student?->name ?? 'Student' }}</p>
                    <p><span class="font-semibold text-slate-900">Admission No:</span> {{ $leave->student?->student_id ?? '-' }}</p>
                    <p><span class="font-semibold text-slate-900">Class:</span> {{ trim(($leave->student?->classRoom?->name ?? '').' '.($leave->student?->classRoom?->section ?? '')) }}</p>
                    <p><span class="font-semibold text-slate-900">Room:</span>
                        @if ($leave->hostelRoom)
                            {{ $leave->hostelRoom->room_name }} (Floor {{ $leave->hostelRoom->floor_number }})
                        @else
                            -
                        @endif
                    </p>
                    <p><span class="font-semibold text-slate-900">Leave From:</span> {{ optional($leave->leave_from)->format('d M Y h:i A') }}</p>
                    <p><span class="font-semibold text-slate-900">Leave To:</span> {{ optional($leave->leave_to)->format('d M Y h:i A') }}</p>
                    <p><span class="font-semibold text-slate-900">Requested By:</span> {{ $leave->requestedBy?->name ?? '-' }}</p>
                    <p><span class="font-semibold text-slate-900">Approved By:</span> {{ $leave->approvedBy?->name ?? '-' }}</p>
                    <p><span class="font-semibold text-slate-900">Approved At:</span> {{ optional($leave->approved_at)->format('d M Y h:i A') ?: '-' }}</p>
                    <p><span class="font-semibold text-slate-900">Returned At:</span> {{ optional($leave->returned_at)->format('d M Y h:i A') ?: '-' }}</p>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h4 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Reason</h4>
                <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-800">{{ $leave->reason }}</p>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h4 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Remarks</h4>
                <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-800">{{ $leave->remarks ?: 'No remarks added.' }}</p>
            </section>

            @if ($leave->status === 'pending')
                <section class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <form method="POST" action="{{ route('warden.hostel.leaves.approve', $leave) }}" class="space-y-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
                        @csrf
                        <h4 class="text-base font-semibold text-emerald-800">Approve Leave</h4>
                        <textarea name="remarks" rows="3" placeholder="Optional approval remarks" class="block w-full rounded-xl border-emerald-200 text-sm shadow-sm focus:border-emerald-400 focus:ring-emerald-400"></textarea>
                        <button type="submit" class="inline-flex min-h-11 items-center rounded-xl bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-600">
                            Approve
                        </button>
                    </form>

                    <form method="POST" action="{{ route('warden.hostel.leaves.reject', $leave) }}" class="space-y-3 rounded-2xl border border-rose-200 bg-rose-50 p-5 shadow-sm">
                        @csrf
                        <h4 class="text-base font-semibold text-rose-800">Reject Leave</h4>
                        <textarea name="remarks" rows="3" placeholder="Optional rejection reason" class="block w-full rounded-xl border-rose-200 text-sm shadow-sm focus:border-rose-400 focus:ring-rose-400"></textarea>
                        <button type="submit" class="inline-flex min-h-11 items-center rounded-xl bg-rose-700 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-600">
                            Reject
                        </button>
                    </form>
                </section>
            @endif

            @if ($leave->status === 'approved')
                <section class="rounded-2xl border border-cyan-200 bg-cyan-50 p-5 shadow-sm">
                    <form method="POST" action="{{ route('warden.hostel.leaves.returned', $leave) }}" class="space-y-3">
                        @csrf
                        <h4 class="text-base font-semibold text-cyan-800">Mark Student Returned</h4>
                        <textarea name="remarks" rows="3" placeholder="Optional return remarks" class="block w-full rounded-xl border-cyan-200 text-sm shadow-sm focus:border-cyan-400 focus:ring-cyan-400"></textarea>
                        <button type="submit" class="inline-flex min-h-11 items-center rounded-xl bg-cyan-700 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-600">
                            Mark Returned
                        </button>
                    </form>
                </section>
            @endif
        </div>
    </div>
</x-app-layout>

