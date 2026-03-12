@props([
    'variant' => 'default',
])

@php
    $classes = [
        'default' => 'bg-slate-100 text-slate-700',
        'success' => 'bg-emerald-100 text-emerald-700',
        'warning' => 'bg-amber-100 text-amber-700',
        'danger' => 'bg-rose-100 text-rose-700',
        'info' => 'bg-indigo-100 text-indigo-700',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold '.($classes[$variant] ?? $classes['default'])]) }}>
    {{ $slot }}
</span>
