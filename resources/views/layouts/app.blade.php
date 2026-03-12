<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    @php
        $layoutUser = auth()->user();
        $isTeacher = $layoutUser?->hasRole('Teacher') ?? false;
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

        <title>{{ config('app.name', 'School Managment by Hour of Light') }}</title>

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
    </body>
</html>
