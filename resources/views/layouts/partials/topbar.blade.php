@php
    $topbarUser = auth()->user();
    $topbarSchool = \App\Models\SchoolSetting::cached();
    $topbarUnreadCount = $topbarUser?->unreadNotifications()->count() ?? 0;
    $topbarNotifications = $topbarUser?->notifications()->latest()->limit(8)->get() ?? collect();
    $canGlobalSearch = $topbarUser?->hasAnyRole(['Admin', 'Principal']) ?? false;

    $logoUrl = asset('favicon.ico');
    if (! empty($topbarSchool?->logo_path)) {
        $logoUrl = str_starts_with($topbarSchool->logo_path, 'http')
            ? $topbarSchool->logo_path
            : \Illuminate\Support\Facades\Storage::url($topbarSchool->logo_path);
    }
@endphp

<header class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur">
    <div class="flex h-16 items-center justify-between gap-3 px-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-3">
            <button type="button" class="rounded-md border border-slate-200 p-2 text-slate-600 hover:bg-slate-50 lg:hidden" @click="sidebarOpen = true">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm1 4a1 1 0 100 2h12a1 1 0 100-2H4z" clip-rule="evenodd" />
                </svg>
            </button>

            <div class="hidden items-center gap-3 sm:flex">
                <img src="{{ $logoUrl }}" alt="School Logo" class="h-9 w-9 rounded-lg border border-slate-200 object-cover">
                <div>
                    <p class="text-sm font-semibold leading-4 text-slate-900">{{ $topbarSchool?->school_name ?? config('app.name', 'School Management') }}</p>
                    <p class="text-xs text-slate-500">School Management System</p>
                </div>
            </div>
        </div>

        <div class="flex flex-1 items-center justify-end gap-3">
            @if($canGlobalSearch)
                <div class="relative hidden w-full max-w-md lg:block">
                    <input
                        id="globalSearchInput"
                        type="text"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-700 placeholder-slate-400 focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-100"
                        placeholder="Search students by name, ID, father name, class..."
                        autocomplete="off"
                    >
                    <div id="globalSearchResults" class="absolute left-0 right-0 top-12 hidden max-h-80 overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-xl"></div>
                </div>
            @endif

            <div class="relative" x-data="{ open: false }">
                <button type="button" class="relative rounded-xl border border-slate-200 bg-white p-2.5 text-slate-600 hover:bg-slate-50" @click="open = !open">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10 2a4 4 0 00-4 4v1.17a2 2 0 01-.59 1.42L4.3 9.7A1 1 0 005 11h10a1 1 0 00.7-1.7l-1.11-1.11A2 2 0 0114 7.17V6a4 4 0 00-4-4z"/>
                        <path d="M8 13a2 2 0 104 0H8z"/>
                    </svg>
                    @if($topbarUnreadCount > 0)
                        <span class="absolute -right-1 -top-1 inline-flex min-h-5 min-w-5 items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-semibold text-white">
                            {{ $topbarUnreadCount > 99 ? '99+' : $topbarUnreadCount }}
                        </span>
                    @endif
                </button>

                <div
                    x-cloak
                    x-show="open"
                    @click.outside="open = false"
                    x-transition
                    class="absolute right-0 mt-2 w-80 rounded-xl border border-slate-200 bg-white shadow-xl"
                >
                    <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                        <p class="text-sm font-semibold text-slate-900">Notifications</p>
                        @if($topbarUnreadCount > 0)
                            <form method="POST" action="{{ route('notifications.read-all') }}">
                                @csrf
                                <button type="submit" class="text-xs font-medium text-indigo-600 hover:text-indigo-700">Mark all read</button>
                            </form>
                        @endif
                    </div>

                    <div class="max-h-96 overflow-y-auto">
                        @forelse($topbarNotifications as $note)
                            <div class="{{ $note->read_at ? 'bg-white' : 'bg-indigo-50/50' }} border-b border-slate-100 px-4 py-3">
                                <p class="text-sm font-semibold text-slate-900">{{ $note->data['title'] ?? 'Notification' }}</p>
                                <p class="mt-1 text-xs text-slate-600">{{ $note->data['message'] ?? '-' }}</p>
                                <div class="mt-2 flex items-center justify-between">
                                    <span class="text-[11px] text-slate-500">{{ $note->created_at?->diffForHumans() }}</span>
                                    @if(! $note->read_at)
                                        <form method="POST" action="{{ route('notifications.read', $note->id) }}">
                                            @csrf
                                            <button type="submit" class="text-[11px] font-medium text-indigo-600 hover:text-indigo-700">Mark read</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="px-4 py-6 text-sm text-slate-500">No notifications yet.</p>
                        @endforelse
                    </div>

                    <a href="{{ route('notifications.index') }}" class="block rounded-b-xl border-t border-slate-200 px-4 py-2.5 text-center text-xs font-semibold text-indigo-600 hover:bg-indigo-50">
                        Open Notification Center
                    </a>
                </div>
            </div>

            <div class="relative" x-data="{ open: false }">
                <button type="button" class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-50" @click="open = !open">
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-100 text-xs font-semibold text-indigo-700">
                        {{ strtoupper(substr($topbarUser?->name ?? 'U', 0, 1)) }}
                    </span>
                    <span class="hidden text-sm font-medium sm:inline">{{ $topbarUser?->name }}</span>
                </button>

                <div
                    x-cloak
                    x-show="open"
                    @click.outside="open = false"
                    x-transition
                    class="absolute right-0 mt-2 w-48 rounded-xl border border-slate-200 bg-white py-1 shadow-xl"
                >
                    <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Profile</a>
                    <a href="{{ route('logout') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Logout</a>
                </div>
            </div>
        </div>
    </div>
