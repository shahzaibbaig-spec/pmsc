<?php

namespace App\Support;

class KcatVisualRenderer
{
    /**
     * @var array<int, string>
     */
    private const VISUAL_TYPES = [
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

    /**
     * @var array<string, string>
     */
    private const TOKEN_ALIASES = [
        'CIRCLE' => 'CIR',
        'SQUARE' => 'SQR',
        'TRIANGLE' => 'TRI',
        'RECTANGLE' => 'RECT',
        'DIAMOND' => 'DIA',
        'HEXAGON' => 'HEX',
        'PENTAGON' => 'PENT',
    ];

    public static function questionDataUri(string $questionType, string $questionText): ?string
    {
        $svg = self::questionSvg($questionType, $questionText);

        return $svg !== null ? 'data:image/svg+xml;base64,'.base64_encode($svg) : null;
    }

    public static function optionDataUri(string $questionType, string $optionText): ?string
    {
        $questionType = strtolower(trim($questionType));
        $optionText = trim($optionText);

        if ($optionText === '') {
            return null;
        }

        $token = self::firstShapeToken($optionText);
        if ($token !== null) {
            return 'data:image/svg+xml;base64,'.base64_encode(self::tileSvg($token, 128, true));
        }

        if (! in_array($questionType, self::VISUAL_TYPES, true) && ! self::looksVisualOption($optionText)) {
            return null;
        }

        if (preg_match('/^\([^)]+\)$/', $optionText) === 1) {
            return 'data:image/svg+xml;base64,'.base64_encode(self::coordinateOptionSvg($optionText));
        }

        if (preg_match('/^(North|East|South|West|Up|Right|Down|Left)$/i', $optionText) === 1) {
            return 'data:image/svg+xml;base64,'.base64_encode(self::directionOptionSvg($optionText));
        }

        return null;
    }

    public static function questionSvg(string $questionType, string $questionText): ?string
    {
        $questionType = strtolower(trim($questionType));
        $questionText = trim($questionText);

        if ($questionText === '') {
            return null;
        }

        if (! in_array($questionType, self::VISUAL_TYPES, true)) {
            return self::fallbackQuestionSvg($questionText);
        }

        return match ($questionType) {
            'matrix' => self::matrixSvg($questionText),
            'pattern_sequence', 'shape_series' => self::sequenceSvg($questionText, 'shape_series' === $questionType ? 'Shape Series' : 'Pattern Sequence'),
            'odd_shape' => self::oddShapeSvg($questionText),
            'rotation' => self::rotationSvg($questionText),
            'mirror_image' => self::mirrorSvg($questionText),
            'folding' => self::foldingSvg($questionText),
            'cube_logic' => self::cubeSvg($questionText),
            'image_mcq' => self::sequenceSvg($questionText, 'Visual Question'),
            default => null,
        };
    }

    private static function sequenceSvg(string $text, string $title): ?string
    {
        $tokens = self::tokensFromBracket($text);
        if ($tokens === []) {
            $tokens = self::shapeTokens($text);
        }

        if ($tokens === []) {
            return null;
        }

        if (! in_array('?', $tokens, true)) {
            $tokens[] = '?';
        }

        $tiles = '';
        $count = count($tokens);
        $tileWidth = min(112, max(72, (int) floor(760 / max($count, 1))));
        $startX = (int) ((920 - ($tileWidth * $count)) / 2);
        foreach ($tokens as $index => $token) {
            $x = $startX + ($index * $tileWidth);
            $tiles .= self::tileGroup($token, $x + 8, 115, $tileWidth - 16, 118, true);
            if ($index < $count - 1) {
                $tiles .= '<path d="M'.($x + $tileWidth - 10).' 174 L'.($x + $tileWidth + 2).' 174" stroke="#94a3b8" stroke-width="2" stroke-linecap="round"/>';
            }
        }

        return self::frameSvg($title, 'Choose the missing item that continues the visual pattern.', $tiles);
    }

    private static function fallbackQuestionSvg(string $text): ?string
    {
        $normalized = strtolower($text);

        if (str_contains($normalized, 'arrow') && str_contains($normalized, 'rotat')) {
            return self::rotationSvg($text);
        }

        if (str_contains($normalized, 'fold')) {
            return self::foldingSvg($text);
        }

        $tokens = self::shapeTokens($text);
        if (count($tokens) >= 2 && preg_match('/\b(pattern|sequence|series|matrix|shape)\b/i', $text) === 1) {
            return self::sequenceSvg($text, 'Visual Pattern');
        }

        return null;
    }

    private static function matrixSvg(string $text): ?string
    {
        $rows = self::matrixRows($text);
        if ($rows === []) {
            return self::sequenceSvg($text, 'Matrix Pattern');
        }

        $rowCount = count($rows);
        $colCount = max(array_map('count', $rows));
        $cell = $rowCount >= 3 ? 92 : 106;
        $gridWidth = $cell * $colCount;
        $gridHeight = $cell * $rowCount;
        $startX = (int) ((920 - $gridWidth) / 2);
        $startY = $rowCount >= 3 ? 82 : 102;
        $body = '<rect x="'.($startX - 10).'" y="'.($startY - 10).'" width="'.($gridWidth + 20).'" height="'.($gridHeight + 20).'" rx="16" fill="#f8fafc" stroke="#cbd5e1"/>';

        foreach ($rows as $rowIndex => $row) {
            for ($colIndex = 0; $colIndex < $colCount; $colIndex++) {
                $token = $row[$colIndex] ?? '';
                $body .= self::tileGroup($token, $startX + ($colIndex * $cell) + 8, $startY + ($rowIndex * $cell) + 8, $cell - 16, $cell - 16, false);
            }
        }

        return self::frameSvg('Matrix Pattern', 'Study the row and column relationship, then choose the missing tile.', $body);
    }

    private static function oddShapeSvg(string $text): ?string
    {
        $tokens = self::shapeTokens($text);
        if ($tokens === []) {
            return null;
        }

        $tokens = array_slice($tokens, 0, 6);
        $tiles = '';
        $tileWidth = 130;
        $startX = (int) ((920 - ($tileWidth * count($tokens))) / 2);
        foreach ($tokens as $index => $token) {
            $tiles .= self::tileGroup($token, $startX + ($index * $tileWidth) + 10, 112, 110, 128, true);
        }

        return self::frameSvg('Odd Shape', 'Find the item that does not belong with the others.', $tiles);
    }

    private static function rotationSvg(string $text): ?string
    {
        $start = 'North';
        if (preg_match('/starts facing\s+(North|East|South|West)/i', $text, $match) === 1) {
            $start = ucfirst(strtolower($match[1]));
        }

        preg_match_all('/(\d+)\s*(?:[^A-Za-z0-9\s]+|degrees?|deg)?\s*(clockwise|counterclockwise)/i', $text, $matches, PREG_SET_ORDER);
        $steps = [];
        foreach ($matches as $match) {
            $steps[] = ((int) $match[1]).' '.(strtolower($match[2]) === 'clockwise' ? 'CW' : 'CCW');
        }

        $body = '<g transform="translate(130 86)">'.self::compassSvg($start, 160).'</g>';
        $body .= '<path d="M330 175 C390 100 470 100 530 175" fill="none" stroke="#2563eb" stroke-width="5" stroke-linecap="round"/>';
        $body .= '<path d="M522 160 L548 178 L518 190" fill="none" stroke="#2563eb" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>';
        $body .= '<g transform="translate(605 86)">'.self::compassSvg('?', 160).'</g>';
        $body .= '<text x="460" y="215" text-anchor="middle" font-family="Arial, sans-serif" font-size="22" font-weight="700" fill="#0f172a">'.self::escape(implode('  +  ', $steps)).'</text>';
        $body .= '<text x="210" y="282" text-anchor="middle" font-family="Arial, sans-serif" font-size="18" font-weight="700" fill="#475569">Start: '.self::escape($start).'</text>';
        $body .= '<text x="685" y="282" text-anchor="middle" font-family="Arial, sans-serif" font-size="18" font-weight="700" fill="#475569">Final direction?</text>';

        return self::frameSvg('Rotation', 'Track the arrow turns in order.', $body);
    }

    private static function mirrorSvg(string $text): ?string
    {
        $pointName = 'P';
        $x = 2;
        $y = 2;
        if (preg_match('/Point\s+([A-Z])(?:\s+starts)?\s+at\s+\((-?\d+),\s*(-?\d+)\)/i', $text, $match) === 1) {
            $pointName = strtoupper($match[1]);
            $x = (int) $match[2];
            $y = (int) $match[3];
        }

        $axis = str_contains(strtolower($text), 'across the x-axis') ? 'x-axis' : 'y-axis';
        if (str_contains(strtolower($text), 'then across the x-axis')) {
            $axis = 'y-axis, then x-axis';
        }

        $plot = function (int $px, int $py): array {
            return [460 + ($px * 32), 180 - ($py * 32)];
        };
        [$cx, $cy] = $plot($x, $y);
        $axisLine = str_contains($axis, 'x-axis')
            ? '<line x1="210" y1="180" x2="710" y2="180" stroke="#ef4444" stroke-width="4" stroke-dasharray="10 8"/>'
            : '<line x1="460" y1="50" x2="460" y2="310" stroke="#ef4444" stroke-width="4" stroke-dasharray="10 8"/>';

        if ($axis === 'y-axis, then x-axis') {
            $axisLine = '<line x1="460" y1="50" x2="460" y2="310" stroke="#ef4444" stroke-width="4" stroke-dasharray="10 8"/>'
                .'<line x1="210" y1="180" x2="710" y2="180" stroke="#f97316" stroke-width="4" stroke-dasharray="10 8"/>';
        }

        $body = '<g>'
            .'<rect x="200" y="40" width="520" height="280" rx="18" fill="#f8fafc" stroke="#cbd5e1"/>'
            .'<line x1="210" y1="180" x2="710" y2="180" stroke="#94a3b8" stroke-width="2"/>'
            .'<line x1="460" y1="50" x2="460" y2="310" stroke="#94a3b8" stroke-width="2"/>'
            .$axisLine
            .'<circle cx="'.$cx.'" cy="'.$cy.'" r="10" fill="#2563eb"/>'
            .'<text x="'.($cx + 14).'" y="'.($cy - 12).'" font-family="Arial, sans-serif" font-size="18" font-weight="700" fill="#1e40af">'.self::escape($pointName).'('.$x.', '.$y.')</text>'
            .'<text x="460" y="344" text-anchor="middle" font-family="Arial, sans-serif" font-size="20" font-weight="700" fill="#0f172a">Reflect across: '.self::escape($axis).'</text>'
            .'</g>';

        return self::frameSvg('Mirror Image', 'Use the marked axis to find the reflected point.', $body);
    }

    private static function foldingSvg(string $text): ?string
    {
        $folds = self::foldCount($text);

        $punches = 1;
        if (preg_match('/(\d+)\s+(?:hole|punch)/i', $text, $match) === 1) {
            $punches = (int) $match[1];
        }

        $body = '<g transform="translate(190 76)">'
            .'<rect x="0" y="20" width="170" height="170" rx="10" fill="#eff6ff" stroke="#2563eb" stroke-width="3"/>'
            .'<line x1="85" y1="20" x2="85" y2="190" stroke="#60a5fa" stroke-width="3" stroke-dasharray="8 7"/>'
            .'<line x1="0" y1="105" x2="170" y2="105" stroke="#60a5fa" stroke-width="3" stroke-dasharray="8 7"/>'
            .'<text x="85" y="226" text-anchor="middle" font-family="Arial, sans-serif" font-size="18" font-weight="700" fill="#1e40af">Open sheet</text>'
            .'</g>'
            .'<path d="M410 170 L515 170" stroke="#334155" stroke-width="4" stroke-linecap="round"/>'
            .'<path d="M500 150 L525 170 L500 190" fill="none" stroke="#334155" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>'
            .'<g transform="translate(570 90)">';

        for ($i = 0; $i < min($folds, 6); $i++) {
            $offset = $i * 8;
            $body .= '<rect x="'.$offset.'" y="'.$offset.'" width="150" height="130" rx="9" fill="#ffffff" stroke="#0f172a" stroke-width="2"/>';
        }
        for ($i = 0; $i < min($punches, 4); $i++) {
            $body .= '<circle cx="'.(48 + ($i * 28)).'" cy="'.(64 + (($i % 2) * 26)).'" r="8" fill="#ef4444"/>';
        }

        $body .= '<text x="80" y="190" text-anchor="middle" font-family="Arial, sans-serif" font-size="18" font-weight="700" fill="#0f172a">Folded packet</text>'
            .'</g>'
            .'<text x="460" y="316" text-anchor="middle" font-family="Arial, sans-serif" font-size="20" font-weight="700" fill="#0f172a">Folds: '.$folds.'   Punch marks: '.$punches.'</text>';

        return self::frameSvg('Paper Folding', 'Each punch passes through every folded layer.', $body);
    }

    private static function cubeSvg(string $text): ?string
    {
        $faces = ['Top' => 'A', 'Front' => 'B', 'Right' => 'C', 'Bottom' => 'D', 'Back' => 'E', 'Left' => 'F'];
        if (preg_match('/Top=([A-Z]),\s*Front=([A-Z]),\s*Right=([A-Z]).*Bottom=([A-Z]),\s*Back=([A-Z]),\s*Left=([A-Z])/i', $text, $match) === 1) {
            $faces = [
                'Top' => strtoupper($match[1]),
                'Front' => strtoupper($match[2]),
                'Right' => strtoupper($match[3]),
                'Bottom' => strtoupper($match[4]),
                'Back' => strtoupper($match[5]),
                'Left' => strtoupper($match[6]),
            ];
        }

        $moves = self::cubeMoves($text);

        $body = '<g transform="translate(320 72)">'
            .'<polygon points="80,0 190,45 110,92 0,45" fill="#dbeafe" stroke="#2563eb" stroke-width="3"/>'
            .'<polygon points="0,45 110,92 110,220 0,168" fill="#eff6ff" stroke="#2563eb" stroke-width="3"/>'
            .'<polygon points="110,92 190,45 190,172 110,220" fill="#bfdbfe" stroke="#2563eb" stroke-width="3"/>'
            .'<text x="94" y="48" text-anchor="middle" font-family="Arial, sans-serif" font-size="30" font-weight="800" fill="#1e40af">'.$faces['Top'].'</text>'
            .'<text x="56" y="145" text-anchor="middle" font-family="Arial, sans-serif" font-size="30" font-weight="800" fill="#1e40af">'.$faces['Front'].'</text>'
            .'<text x="154" y="143" text-anchor="middle" font-family="Arial, sans-serif" font-size="30" font-weight="800" fill="#1e40af">'.$faces['Right'].'</text>'
            .'</g>'
            .'<text x="460" y="306" text-anchor="middle" font-family="Arial, sans-serif" font-size="20" font-weight="700" fill="#0f172a">Moves: '.self::escape(implode(' -> ', $moves)).'</text>';

        return self::frameSvg('Cube Logic', 'Track the visible faces as the cube rolls.', $body);
    }

    /**
     * @return array<int, string>
     */
    private static function tokensFromBracket(string $text): array
    {
        if (preg_match('/\[(.*?)\]/s', $text, $match) !== 1) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (string $token): string => self::normalizeToken($token),
            preg_split('/\s*,\s*/', (string) $match[1]) ?: []
        ), static fn (string $token): bool => $token !== ''));
    }

    /**
     * @return array<int, array<int, string>>
     */
    private static function matrixRows(string $text): array
    {
        preg_match_all('/Row\s+\d+:\s*(.*?)(?=\s+Row\s+\d+:|\s+(?:The|Both|Numbers)\b|$)/is', $text, $matches);
        $rows = [];
        foreach ($matches[1] ?? [] as $rowText) {
            $cells = preg_split('/\s+/', strtoupper(trim((string) $rowText))) ?: [];
            $cells = array_values(array_filter($cells, static fn (string $cell): bool => $cell !== ''));
            if ($cells !== []) {
                $rows[] = $cells;
            }
        }

        return $rows;
    }

    /**
     * @return array<int, string>
     */
    private static function shapeTokens(string $text): array
    {
        preg_match_all(self::tokenPattern(), self::normalizeText($text), $matches);

        return array_values(array_unique(array_map('trim', $matches[0] ?? [])));
    }

    private static function firstShapeToken(string $text): ?string
    {
        return preg_match(self::tokenPattern(), self::normalizeText($text), $match) === 1 ? trim($match[0]) : null;
    }

    private static function tokenPattern(): string
    {
        return '/\b(?:(?:[A-Z0-9]+[-_])*(?:TRI|CIR|SQR|DIA|HEX|STAR|RECT|OVAL|ARC|RING|PENT|LINE)(?:[-_][A-Z0-9]+)*|ARROW_(?:UP|RIGHT|DOWN|LEFT)|(?:UP|RIGHT|DOWN|LEFT)_ARROW)\b/';
    }

    private static function normalizeText(string $text): string
    {
        return preg_replace_callback(
            '/\b(?:'.implode('|', array_keys(self::TOKEN_ALIASES)).')\b/i',
            static fn (array $match): string => self::TOKEN_ALIASES[strtoupper($match[0])] ?? strtoupper($match[0]),
            strtoupper($text)
        ) ?? strtoupper($text);
    }

    private static function normalizeToken(string $token): string
    {
        $normalized = trim(self::normalizeText($token));

        return $normalized === 'LINE' ? 'LINE' : $normalized;
    }

    private static function looksVisualOption(string $optionText): bool
    {
        return preg_match('/^\([^)]+\)$/', $optionText) === 1
            || preg_match('/^(North|East|South|West|Up|Right|Down|Left)$/i', $optionText) === 1;
    }

    private static function foldCount(string $text): int
    {
        $normalized = strtolower($text);
        $folds = 1;

        foreach ([
            'six times' => 6,
            'five times' => 5,
            'four times' => 4,
            'three times' => 3,
            'twice' => 2,
            'once' => 1,
        ] as $word => $value) {
            if (str_contains($normalized, $word)) {
                $folds = max($folds, $value);
            }
        }

        $onceCount = preg_match_all('/\bonce\b/i', $text);
        if ($onceCount !== false && $onceCount > 1) {
            $folds = max($folds, $onceCount);
        }

        if (str_contains($normalized, 'then the folded stack is halved once more')) {
            $folds = max($folds, 4);
        }

        if (str_contains($normalized, 'twice and then') || str_contains($normalized, 'twice then')) {
            $folds = max($folds, 3);
        }

        return min($folds, 6);
    }

    /**
     * @return array<int, string>
     */
    private static function cubeMoves(string $text): array
    {
        preg_match_all('/rolls\s+(?:to\s+the\s+)?(right|left|forward|backward)/i', $text, $matches);

        return array_map(
            static fn (string $move): string => ucfirst(strtolower($move)),
            $matches[1] ?? []
        );
    }

    private static function tileGroup(string $token, int $x, int $y, int $width, int $height, bool $showLabel): string
    {
        $token = strtoupper(trim($token));
        $body = '<g transform="translate('.$x.' '.$y.')">';
        $body .= '<rect x="0" y="0" width="'.$width.'" height="'.$height.'" rx="10" fill="#ffffff" stroke="#cbd5e1" stroke-width="2"/>';
        if ($token === '?' || $token === '') {
            $body .= '<text x="'.($width / 2).'" y="'.($height / 2 + 13).'" text-anchor="middle" font-family="Arial, sans-serif" font-size="46" font-weight="800" fill="#ef4444">?</text>';
        } else {
            $body .= '<g transform="translate('.(($width - 64) / 2).' 14)">'.self::glyphMarkup($token, 64).'</g>';
            if ($showLabel) {
                $body .= '<text x="'.($width / 2).'" y="'.($height - 14).'" text-anchor="middle" font-family="Arial, sans-serif" font-size="12" font-weight="700" fill="#475569">'.self::escape($token).'</text>';
            }
        }

        return $body.'</g>';
    }

    private static function tileSvg(string $token, int $size, bool $showLabel): string
    {
        $height = $showLabel ? $size + 32 : $size;

        return '<svg xmlns="http://www.w3.org/2000/svg" width="'.$size.'" height="'.$height.'" viewBox="0 0 '.$size.' '.$height.'">'
            .'<rect width="'.$size.'" height="'.$height.'" rx="10" fill="#f8fafc"/>'
            .'<g transform="translate('.(($size - 64) / 2).' 12)">'.self::glyphMarkup($token, 64).'</g>'
            .($showLabel ? '<text x="'.($size / 2).'" y="'.($height - 12).'" text-anchor="middle" font-family="Arial, sans-serif" font-size="13" font-weight="700" fill="#475569">'.self::escape(strtoupper($token)).'</text>' : '')
            .'</svg>';
    }

    private static function glyphMarkup(string $token, int $size): string
    {
        $glyph = self::glyphParts($token);
        $shape = $glyph['shape'];
        $meta = $glyph['meta'];
        $scale = $size / 64;
        $shapeMarkup = match ($shape) {
            'TRI' => '<polygon points="32,8 56,52 8,52" fill="#2563eb"/>',
            'CIR' => '<circle cx="32" cy="32" r="22" fill="#2563eb"/>',
            'SQR' => '<rect x="12" y="12" width="40" height="40" rx="3" fill="#2563eb"/>',
            'DIA' => '<polygon points="32,6 58,32 32,58 6,32" fill="#2563eb"/>',
            'HEX' => '<polygon points="16,14 48,14 60,32 48,50 16,50 4,32" fill="#2563eb"/>',
            'STAR' => '<polygon points="32,6 39,24 58,24 42,36 48,56 32,44 16,56 22,36 6,24 25,24" fill="#2563eb"/>',
            'RECT' => '<rect x="8" y="18" width="48" height="28" rx="3" fill="#2563eb"/>',
            'OVAL' => '<ellipse cx="32" cy="32" rx="24" ry="16" fill="#2563eb"/>',
            'ARC' => '<path d="M10 42 A22 22 0 1 1 54 42" fill="none" stroke="#2563eb" stroke-width="8" stroke-linecap="round"/>',
            'RING' => '<circle cx="32" cy="32" r="20" fill="none" stroke="#2563eb" stroke-width="8"/>',
            'PENT' => '<polygon points="32,6 56,24 47,54 17,54 8,24" fill="#2563eb"/>',
            'LINE' => '<line x1="10" y1="32" x2="54" y2="32" stroke="#2563eb" stroke-width="9" stroke-linecap="round"/>',
            'ARROW_UP' => '<polygon points="32,8 56,36 42,36 42,56 22,56 22,36 8,36" fill="#2563eb"/>',
            'ARROW_RIGHT' => '<polygon points="56,32 28,8 28,22 8,22 8,42 28,42 28,56" fill="#2563eb"/>',
            'ARROW_DOWN' => '<polygon points="32,56 56,28 42,28 42,8 22,8 22,28 8,28" fill="#2563eb"/>',
            'ARROW_LEFT' => '<polygon points="8,32 36,8 36,22 56,22 56,42 36,42 36,56" fill="#2563eb"/>',
            default => '<rect x="8" y="8" width="48" height="48" rx="8" fill="#0f172a"/>',
        };

        $metaMarkup = $meta !== ''
            ? '<text x="32" y="62" text-anchor="middle" font-family="Arial, sans-serif" font-size="12" font-weight="800" fill="#0f172a">'.self::escape($meta).'</text>'
            : '';

        return '<svg viewBox="0 0 64 70" width="'.$size.'" height="'.(int) ($size * 1.09).'">'
            .'<g transform="scale('.$scale.')">'.$shapeMarkup.$metaMarkup.'</g>'
            .'</svg>';
    }

    /**
     * @return array{shape:string,meta:string}
     */
    private static function glyphParts(string $token): array
    {
        $normalized = strtoupper(trim($token));
        $normalized = str_replace(['UP_ARROW', 'RIGHT_ARROW', 'DOWN_ARROW', 'LEFT_ARROW'], ['ARROW_UP', 'ARROW_RIGHT', 'ARROW_DOWN', 'ARROW_LEFT'], $normalized);
        if (str_starts_with($normalized, 'ARROW_')) {
            return ['shape' => $normalized, 'meta' => ''];
        }

        $parts = array_values(array_filter(preg_split('/[-_]/', $normalized) ?: []));
        $knownShapes = ['TRI', 'CIR', 'SQR', 'DIA', 'HEX', 'STAR', 'RECT', 'OVAL', 'ARC', 'RING', 'PENT', 'LINE'];
        $shape = 'UNKNOWN';
        $meta = [];
        foreach ($parts as $part) {
            if ($shape === 'UNKNOWN' && in_array($part, $knownShapes, true)) {
                $shape = $part;
                continue;
            }
            $meta[] = $part;
        }

        return ['shape' => $shape, 'meta' => implode('-', $meta)];
    }

    private static function compassSvg(string $direction, int $size): string
    {
        $rotation = match (strtolower($direction)) {
            'east' => 90,
            'south' => 180,
            'west' => 270,
            default => 0,
        };

        $arrow = $direction === '?'
            ? '<text x="80" y="99" text-anchor="middle" font-family="Arial, sans-serif" font-size="72" font-weight="800" fill="#ef4444">?</text>'
            : '<g transform="rotate('.$rotation.' 80 80)"><polygon points="80,24 116,86 94,86 94,132 66,132 66,86 44,86" fill="#2563eb"/></g>';

        return '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 160 160">'
            .'<circle cx="80" cy="80" r="72" fill="#f8fafc" stroke="#cbd5e1" stroke-width="3"/>'
            .'<text x="80" y="22" text-anchor="middle" font-family="Arial, sans-serif" font-size="14" font-weight="700" fill="#64748b">N</text>'
            .'<text x="80" y="150" text-anchor="middle" font-family="Arial, sans-serif" font-size="14" font-weight="700" fill="#64748b">S</text>'
            .'<text x="145" y="85" text-anchor="middle" font-family="Arial, sans-serif" font-size="14" font-weight="700" fill="#64748b">E</text>'
            .'<text x="15" y="85" text-anchor="middle" font-family="Arial, sans-serif" font-size="14" font-weight="700" fill="#64748b">W</text>'
            .$arrow
            .'</svg>';
    }

    private static function directionOptionSvg(string $direction): string
    {
        $resolved = match (strtolower($direction)) {
            'up' => 'North',
            'right' => 'East',
            'down' => 'South',
            'left' => 'West',
            default => ucfirst(strtolower($direction)),
        };

        return '<svg xmlns="http://www.w3.org/2000/svg" width="128" height="128" viewBox="0 0 128 128"><g transform="scale(.8) translate(16 16)">'.self::compassSvg($resolved, 128).'</g></svg>';
    }

    private static function coordinateOptionSvg(string $point): string
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="128" height="80" viewBox="0 0 128 80">'
            .'<rect width="128" height="80" rx="10" fill="#f8fafc"/>'
            .'<line x1="16" y1="40" x2="112" y2="40" stroke="#94a3b8" stroke-width="2"/>'
            .'<line x1="64" y1="10" x2="64" y2="70" stroke="#94a3b8" stroke-width="2"/>'
            .'<circle cx="78" cy="26" r="7" fill="#2563eb"/>'
            .'<text x="64" y="74" text-anchor="middle" font-family="Arial, sans-serif" font-size="15" font-weight="800" fill="#0f172a">'.self::escape($point).'</text>'
            .'</svg>';
    }

    private static function frameSvg(string $title, string $subtitle, string $body): string
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="920" height="360" viewBox="0 0 920 360" role="img" aria-label="'.self::escape($title).'">'
            .'<rect width="920" height="360" rx="18" fill="#ffffff"/>'
            .'<rect x="10" y="10" width="900" height="340" rx="16" fill="#f8fafc" stroke="#cbd5e1" stroke-width="2"/>'
            .'<text x="34" y="45" font-family="Arial, sans-serif" font-size="24" font-weight="800" fill="#0f172a">'.self::escape($title).'</text>'
            .'<text x="34" y="70" font-family="Arial, sans-serif" font-size="15" font-weight="600" fill="#64748b">'.self::escape($subtitle).'</text>'
            .$body
            .'</svg>';
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
