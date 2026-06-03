<?php

namespace Tests\Unit;

use App\Support\KcatVisualRenderer;
use Tests\TestCase;

class KcatVisualRendererTest extends TestCase
{
    public function test_it_renders_rotation_steps_with_degree_symbols(): void
    {
        $degree = html_entity_decode('&#176;', ENT_QUOTES, 'UTF-8');
        $svg = $this->decodeDataUri(KcatVisualRenderer::questionDataUri(
            'rotation',
            'An arrow starts facing North. It rotates 90'.$degree.' clockwise, 180'.$degree.' counterclockwise. Which direction does it face now?'
        ));

        $this->assertStringContainsString('90 CW', $svg);
        $this->assertStringContainsString('180 CCW', $svg);
    }

    public function test_it_renders_multi_once_folding_as_multiple_folds(): void
    {
        $svg = $this->decodeDataUri(KcatVisualRenderer::questionDataUri(
            'folding',
            'A paper is folded once vertical, once horizontal, once diagonal. 3 punch mark(s) are made on the folded packet.'
        ));

        $this->assertStringContainsString('Folds: 3', $svg);
        $this->assertStringContainsString('Punch marks: 3', $svg);
    }

    public function test_it_preserves_cube_move_order_in_diagram(): void
    {
        $svg = $this->decodeDataUri(KcatVisualRenderer::questionDataUri(
            'cube_logic',
            'A cube has Top=A, Front=B, Right=C (so opposite faces are Bottom=D, Back=E, Left=F). Then the cube rolls to the right, rolls forward, rolls to the left, rolls backward.'
        ));

        $this->assertStringContainsString('Right -&gt; Forward -&gt; Left -&gt; Backward', $svg);
    }

    public function test_it_renders_numeric_prefix_shape_options(): void
    {
        $svg = $this->decodeDataUri(KcatVisualRenderer::optionDataUri('matrix', '4-SQR'));

        $this->assertStringContainsString('4-SQR', $svg);
    }

    public function test_it_renders_existing_demo_shape_questions_with_plain_words(): void
    {
        $questionSvg = $this->decodeDataUri(KcatVisualRenderer::questionDataUri(
            'analogy',
            'Demo pattern: Circle, Square, Circle, Square, ___.'
        ));
        $optionSvg = $this->decodeDataUri(KcatVisualRenderer::optionDataUri('analogy', 'Circle'));
        $lineOptionSvg = $this->decodeDataUri(KcatVisualRenderer::optionDataUri('analogy', 'Line'));

        $this->assertStringContainsString('Visual Pattern', $questionSvg);
        $this->assertStringContainsString('CIR', $questionSvg);
        $this->assertStringContainsString('SQR', $questionSvg);
        $this->assertStringContainsString('CIR', $optionSvg);
        $this->assertStringContainsString('LINE', $lineOptionSvg);
    }

    private function decodeDataUri(?string $dataUri): string
    {
        $this->assertNotNull($dataUri);
        $this->assertStringStartsWith('data:image/svg+xml;base64,', $dataUri);

        $decoded = base64_decode(substr($dataUri, strlen('data:image/svg+xml;base64,')), true);
        $this->assertIsString($decoded);

        return $decoded;
    }
}
