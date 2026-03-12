@props([
    'infoId' => 'paginationInfo',
    'prevId' => 'prevPageBtn',
    'nextId' => 'nextPageBtn',
])

<div class="flex items-center justify-between gap-3 px-1 py-3">
    <p id="{{ $infoId }}" class="text-sm text-slate-600">-</p>
    <div class="flex items-center gap-2">
        <button id="{{ $prevId }}" type="button" class="inline-flex min-h-10 items-center rounded-xl border border-slate-300 px-4 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-50">
            Previous
        </button>
        <button id="{{ $nextId }}" type="button" class="inline-flex min-h-10 items-center rounded-xl border border-slate-300 px-4 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-50">
            Next
        </button>
    </div>
</div>
