<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    @php
        $layoutUser = auth()->user();
        $isTeacher = $layoutUser?->hasRole('Teacher') ?? false;
        $isDashboardRoute = request()->routeIs('dashboard') || request()->routeIs('*.dashboard');
        $academicPopupNotifications = collect();
        $hasAcademicNotificationsTable = \Illuminate\Support\Facades\Schema::hasTable('academic_notifications');
        if ($layoutUser && $isDashboardRoute && $hasAcademicNotificationsTable) {
            $academicPopupNotifications = \App\Models\AcademicNotification::query()
                ->where('user_id', (int) $layoutUser->id)
                ->where('is_read', false)
                ->orderByDesc('sent_at')
                ->orderByDesc('id')
                ->limit(5)
                ->get(['id', 'title', 'message', 'sent_at']);
        }
    @endphp
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @if ($isTeacher)
            <meta name="theme-color" content="#0f172a">
            <link rel="manifest" href="{{ asset('manifest-teacher.json') }}">
            <link rel="apple-touch-icon" href="{{ asset('favicon.ico') }}">
        @endif

        <title>{{ config('app.name', 'Houroflight SMS') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <script>
            window.NSMS = window.NSMS || {};
            window.NSMS.debounce = window.NSMS.debounce || function (callback, wait = 300) {
                let timer = null;
                return function (...args) {
                    clearTimeout(timer);
                    timer = setTimeout(() => callback.apply(this, args), wait);
                };
            };
            window.NSMS.escapeHtml = window.NSMS.escapeHtml || function (value) {
                if (value === null || value === undefined) {
                    return '';
                }

                return String(value)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            };
            window.NSMS.lazyInit = window.NSMS.lazyInit || function (target, callback, options = {}) {
                const element = typeof target === 'string' ? document.querySelector(target) : target;
                if (!element || typeof callback !== 'function') {
                    return;
                }

                const invoke = () => Promise.resolve(callback()).catch(() => null);
                if (!('IntersectionObserver' in window)) {
                    invoke();
                    return;
                }

                let initialized = false;
                const observer = new IntersectionObserver((entries) => {
                    const entry = entries[0];
                    if (!entry || !entry.isIntersecting || initialized) {
                        return;
                    }

                    initialized = true;
                    observer.disconnect();
                    invoke();
                }, {
                    rootMargin: options.rootMargin || '200px 0px',
                    threshold: options.threshold || 0.01,
                });

                observer.observe(element);
            };
        </script>
        <style>[x-cloak]{display:none !important;}</style>
    </head>
    <body class="bg-slate-100 font-sans text-slate-900 antialiased" x-data="{ sidebarOpen: false }">
        <div class="min-h-screen">
            @include('layouts.partials.sidebar')

            <div class="lg:pl-72">
                @include('layouts.partials.topbar')

                <main class="px-4 py-5 sm:px-6 lg:px-8">
                    @isset($header)
                        <div class="mb-6 rounded-xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
                            {{ $header }}
                        </div>
                    @endisset

                    {{ $slot }}
                </main>
            </div>
        </div>

        @if ($isTeacher)
            <script>
                (() => {
                    if (!('serviceWorker' in navigator)) {
                        return;
                    }

                    window.addEventListener('load', () => {
                        navigator.serviceWorker.register('{{ asset('sw-teacher.js') }}').catch(() => null);
                    });
                })();
            </script>
        @endif

        @if ($academicPopupNotifications->isNotEmpty())
            <div id="academicNotificationPopup" class="fixed bottom-4 right-4 z-50 w-[calc(100%-2rem)] max-w-sm rounded-2xl border border-indigo-200 bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-indigo-100 px-4 py-3">
                    <div>
                        <p class="text-sm font-semibold text-slate-900">Academic Reminders</p>
                        <p id="academicPopupCount" class="text-xs text-slate-500">{{ $academicPopupNotifications->count() }} unread</p>
                    </div>
                    <button id="academicPopupCloseBtn" type="button" class="rounded-md px-2 py-1 text-xs font-medium text-slate-500 hover:bg-slate-100 hover:text-slate-700">
                        Close
                    </button>
                </div>

                <div id="academicPopupRows" class="max-h-96 overflow-y-auto">
                    @foreach ($academicPopupNotifications as $notification)
                        <div id="academic-popup-row-{{ $notification->id }}" class="border-b border-slate-100 px-4 py-3">
                            <p class="text-sm font-semibold text-slate-900">{{ $notification->title }}</p>
                            <p class="mt-1 text-xs text-slate-600">{{ $notification->message }}</p>
                            <div class="mt-2 flex items-center justify-between">
                                <p class="text-[11px] text-slate-500">{{ $notification->sent_at?->diffForHumans() ?? '-' }}</p>
                                <button
                                    type="button"
                                    class="academic-mark-read-btn text-[11px] font-medium text-indigo-600 hover:text-indigo-700"
                                    data-endpoint="{{ route('academic-notifications.read', $notification) }}"
                                    data-row-id="academic-popup-row-{{ $notification->id }}"
                                >
                                    Mark read
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="flex items-center justify-between gap-2 px-4 py-3">
                    <button id="academicPopupReadAllBtn" type="button" class="inline-flex min-h-9 items-center rounded-lg border border-indigo-300 px-3 py-1 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">
                        Mark all read
                    </button>
                    <a href="{{ route('academic-calendar.index') }}" class="text-xs font-medium text-slate-600 hover:text-slate-800">
                        Open Academic Calendar
                    </a>
                </div>
            </div>

            <script>
                (() => {
                    const popup = document.getElementById('academicNotificationPopup');
                    const rowsContainer = document.getElementById('academicPopupRows');
                    const countLabel = document.getElementById('academicPopupCount');
                    const closeBtn = document.getElementById('academicPopupCloseBtn');
                    const readAllBtn = document.getElementById('academicPopupReadAllBtn');
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    if (!popup || !rowsContainer || !countLabel || !csrfToken) {
                        return;
                    }

                    const refreshState = () => {
                        const remaining = rowsContainer.querySelectorAll('[id^="academic-popup-row-"]').length;
                        countLabel.textContent = `${remaining} unread`;
                        if (remaining === 0) {
                            popup.remove();
                        }
                    };

                    const postRequest = async (endpoint) => {
                        const response = await fetch(endpoint, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                            },
                        });

                        if (!response.ok) {
                            throw new Error('Request failed.');
                        }

                        return response.json().catch(() => ({}));
                    };

                    rowsContainer.addEventListener('click', async (event) => {
                        const target = event.target;
                        if (!target.classList.contains('academic-mark-read-btn')) {
                            return;
                        }

                        target.disabled = true;
                        const endpoint = target.getAttribute('data-endpoint');
                        const rowId = target.getAttribute('data-row-id');
                        try {
                            await postRequest(endpoint);
                            const row = document.getElementById(rowId);
                            if (row) {
                                row.remove();
                            }
                            refreshState();
                        } catch (_) {
                            target.disabled = false;
                        }
                    });

                    readAllBtn?.addEventListener('click', async () => {
                        readAllBtn.disabled = true;
                        try {
                            await postRequest(@json(route('academic-notifications.read-all')));
                            popup.remove();
                        } catch (_) {
                            readAllBtn.disabled = false;
                        }
                    });

                    closeBtn?.addEventListener('click', () => {
                        popup.remove();
                    });
                })();
            </script>
        @endif
    </body>
</html>
