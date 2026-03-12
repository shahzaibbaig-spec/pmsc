@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'href' => null,
])

@php
    $variantClasses = [
        'primary' => 'bg-indigo-600 text-white hover:bg-indigo-700 focus:ring-indigo-500',
        'secondary' => 'bg-slate-700 text-white hover:bg-slate-800 focus:ring-slate-500',
        'success' => 'bg-emerald-600 text-white hover:bg-emerald-700 focus:ring-emerald-500',
        'danger' => 'bg-rose-600 text-white hover:bg-rose-700 focus:ring-rose-500',
        'outline' => 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 focus:ring-slate-400',
    ];

    $sizeClasses = [
        'sm' => 'min-h-9 px-3 py-1.5 text-xs',
        'md' => 'min-h-10 px-4 py-2 text-sm',
        'lg' => 'min-h-11 px-5 py-2.5 text-sm',
    ];

    $classes = ($variantClasses[$variant] ?? $variantClasses['primary']).' '.($sizeClasses[$size] ?? $sizeClasses['md']);
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => "inline-flex items-center justify-center gap-2 rounded-xl font-medium transition focus:outline-none focus:ring-2 focus:ring-offset-1 {$classes}"]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => "inline-flex items-center justify-center gap-2 rounded-xl font-medium transition focus:outline-none focus:ring-2 focus:ring-offset-1 {$classes}"]) }}>
        {{ $slot }}
    </button>
@endif
