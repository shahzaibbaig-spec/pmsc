<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">Doctor Dashboard</h2>
            <p class="mt-1 text-sm text-slate-500">Review referrals, update cases, and monitor workload.</p>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <x-ui.card title="Pending Referrals"><p class="text-3xl font-semibold text-amber-700">{{ $pendingCount ?? 0 }}</p></x-ui.card>
        <x-ui.card title="Completed Today"><p class="text-3xl font-semibold text-emerald-700">{{ $completedTodayCount ?? 0 }}</p></x-ui.card>
        <x-ui.card title="Unread Notifications"><p class="text-3xl font-semibold text-indigo-700">{{ $unreadCount ?? 0 }}</p></x-ui.card>
    </div>

    <x-ui.card class="mt-6" title="Quick Actions">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <x-ui.button href="{{ route('doctor.medical.requests-list') }}">Medical Requests List</x-ui.button>
            <x-ui.button href="{{ route('doctor.medical.referrals.index') }}" variant="success">Open Referral Panel</x-ui.button>
            <x-ui.button href="{{ route('medical.reports.index') }}" variant="outline">Medical Reports</x-ui.button>
        </div>
    </x-ui.card>
</x-app-layout>
