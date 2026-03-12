@props([
    'label' => null,
    'name' => null,
    'id' => null,
    'type' => 'text',
    'value' => null,
])

@php
    $inputId = $id ?: $name ?: 'input_'.uniqid();
@endphp

<div {{ $attributes->only('class')->merge(['class' => '']) }}>
    @if($label)
        <label for="{{ $inputId }}" class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
            {{ $label }}
        </label>
    @endif

    <input
        id="{{ $inputId }}"
        @if($name) name="{{ $name }}" @endif
        type="{{ $type }}"
        value="{{ old($name ?? '', $value) }}"
        {{ $attributes->except('class')->merge(['class' => 'block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100']) }}
    >

    @if($name)
        @error($name)
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    @endif
</div>
