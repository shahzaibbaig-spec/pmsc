@props([
    'title' => null,
    'subtitle' => null,
])

<section {{ $attributes->merge(['class' => 'rounded-xl border border-slate-200 bg-white shadow-sm']) }}>
    @if($title || $subtitle)
        <header class="border-b border-slate-100 px-5 py-4">
            @if($title)
                <h3 class="text-sm font-semibold text-slate-900">{{ $title }}</h3>
            @endif
            @if($subtitle)
                <p class="mt-1 text-xs text-slate-500">{{ $subtitle }}</p>
            @endif
        </header>
    @endif

    <div class="p-5">
        {{ $slot }}
    </div>
</section>
