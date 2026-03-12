@php
    $sidebarUser = auth()->user();
    $menuItems = [];

    if ($sidebarUser?->hasRole('Admin')) {
        $menuItems = [
            ['route' => 'admin.dashboard', 'label' => 'Dashboard'],
            ['route' => 'admin.users.index', 'label' => 'User Management'],
            ['route' => 'admin.students.index', 'label' => 'Students'],
            ['route' => 'admin.rbac-matrix.index', 'label' => 'RBAC Matrix'],
            ['route' => 'admin.settings.edit', 'label' => 'School Settings'],
            ['route' => 'principal.results.generator', 'label' => 'Results'],
            ['route' => 'notifications.index', 'label' => 'Notifications'],
        ];
    } elseif ($sidebarUser?->hasRole('Principal')) {
        $menuItems = [
            ['route' => 'principal.dashboard', 'label' => 'Dashboard'],
            ['route' => 'principal.students.index', 'label' => 'Students'],
            ['route' => 'principal.teachers.index', 'label' => 'Teachers'],
            ['route' => 'principal.teacher-assignments.index', 'label' => 'Assignments'],
            ['route' => 'principal.timetable.import.index', 'label' => 'Timetable Import'],
            ['route' => 'principal.results.generator', 'label' => 'Results'],
            ['route' => 'principal.analytics.teachers.index', 'label' => 'Teacher Analytics'],
            ['route' => 'principal.medical.referrals.index', 'label' => 'Medical Referrals'],
            ['route' => 'notifications.index', 'label' => 'Notifications'],
        ];

        if ($sidebarUser?->can('view_fee_structure')) {
            $menuItems[] = ['route' => 'principal.fees.structures.index', 'label' => 'Fee Structures'];
        }
        if ($sidebarUser?->can('generate_fee_challans')) {
            $menuItems[] = ['route' => 'principal.fees.challans.generate', 'label' => 'Generate Challans'];
        }
        if ($sidebarUser?->can('view_fee_challans')) {
            $menuItems[] = ['route' => 'principal.fees.challans.index', 'label' => 'Fee Challans'];
        }
        if ($sidebarUser?->can('record_fee_payment')) {
            $menuItems[] = ['route' => 'principal.fees.payments.index', 'label' => 'Record Payments'];
        }
        if ($sidebarUser?->can('view_fee_reports')) {
            $menuItems[] = ['route' => 'principal.fees.reports.index', 'label' => 'Fee Reports'];
        }
        if ($sidebarUser?->can('manage_subject_assignments')) {
            $menuItems[] = ['route' => 'principal.subject-matrix.index', 'label' => 'Subject Matrix'];
        }
        $hasPayrollAccess = $sidebarUser?->can('view_payroll')
            || $sidebarUser?->can('manage_payroll')
            || $sidebarUser?->can('generate_salary_sheet')
            || $sidebarUser?->can('view_salary_slips')
            || $sidebarUser?->can('edit_salary_structure');
        if ($hasPayrollAccess) {
            $menuItems[] = ['route' => 'principal.payroll.dashboard', 'label' => 'Payroll Dashboard'];
        }
        if ($sidebarUser?->can('view_payroll')) {
            $menuItems[] = ['route' => 'principal.payroll.profiles.index', 'label' => 'Payroll Profiles'];
        }
        if ($sidebarUser?->can('generate_salary_sheet')) {
            $menuItems[] = ['route' => 'principal.payroll.generate.index', 'label' => 'Generate Payroll'];
            $menuItems[] = ['route' => 'principal.payroll.sheet.index', 'label' => 'Salary Sheet'];
        }
        if ($sidebarUser?->can('view_salary_slips')) {
            $menuItems[] = ['route' => 'principal.payroll.slips.index', 'label' => 'Salary Slip'];
        }
        if ($sidebarUser?->can('view_payroll')) {
            $menuItems[] = ['route' => 'principal.payroll.reports.index', 'label' => 'Payroll Reports'];
        }
    } elseif ($sidebarUser?->hasRole('Teacher')) {
        $menuItems = [
            ['route' => 'teacher.dashboard', 'label' => 'Dashboard'],
        ];

        if ($sidebarUser?->can('mark_attendance')) {
            $menuItems[] = ['route' => 'teacher.attendance.index', 'label' => 'Attendance'];
        }
        if ($sidebarUser?->can('enter_marks')) {
            $menuItems[] = ['route' => 'teacher.exams.index', 'label' => 'Marks Entry'];
        }
        if ($sidebarUser?->can('view_own_mark_entries')) {
            $menuItems[] = ['route' => 'teacher.marks.entries.index', 'label' => 'My Mark Entries'];
        }

        $menuItems[] = ['route' => 'teacher.timetable.index', 'label' => 'Timetable'];
        $menuItems[] = ['route' => 'notifications.index', 'label' => 'Notifications'];
    } elseif ($sidebarUser?->hasRole('Doctor')) {
        $menuItems = [
            ['route' => 'doctor.dashboard', 'label' => 'Dashboard'],
            ['route' => 'doctor.medical.requests-list', 'label' => 'Medical Requests'],
            ['route' => 'medical.reports.index', 'label' => 'Medical Reports'],
            ['route' => 'notifications.index', 'label' => 'Notifications'],
        ];
    } elseif ($sidebarUser?->hasRole('Student')) {
        $menuItems = [
            ['route' => 'student.dashboard', 'label' => 'Dashboard'],
            ['route' => 'student.results.index', 'label' => 'My Results'],
            ['route' => 'notifications.index', 'label' => 'Notifications'],
        ];
    }
@endphp

<div class="relative z-40 lg:hidden" x-cloak x-show="sidebarOpen" aria-hidden="true">
    <div class="fixed inset-0 bg-slate-900/50" @click="sidebarOpen = false"></div>
</div>

<aside
    class="fixed inset-y-0 left-0 z-50 flex w-72 transform flex-col border-r border-slate-200 bg-white transition duration-200 ease-out lg:translate-x-0"
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
>
    <div class="flex h-16 items-center justify-between border-b border-slate-200 px-5">
        <a href="{{ route('dashboard') }}" class="text-base font-semibold text-slate-900">School Managment System by HOL</a>
        <button type="button" class="rounded-md p-2 text-slate-600 hover:bg-slate-100 lg:hidden" @click="sidebarOpen = false">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
        </button>
    </div>

    <nav class="flex-1 space-y-1 overflow-y-auto px-4 py-5">
        @foreach($menuItems as $item)
            @php
                $routeParts = explode('.', $item['route']);
                array_pop($routeParts);
                $routeGroup = implode('.', $routeParts);
                $isActive = request()->routeIs($item['route']) || ($routeGroup !== '' && request()->routeIs($routeGroup.'.*'));
            @endphp
            <a
                href="{{ route($item['route']) }}"
                class="{{ $isActive ? 'bg-indigo-50 text-indigo-700 border-indigo-200' : 'text-slate-700 hover:bg-slate-50 border-transparent' }} block rounded-xl border px-4 py-2.5 text-sm font-medium transition"
            >
                {{ $item['label'] }}
            </a>
        @endforeach
    </nav>

    <div class="border-t border-slate-200 p-4">
        <p class="text-xs text-slate-500">Logged in as</p>
        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $sidebarUser?->name }}</p>
        <p class="text-xs text-slate-500">{{ $sidebarUser?->getRoleNames()?->implode(', ') }}</p>
    </div>
</aside>
