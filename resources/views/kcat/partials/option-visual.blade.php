@php
    $questionType = strtolower(trim((string) ($questionType ?? '')));
    $optionText = trim((string) ($option->option_text ?? ''));
    $optionImageUrl = $option->option_image_url ?? null;
    $generatedOptionImageUrl = $optionImageUrl ?: \App\Support\KcatVisualRenderer::optionDataUri($questionType, $optionText);
@endphp

@if ($generatedOptionImageUrl)
    <img
        src="{{ $generatedOptionImageUrl }}"
        alt="Option diagram"
        class="h-14 w-20 rounded-md border border-slate-200 object-contain"
        loading="lazy"
        referrerpolicy="no-referrer"
        onerror="this.style.display='none';"
    >
@endif

<span>{{ $optionText !== '' ? $optionText : 'Option' }}</span>
