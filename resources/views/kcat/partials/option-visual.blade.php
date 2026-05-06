@php
    $questionType = strtolower(trim((string) ($questionType ?? '')));
    $shapeQuestionTypes = [
        'matrix',
        'pattern_sequence',
        'odd_shape',
        'shape_series',
        'rotation',
        'mirror_image',
        'folding',
        'cube_logic',
        'image_mcq',
    ];
    $isShapeContext = in_array($questionType, $shapeQuestionTypes, true);

    $optionText = trim((string) ($option->option_text ?? ''));
    $optionImageUrl = $option->option_image_url ?? null;

    $token = null;
    if ($isShapeContext && $optionText !== '') {
        if (preg_match(
            '/\b(?:TRI|CIR|SQR|DIA|HEX|STAR|RECT|OVAL|ARC|RING|PENT|ARROW_(?:UP|RIGHT|DOWN|LEFT)|(?:UP|RIGHT|DOWN|LEFT)_ARROW)(?:-[A-Z0-9]+)*\b/i',
            $optionText,
            $match
        ) === 1) {
            $token = strtoupper(trim((string) ($match[0] ?? '')));
        }
    }

    $glyphSvg = null;
    if ($token !== null) {
        $normalized = str_replace('_', '-', $token);
        $parts = array_values(array_filter(explode('-', $normalized), fn ($value) => trim((string) $value) !== ''));
        $knownShapes = ['TRI', 'CIR', 'SQR', 'DIA', 'HEX', 'STAR', 'RECT', 'OVAL', 'ARC', 'RING', 'PENT'];
        $shape = null;
        foreach ($parts as $part) {
            if (in_array($part, $knownShapes, true)) {
                $shape = $part;
                break;
            }
        }

        if (str_starts_with($token, 'ARROW_')) {
            $shape = $token;
        } elseif (str_ends_with($token, '_ARROW')) {
            $direction = str_replace('_ARROW', '', $token);
            $shape = 'ARROW_'.$direction;
        }

        $shapeMarkup = match ($shape) {
            'TRI' => '<polygon points="16,4 30,28 2,28" fill="#2563eb" />',
            'CIR' => '<circle cx="16" cy="16" r="12" fill="#2563eb" />',
            'SQR' => '<rect x="5" y="5" width="22" height="22" rx="2" fill="#2563eb" />',
            'DIA' => '<polygon points="16,2 30,16 16,30 2,16" fill="#2563eb" />',
            'HEX' => '<polygon points="7,4 25,4 30,16 25,28 7,28 2,16" fill="#2563eb" />',
            'STAR' => '<polygon points="16,2 20,11 30,11 22,17 25,28 16,22 7,28 10,17 2,11 12,11" fill="#2563eb" />',
            'RECT' => '<rect x="3" y="9" width="26" height="14" rx="2" fill="#2563eb" />',
            'OVAL' => '<ellipse cx="16" cy="16" rx="12" ry="8" fill="#2563eb" />',
            'ARC' => '<path d="M4 21 A12 12 0 1 1 28 21" fill="none" stroke="#2563eb" stroke-width="4" stroke-linecap="round" />',
            'RING' => '<circle cx="16" cy="16" r="10" fill="none" stroke="#2563eb" stroke-width="4" />',
            'PENT' => '<polygon points="16,2 28,11 24,28 8,28 4,11" fill="#2563eb" />',
            'ARROW_UP' => '<polygon points="16,2 30,16 22,16 22,30 10,30 10,16 2,16" fill="#2563eb" />',
            'ARROW_RIGHT' => '<polygon points="30,16 16,2 16,10 2,10 2,22 16,22 16,30" fill="#2563eb" />',
            'ARROW_DOWN' => '<polygon points="16,30 30,16 22,16 22,2 10,2 10,16 2,16" fill="#2563eb" />',
            'ARROW_LEFT' => '<polygon points="2,16 16,2 16,10 30,10 30,22 16,22 16,30" fill="#2563eb" />',
            default => '<rect x="4" y="4" width="24" height="24" rx="6" fill="#0f172a" />',
        };

        $glyphSvg = '<svg viewBox="0 0 32 32" class="h-8 w-8 shrink-0" aria-hidden="true">'.$shapeMarkup.'</svg>';
    }
@endphp

@if ($optionImageUrl)
    <img
        src="{{ $optionImageUrl }}"
        alt="Option diagram"
        class="h-14 w-20 rounded-md border border-slate-200 object-contain"
        loading="lazy"
        referrerpolicy="no-referrer"
        onerror="this.style.display='none';"
    >
@elseif ($glyphSvg)
    {!! $glyphSvg !!}
@endif

<span>{{ $optionText !== '' ? $optionText : 'Option' }}</span>
