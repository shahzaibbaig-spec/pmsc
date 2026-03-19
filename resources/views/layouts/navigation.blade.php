<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    @php
        $navUser = auth()->user();
        $navUnreadCount = $navUser?->unreadNotifications()->count() ?? 0;
        $navRecentNotifications = $navUser?->notifications()->latest()->limit(8)->get() ?? collect();
    @endphp

    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>
                    @if (auth()->user()->hasRole('Admin'))
                        <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">
                            {{ __('Admin') }}
                        </x-nav-link>
                        <x-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')">
                            {{ __('Users') }}
                        </x-nav-link>
                        <x-nav-link :href="route('admin.students.index')" :active="request()->routeIs('admin.students.*')">
                            {{ __('Students') }}
                        </x-nav-link>
                        <x-nav-link :href="route('admin.rbac-matrix.index')" :active="request()->routeIs('admin.rbac-matrix.*')">
                            {{ __('RBAC Matrix') }}
                        </x-nav-link>
                    @elseif (auth()->user()->hasRole('Principal'))
                        <x-nav-link :href="route('principal.dashboard')" :active="request()->routeIs('principal.dashboard')">
                            {{ __('Principal') }}
                        </x-nav-link>
                        <x-nav-link :href="route('principal.classes.index')" :active="request()->routeIs('principal.classes.*')">
                            {{ __('Classes') }}
                        </x-nav-link>
                        <x-nav-link :href="route('principal.subjects.index')" :active="request()->routeIs('principal.subjects.index')">
                            {{ __('Subjects') }}
                        </x-nav-link>
                        @can('manage_subject_assignments')
                            <x-nav-link :href="route('principal.subject-matrix.index')" :active="request()->routeIs('principal.subject-matrix.*') || request()->routeIs('principal.subjects.matrix.*')">
                                {{ __('Subject Matrix') }}
                            </x-nav-link>
                        @endcan
                        <x-nav-link :href="route('principal.timetable.settings.index')" :active="request()->routeIs('principal.timetable.settings.*')">
                            {{ __('Timetable Settings') }}
                        </x-nav-link>
                        <x-nav-link :href="route('principal.timetable.subject-rules.index')" :active="request()->routeIs('principal.timetable.subject-rules.*')">
                            {{ __('Subject Rules') }}
                        </x-nav-link>
                        <x-nav-link :href="route('principal.timetable.teacher-availability.index')" :active="request()->routeIs('principal.timetable.teacher-availability.*')">
                            {{ __('Teacher Availability') }}
                        </x-nav-link>
                        <x-nav-link :href="route('principal.timetable.generate.index')" :active="request()->routeIs('principal.timetable.generate.*')">
                            {{ __('Generate Timetable') }}
                        </x-nav-link>
                        <x-nav-link :href="route('principal.timetable.viewer.index')" :active="request()->routeIs('principal.timetable.viewer.*')">
                            {{ __('Timetable Viewer') }}
                        </x-nav-link>
                        <x-nav-link :href="route('principal.teacher-assignments.index')" :active="request()->routeIs('principal.teacher-assignments.*')">
                            {{ __('Teacher Assignments') }}
                        </x-nav-link>
                        <x-nav-link :href="route('principal.results.generator')" :active="request()->routeIs('principal.results.*')">
                            {{ __('Results') }}
                        </x-nav-link>
                        <x-nav-link :href="route('principal.analytics.teachers.index')" :active="request()->routeIs('principal.analytics.teachers.*') || request()->routeIs('principal.analytics.performance-insights.*')">
                            {{ __('Teacher Analytics') }}
                        </x-nav-link>
                        <x-nav-link :href="route('principal.medical.referrals.index')" :active="request()->routeIs('principal.medical.referrals.*')">
                            {{ __('Medical') }}
                        </x-nav-link>
                        <x-nav-link :href="route('reports.index')" :active="request()->routeIs('reports.index') || request()->routeIs('reports.pdf.*')">
                            {{ __('Reports') }}
                        </x-nav-link>
                        <x-nav-link :href="route('medical.reports.index')" :active="request()->routeIs('medical.reports.*')">
                            {{ __('Medical Reports') }}
                        </x-nav-link>
                    @elseif (auth()->user()->hasRole('Teacher'))
                        <x-nav-link :href="route('teacher.dashboard')" :active="request()->routeIs('teacher.dashboard')">
                            {{ __('Teacher') }}
                        </x-nav-link>
                        @can('mark_attendance')
                            <x-nav-link :href="route('teacher.attendance.index')" :active="request()->routeIs('teacher.attendance.*')">
                                {{ __('Attendance') }}
                            </x-nav-link>
                        @endcan
                        @can('enter_marks')
                            <x-nav-link :href="route('teacher.exams.index')" :active="request()->routeIs('teacher.exams.*')">
                                {{ __('Examinations') }}
                            </x-nav-link>
                        @endcan
                        @can('view_own_mark_entries')
                            <x-nav-link :href="route('teacher.marks.entries.index')" :active="request()->routeIs('teacher.marks.entries.*')">
                                {{ __('My Mark Entries') }}
                            </x-nav-link>
                        @endcan
                        <x-nav-link :href="route('teacher.timetable.index')" :active="request()->routeIs('teacher.timetable.*')">
                            {{ __('Timetable') }}
                        </x-nav-link>
                        <x-nav-link :href="route('notifications.index')" :active="request()->routeIs('notifications.*')">
                            {{ __('Notifications') }}
                        </x-nav-link>
                    @elseif (auth()->user()->hasRole('Doctor'))
                        <x-nav-link :href="route('doctor.dashboard')" :active="request()->routeIs('doctor.dashboard')">
                            {{ __('Doctor') }}
                        </x-nav-link>
                        <x-nav-link :href="route('doctor.medical.referrals.index')" :active="request()->routeIs('doctor.medical.referrals.*')">
                            {{ __('Referrals') }}
                        </x-nav-link>
                        <x-nav-link :href="route('notifications.index')" :active="request()->routeIs('notifications.*')">
                            {{ __('Notifications') }}
                        </x-nav-link>
                        <x-nav-link :href="route('medical.reports.index')" :active="request()->routeIs('medical.reports.*')">
                            {{ __('Reports') }}
                        </x-nav-link>
                    @elseif (auth()->user()->hasRole('Student'))
                        <x-nav-link :href="route('student.dashboard')" :active="request()->routeIs('student.dashboard')">
                            {{ __('Student') }}
                        </x-nav-link>
                        <x-nav-link :href="route('student.results.index')" :active="request()->routeIs('student.results.*')">
                            {{ __('My Results') }}
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                @if (auth()->user()->hasRole('Principal'))
                    <div class="relative me-4">
                        <input
                            id="globalStudentSearch"
                            type="text"
                            placeholder="Search students..."
                            class="w-72 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            autocomplete="off"
                        >
                        <div id="globalStudentSearchResults" class="absolute right-0 z-50 mt-1 hidden w-96 max-h-72 overflow-y-auto rounded-md border border-gray-200 bg-white shadow-xl"></div>
                    </div>
                @endif

                <x-dropdown align="right" width="96">
                    <x-slot name="trigger">
                        <button class="relative me-3 inline-flex h-10 w-10 items-center justify-center rounded-full border border-gray-200 text-gray-600 hover:bg-gray-50 hover:text-gray-800 focus:outline-none">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10 2a4 4 0 00-4 4v1.172a2 2 0 01-.586 1.414L4.293 9.707A1 1 0 005 11h10a1 1 0 00.707-1.707l-1.121-1.121A2 2 0 0114 6.172V6a4 4 0 00-4-4z" />
                                <path d="M8 13a2 2 0 104 0H8z" />
                            </svg>
                            @if ($navUnreadCount > 0)
                                <span class="absolute -top-1 -right-1 inline-flex min-w-5 items-center justify-center rounded-full bg-red-600 px-1.5 text-xs font-semibold text-white">
                                    {{ $navUnreadCount > 99 ? '99+' : $navUnreadCount }}
                                </span>
                            @endif
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="px-4 py-3 border-b border-gray-100">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm font-semibold text-gray-800">Notifications</p>
                                @if ($navUnreadCount > 0)
                                    <form method="POST" action="{{ route('notifications.read-all') }}">
                                        @csrf
                                        <button type="submit" class="text-xs font-medium text-indigo-600 hover:text-indigo-700">
                                            Mark all as read
                                        </button>
                                    </form>
                                @endif
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Unread: {{ $navUnreadCount }}</p>
                        </div>

                        <div class="max-h-96 overflow-y-auto">
                            @if ($navRecentNotifications->isEmpty())
                                <div class="px-4 py-3 text-sm text-gray-500">No notifications yet.</div>
                            @else
                                @foreach ($navRecentNotifications as $notification)
                                    @php
                                        $title = $notification->data['title'] ?? 'Notification';
                                        $message = $notification->data['message'] ?? 'You have a new update.';
                                        $url = $notification->data['url'] ?? null;
                                    @endphp
                                    <div class="px-4 py-3 border-b border-gray-100 {{ $notification->read_at ? 'bg-white' : 'bg-indigo-50/40' }}">
                                        @if ($url)
                                            <a href="{{ $url }}" class="block">
                                                <p class="text-sm font-semibold text-gray-900">{{ $title }}</p>
                                                <p class="mt-1 text-xs text-gray-600">{{ $message }}</p>
                                            </a>
                                        @else
                                            <p class="text-sm font-semibold text-gray-900">{{ $title }}</p>
                                            <p class="mt-1 text-xs text-gray-600">{{ $message }}</p>
                                        @endif

                                        <div class="mt-2 flex items-center justify-between gap-3">
                                            <p class="text-[11px] text-gray-500">{{ $notification->created_at?->diffForHumans() }}</p>
                                            @if (! $notification->read_at)
                                                <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                                                    @csrf
                                                    <button type="submit" class="text-[11px] font-medium text-indigo-600 hover:text-indigo-700">
                                                        Mark read
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </x-slot>
                </x-dropdown>

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <x-dropdown-link :href="route('logout')">
                            {{ __('Log Out') }}
                        </x-dropdown-link>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
            @if (auth()->user()->hasRole('Admin'))
                <x-responsive-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">
                    {{ __('Admin') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')">
                    {{ __('Users') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.students.index')" :active="request()->routeIs('admin.students.*')">
                    {{ __('Students') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.rbac-matrix.index')" :active="request()->routeIs('admin.rbac-matrix.*')">
                    {{ __('RBAC Matrix') }}
                </x-responsive-nav-link>
            @elseif (auth()->user()->hasRole('Principal'))
                <x-responsive-nav-link :href="route('principal.dashboard')" :active="request()->routeIs('principal.dashboard')">
                    {{ __('Principal') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('principal.classes.index')" :active="request()->routeIs('principal.classes.*')">
                    {{ __('Classes') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('principal.subjects.index')" :active="request()->routeIs('principal.subjects.index')">
                    {{ __('Subjects') }}
                </x-responsive-nav-link>
                @can('manage_subject_assignments')
                    <x-responsive-nav-link :href="route('principal.subject-matrix.index')" :active="request()->routeIs('principal.subject-matrix.*') || request()->routeIs('principal.subjects.matrix.*')">
                        {{ __('Subject Matrix') }}
                    </x-responsive-nav-link>
                @endcan
                <x-responsive-nav-link :href="route('principal.timetable.settings.index')" :active="request()->routeIs('principal.timetable.settings.*')">
                    {{ __('Timetable Settings') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('principal.timetable.subject-rules.index')" :active="request()->routeIs('principal.timetable.subject-rules.*')">
                    {{ __('Subject Rules') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('principal.timetable.teacher-availability.index')" :active="request()->routeIs('principal.timetable.teacher-availability.*')">
                    {{ __('Teacher Availability') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('principal.timetable.generate.index')" :active="request()->routeIs('principal.timetable.generate.*')">
                    {{ __('Generate Timetable') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('principal.timetable.viewer.index')" :active="request()->routeIs('principal.timetable.viewer.*')">
                    {{ __('Timetable Viewer') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('principal.teacher-assignments.index')" :active="request()->routeIs('principal.teacher-assignments.*')">
                    {{ __('Teacher Assignments') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('principal.results.generator')" :active="request()->routeIs('principal.results.*')">
                    {{ __('Results') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('principal.analytics.teachers.index')" :active="request()->routeIs('principal.analytics.teachers.*') || request()->routeIs('principal.analytics.performance-insights.*')">
                    {{ __('Teacher Analytics') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('principal.medical.referrals.index')" :active="request()->routeIs('principal.medical.referrals.*')">
                    {{ __('Medical') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('reports.index')" :active="request()->routeIs('reports.index') || request()->routeIs('reports.pdf.*')">
                    {{ __('Reports') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('medical.reports.index')" :active="request()->routeIs('medical.reports.*')">
                    {{ __('Medical Reports') }}
                </x-responsive-nav-link>
            @elseif (auth()->user()->hasRole('Teacher'))
                <x-responsive-nav-link :href="route('teacher.dashboard')" :active="request()->routeIs('teacher.dashboard')">
                    {{ __('Teacher') }}
                </x-responsive-nav-link>
                @can('mark_attendance')
                    <x-responsive-nav-link :href="route('teacher.attendance.index')" :active="request()->routeIs('teacher.attendance.*')">
                        {{ __('Attendance') }}
                    </x-responsive-nav-link>
                @endcan
                @can('enter_marks')
                    <x-responsive-nav-link :href="route('teacher.exams.index')" :active="request()->routeIs('teacher.exams.*')">
                        {{ __('Examinations') }}
                    </x-responsive-nav-link>
                @endcan
                @can('view_own_mark_entries')
                    <x-responsive-nav-link :href="route('teacher.marks.entries.index')" :active="request()->routeIs('teacher.marks.entries.*')">
                        {{ __('My Mark Entries') }}
                    </x-responsive-nav-link>
                @endcan
                <x-responsive-nav-link :href="route('teacher.timetable.index')" :active="request()->routeIs('teacher.timetable.*')">
                    {{ __('Timetable') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('notifications.index')" :active="request()->routeIs('notifications.*')">
                    {{ __('Notifications') }}
                </x-responsive-nav-link>
            @elseif (auth()->user()->hasRole('Doctor'))
                <x-responsive-nav-link :href="route('doctor.dashboard')" :active="request()->routeIs('doctor.dashboard')">
                    {{ __('Doctor') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('doctor.medical.referrals.index')" :active="request()->routeIs('doctor.medical.referrals.*')">
                    {{ __('Referrals') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('notifications.index')" :active="request()->routeIs('notifications.*')">
                    {{ __('Notifications') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('medical.reports.index')" :active="request()->routeIs('medical.reports.*')">
                    {{ __('Reports') }}
                </x-responsive-nav-link>
            @elseif (auth()->user()->hasRole('Student'))
                <x-responsive-nav-link :href="route('student.dashboard')" :active="request()->routeIs('student.dashboard')">
                    {{ __('Student') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('student.results.index')" :active="request()->routeIs('student.results.*')">
                    {{ __('My Results') }}
                </x-responsive-nav-link>
            @endif
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
                <div class="mt-2 flex items-center justify-between">
                    <p class="text-xs text-gray-500">Unread Notifications: {{ $navUnreadCount }}</p>
                    @if ($navUnreadCount > 0)
                        <form method="POST" action="{{ route('notifications.read-all') }}">
                            @csrf
                            <button type="submit" class="text-xs font-medium text-indigo-600 hover:text-indigo-700">
                                Mark all read
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <x-responsive-nav-link :href="route('logout')">
                    {{ __('Log Out') }}
                </x-responsive-nav-link>
            </div>
        </div>
    </div>
</nav>

@if (auth()->check() && auth()->user()->hasRole('Principal'))
    <script>
        const globalStudentSearchInput = document.getElementById('globalStudentSearch');
        const globalStudentSearchResults = document.getElementById('globalStudentSearchResults');
        let globalSearchController = null;

        async function fetchGlobalStudents() {
            const q = (globalStudentSearchInput.value || '').trim();
            if (q.length < 2) {
                if (globalSearchController) {
                    globalSearchController.abort();
                }
                globalStudentSearchResults.classList.add('hidden');
                globalStudentSearchResults.innerHTML = '';
                return;
            }

            if (globalSearchController) {
                globalSearchController.abort();
            }

            globalSearchController = new AbortController();
            const params = new URLSearchParams({ q });
            const response = await fetch(`/api/search/students?${params.toString()}`, {
                headers: { 'Accept': 'application/json' },
                signal: globalSearchController.signal,
            }).catch((error) => {
                if (error?.name === 'AbortError') {
                    return null;
                }

                throw error;
            });

            if (!response) {
                return;
            }

            if (!response.ok) {
                globalStudentSearchResults.classList.add('hidden');
                return;
            }

            const result = await response.json();
            const rows = result.data || [];

            if (!rows.length) {
                globalStudentSearchResults.innerHTML = '<div class="px-3 py-2 text-sm text-gray-500">No matches found</div>';
                globalStudentSearchResults.classList.remove('hidden');
                return;
            }

            globalStudentSearchResults.innerHTML = rows.map(row => `
                <a href="${row.profile_url}" class="block border-b border-gray-100 px-3 py-2 hover:bg-gray-50">
                    <div class="text-sm font-medium text-gray-900">${window.NSMS.escapeHtml(row.name)} (${window.NSMS.escapeHtml(row.student_id)})</div>
                    <div class="text-xs text-gray-500">Father: ${window.NSMS.escapeHtml(row.father_name ?? '-')} | Class: ${window.NSMS.escapeHtml(row.class_name ?? '-')}</div>
                </a>
            `).join('');
            globalStudentSearchResults.classList.remove('hidden');
        }

        const debouncedGlobalSearch = window.NSMS.debounce(fetchGlobalStudents, 300);
        globalStudentSearchInput.addEventListener('input', debouncedGlobalSearch);

        document.addEventListener('click', (event) => {
            if (!globalStudentSearchResults.contains(event.target) && event.target !== globalStudentSearchInput) {
                globalStudentSearchResults.classList.add('hidden');
            }
        });
    </script>
@endif
