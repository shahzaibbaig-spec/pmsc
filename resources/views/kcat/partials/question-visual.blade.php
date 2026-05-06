@php
    $questionText = trim((string) ($question->question_text ?? ''));
    $questionType = strtolower(trim((string) ($question->question_type ?? '')));
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

    $extractTokens = function (string $text): array {
        $normalized = strtoupper(trim($text));
        if ($normalized === '') {
            return [];
        }

        preg_match_all(
            '/\b(?:TRI|CIR|SQR|DIA|HEX|STAR|RECT|OVAL|ARC|RING|PENT|ARROW_(?:UP|RIGHT|DOWN|LEFT)|(?:UP|RIGHT|DOWN|LEFT)_ARROW)(?:-[A-Z0-9]+)*\b/',
            $normalized,
            $matches
        );

        return array_values(array_unique(array_map('trim', $matches[0] ?? [])));
    };

    $questionTokens = [];
    if (preg_match('/\[(.*?)\]/s', $questionText, $sequenceMatch) === 1) {
        $parts = preg_split('/\s*,\s*/', (string) ($sequenceMatch[1] ?? ''), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        foreach ($parts as $part) {
            $token = strtoupper(trim((string) $part));
            if ($token !== '' && $token !== '?') {
                $questionTokens[] = $token;
            }
        }
    }

    if ($questionTokens === []) {
        $questionTokens = $extractTokens($questionText);
    }

    if ($questionTokens === []) {
        $optionTokens = collect($question->options ?? [])
            ->flatMap(fn ($option): array => $extractTokens((string) ($option->option_text ?? '')))
            ->all();
        $questionTokens = array_values(array_unique($optionTokens));
    }

    $questionTokens = array_slice($questionTokens, 0, 12);

    $resolveGlyph = function (string $token): array {
        $normalized = strtoupper(trim($token));
        if ($normalized === '?') {
            return ['shape' => 'UNKNOWN', 'meta' => '?', 'label' => '?'];
        }

        $directionMap = [
            'UP_ARROW' => 'ARROW_UP',
            'RIGHT_ARROW' => 'ARROW_RIGHT',
            'DOWN_ARROW' => 'ARROW_DOWN',
            'LEFT_ARROW' => 'ARROW_LEFT',
        ];
        if (isset($directionMap[$normalized])) {
            return ['shape' => $directionMap[$normalized], 'meta' => '', 'label' => $normalized];
        }
        if (str_starts_with($normalized, 'ARROW_')) {
            return ['shape' => $normalized, 'meta' => '', 'label' => $normalized];
        }

        $parts = array_values(array_filter(explode('-', str_replace('_', '-', $normalized)), fn ($value) => trim((string) $value) !== ''));
        $knownShapes = ['TRI', 'CIR', 'SQR', 'DIA', 'HEX', 'STAR', 'RECT', 'OVAL', 'ARC', 'RING', 'PENT'];

        $shape = null;
        $metaParts = [];
        foreach ($parts as $part) {
            if ($shape === null && in_array($part, $knownShapes, true)) {
                $shape = $part;
                continue;
            }
            $metaParts[] = $part;
        }

        if ($shape === null && in_array($normalized, $knownShapes, true)) {
            $shape = $normalized;
        }

        return [
            'shape' => $shape ?? 'UNKNOWN',
            'meta' => implode('-', $metaParts),
            'label' => $normalized,
        ];
    };

    $renderGlyphSvg = function (array $glyph): string {
        $shape = (string) ($glyph['shape'] ?? 'UNKNOWN');
        $meta = trim((string) ($glyph['meta'] ?? ''));
        $metaEscaped = e($meta);

        $shapeMarkup = match ($shape) {
            'TRI' => '<polygon points="32,8 56,52 8,52" fill="#2563eb" />',
            'CIR' => '<circle cx="32" cy="32" r="22" fill="#2563eb" />',
            'SQR' => '<rect x="12" y="12" width="40" height="40" rx="2" fill="#2563eb" />',
            'DIA' => '<polygon points="32,6 58,32 32,58 6,32" fill="#2563eb" />',
            'HEX' => '<polygon points="16,14 48,14 60,32 48,50 16,50 4,32" fill="#2563eb" />',
            'STAR' => '<polygon points="32,6 39,24 58,24 42,36 48,56 32,44 16,56 22,36 6,24 25,24" fill="#2563eb" />',
            'RECT' => '<rect x="8" y="18" width="48" height="28" rx="2" fill="#2563eb" />',
            'OVAL' => '<ellipse cx="32" cy="32" rx="24" ry="16" fill="#2563eb" />',
            'ARC' => '<path d="M10 42 A22 22 0 1 1 54 42" fill="none" stroke="#2563eb" stroke-width="8" stroke-linecap="round" />',
            'RING' => '<circle cx="32" cy="32" r="20" fill="none" stroke="#2563eb" stroke-width="8" />',
            'PENT' => '<polygon points="32,6 56,24 47,54 17,54 8,24" fill="#2563eb" />',
            'ARROW_UP' => '<polygon points="32,8 56,36 42,36 42,56 22,56 22,36 8,36" fill="#2563eb" />',
            'ARROW_RIGHT' => '<polygon points="56,32 28,8 28,22 8,22 8,42 28,42 28,56" fill="#2563eb" />',
            'ARROW_DOWN' => '<polygon points="32,56 56,28 42,28 42,8 22,8 22,28 8,28" fill="#2563eb" />',
            'ARROW_LEFT' => '<polygon points="8,32 36,8 36,22 56,22 56,42 36,42 36,56" fill="#2563eb" />',
            default => '<rect x="8" y="8" width="48" height="48" rx="8" fill="#0f172a" />',
        };

        return '<svg viewBox="0 0 64 64" class="h-12 w-12" aria-hidden="true">'.$shapeMarkup.'</svg>'
            .($meta !== '' ? '<span class="text-[10px] font-semibold text-slate-600">'.$metaEscaped.'</span>' : '');
    };

    $isShapeQuestion = in_array($questionType, $shapeQuestionTypes, true);
    $questionImageUrl = $question->question_image_url ?? null;
@endphp

@if ($questionImageUrl || ($isShapeQuestion && $questionTokens !== []))
    <div class="mt-3 space-y-2">
        @if ($questionImageUrl)
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-slate-50 p-2">
                <img
                    src="{{ $questionImageUrl }}"
                    alt="Question diagram"
                    class="mx-auto max-h-64 w-auto object-contain"
                    loading="lazy"
                    referrerpolicy="no-referrer"
                    onerror="this.closest('div').style.display='none';"
                >
            </div>
        @elseif ($isShapeQuestion)
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Diagram Preview</p>
                <div class="grid grid-cols-3 gap-2 sm:grid-cols-4 md:grid-cols-6">
                    @foreach ($questionTokens as $token)
                        @php($glyph = $resolveGlyph((string) $token))
                        <div class="flex flex-col items-center justify-center rounded-lg border border-slate-200 bg-white p-2">
                            {!! $renderGlyphSvg($glyph) !!}
                            <span class="mt-1 text-[10px] font-semibold text-slate-700">{{ $glyph['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endif
