<div {{ $attributes->merge(['class' => 'overflow-x-auto rounded-xl border border-slate-200']) }}>
    <table class="min-w-full divide-y divide-slate-200 bg-white">
        {{ $slot }}
    </table>
</div>
