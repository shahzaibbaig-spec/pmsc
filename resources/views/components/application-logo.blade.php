@php
    /** @var \App\Models\SchoolSetting|null $logoSchool */
    $logoSchool = \App\Models\SchoolSetting::cached();
    $logoPath = (string) ($logoSchool?->logo_path ?? '');
    $logoUrl = $logoPath !== ''
        ? (str_starts_with($logoPath, 'http') ? $logoPath : \Illuminate\Support\Facades\Storage::disk('public')->url($logoPath))
        : asset('favicon.ico');
@endphp

<img src="{{ $logoUrl }}" alt="Hour of Light Logo" {{ $attributes->merge(['class' => 'object-contain']) }}>
