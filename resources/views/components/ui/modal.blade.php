@props([
    'name' => 'open',
    'title' => null,
])

<div
    x-cloak
    x-show="{{ $name }}"
    x-transition
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 px-4"
>
    <div @click.outside="{{ $name }} = false" class="w-full max-w-2xl rounded-xl border border-slate-200 bg-white shadow-2xl">
        @if($title || isset($header))
            <div class="border-b border-slate-200 px-5 py-4">
                @if(isset($header))
                    {{ $header }}
                @else
                    <h3 class="text-base font-semibold text-slate-900">{{ $title }}</h3>
                @endif
            </div>
        @endif

        <div class="px-5 py-4">
            {{ $slot }}
        </div>

        @if(isset($footer))
            <div class="flex items-center justify-end gap-2 border-t border-slate-200 px-5 py-3">
                {{ $footer }}
            </div>
        @endif
    </div>
</div>
