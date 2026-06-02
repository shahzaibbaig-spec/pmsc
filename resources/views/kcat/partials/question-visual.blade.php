@php
    $questionImageUrl = $question->question_image_url ?? null;
@endphp

@if ($questionImageUrl)
    <div class="mt-3 overflow-hidden rounded-xl border border-slate-200 bg-slate-50 p-2">
        <img
            src="{{ $questionImageUrl }}"
            alt="Question diagram"
            class="mx-auto max-h-72 w-full object-contain"
            loading="lazy"
            referrerpolicy="no-referrer"
            onerror="this.closest('div').style.display='none';"
        >
    </div>
@endif
