<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Admin Dashboard</h2>
                <p class="mt-1 text-sm text-slate-500">Control users, permissions, and system-wide settings.</p>
            </div>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <x-ui.card title="Total Users"><p class="text-3xl font-semibold text-slate-900">{{ $stats['users'] ?? 0 }}</p></x-ui.card>
        <x-ui.card title="Total Students"><p class="text-3xl font-semibold text-slate-900">{{ $stats['students'] ?? 0 }}</p></x-ui.card>
        <x-ui.card title="Total Teachers"><p class="text-3xl font-semibold text-slate-900">{{ $stats['teachers'] ?? 0 }}</p></x-ui.card>
        <x-ui.card title="System Roles"><p class="text-3xl font-semibold text-slate-900">{{ $stats['roles'] ?? 0 }}</p></x-ui.card>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <x-ui.card title="User Management" subtitle="Create and manage platform users.">
            <x-ui.button href="{{ route('admin.users.index') }}">Open Users</x-ui.button>
        </x-ui.card>
        <x-ui.card title="Student Records" subtitle="Manage student records from admin side.">
            <x-ui.button href="{{ route('admin.students.index') }}" variant="secondary">Open Students</x-ui.button>
        </x-ui.card>
        <x-ui.card title="RBAC Matrix" subtitle="Assign permissions to roles via matrix view.">
            <x-ui.button href="{{ route('admin.rbac-matrix.index') }}" variant="outline">Open RBAC Matrix</x-ui.button>
        </x-ui.card>
        <x-ui.card title="School Settings" subtitle="Update school name and logo used globally.">
            <x-ui.button href="{{ route('admin.settings.edit') }}" variant="outline">Open Settings</x-ui.button>
        </x-ui.card>
        <x-ui.card title="Results Module" subtitle="View and publish generated class results.">
            <x-ui.button href="{{ route('principal.results.generator') }}" variant="outline">Open Results</x-ui.button>
        </x-ui.card>
    </div>
</x-app-layout>