</header>

@if($canGlobalSearch)
    <script>
        (() => {
            const input = document.getElementById('globalSearchInput');
            const results = document.getElementById('globalSearchResults');
            if (!input || !results) {
                return;
            }

            let controller = null;

            const renderEmpty = (text) => {
                results.innerHTML = `<div class="px-4 py-3 text-sm text-slate-500">${window.NSMS.escapeHtml(text)}</div>`;
                results.classList.remove('hidden');
            };

            const fetchStudents = window.NSMS.debounce(async () => {
                const q = input.value.trim();
                if (q.length < 2) {
                    results.classList.add('hidden');
                    results.innerHTML = '';
                    if (controller) {
                        controller.abort();
                    }
                    return;
                }

                if (controller) {
                    controller.abort();
                }

                controller = new AbortController();
                const response = await fetch(`/api/search/students?q=${encodeURIComponent(q)}`, {
                    headers: { 'Accept': 'application/json' },
                    signal: controller.signal,
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
                    renderEmpty('Search failed.');
                    return;
                }

                const payload = await response.json();
                const rows = payload.data || [];
                if (!rows.length) {
                    renderEmpty('No students found.');
                    return;
                }

                results.innerHTML = rows.map((row) => `
                    <a href="${row.profile_url}" class="block border-b border-slate-100 px-4 py-3 hover:bg-slate-50">
                        <p class="text-sm font-semibold text-slate-900">${window.NSMS.escapeHtml(row.name)} (${window.NSMS.escapeHtml(row.student_id)})</p>
                        <p class="text-xs text-slate-600">Father: ${window.NSMS.escapeHtml(row.father_name || '-')} | Class: ${window.NSMS.escapeHtml(row.class_name || '-')}</p>
                    </a>
                `).join('');
                results.classList.remove('hidden');
            }, 300);

            input.addEventListener('input', fetchStudents);
            document.addEventListener('click', (event) => {
                if (!results.contains(event.target) && event.target !== input) {
                    results.classList.add('hidden');
                }
            });
        })();
    </script>
@endif
