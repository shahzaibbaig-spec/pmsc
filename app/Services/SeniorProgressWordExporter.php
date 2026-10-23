<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;
use ZipArchive;

class SeniorProgressWordExporter
{
    private const NS = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    public function export(array $report, string $destination): void
    {
        $template = resource_path('reports/senior-progress.docx');
        if (! copy($template, $destination)) {
            throw new RuntimeException('Unable to copy the progress report template.');
        }
        $zip = new ZipArchive;
        if ($zip->open($destination) !== true) {
            throw new RuntimeException('Unable to open the progress report document.');
        }

        try {
            $xml = new DOMDocument;
            $xml->preserveWhiteSpace = true;
            if (! $xml->loadXML($zip->getFromName('word/document.xml'), LIBXML_NONET)) {
                throw new RuntimeException('Invalid Word template.');
            }
            $xpath = new DOMXPath($xml);
            $xpath->registerNamespace('w', self::NS);
            $tables = $xpath->query('//w:body/w:tbl');
            if ($tables->length !== 8) {
                throw new RuntimeException('The Word template structure has changed.');
            }

            foreach ([0, 5] as $index) {
                $table = $tables->item($index);
                $this->cell($xpath, $table, 1, 0, '☑ Mirpur');
                $this->cell($xpath, $table, 1, 1, '☐ Swabi');
                $this->cell($xpath, $table, 1, 2, $report['student_name']);
                $this->cell($xpath, $table, 1, 3, $report['student_number']);
                $this->cell($xpath, $table, 3, 0, $report['class_name']);
                $this->cell($xpath, $table, 3, 1, '');
                $this->cell($xpath, $table, 3, 2, $report['age'] === '' ? '' : $report['age'].' Years');
                $this->cell($xpath, $table, 3, 3, $report['teacher_name']);
            }

            foreach ($report['subjects'] as $index => $subject) {
                if ($index >= 10) {
                    throw new RuntimeException('The template supports ten subjects.');
                }
                $this->cell($xpath, $tables->item(1), $index + 2, 1, $subject['name']);
                $this->cell($xpath, $tables->item(1), $index + 2, 2, $subject['grade']);
                if (isset($subject['progress']) && $subject['progress'] !== '') {
                    $this->cell($xpath, $tables->item(1), $index + 2, 3 + (int) $subject['progress'], '✓');
                }
                if (isset($subject['effort']) && $subject['effort'] !== '') {
                    $this->cell($xpath, $tables->item(1), $index + 2, 6 + (int) $subject['effort'], '✓');
                }
            }
            foreach ($report['skills'] ?? [] as $index => $rating) {
                if ($rating !== '' && $rating !== null) {
                    $this->cell($xpath, $tables->item(2), $index + 1, 2 + (int) $rating, '✓');
                }
            }
            $this->remarks($xpath, $tables->item(3), $report['teacher_remarks'] ?? '');
            $this->remarks($xpath, $tables->item(4), $report['head_remarks'] ?? '');

            foreach ($xpath->query('//w:body/w:p') as $paragraph) {
                $texts = $xpath->query('.//w:t', $paragraph);
                $title = implode('', array_map(fn ($text) => $text->textContent, iterator_to_array($texts)));
                if (str_contains($title, 'Student Progress Report')) {
                    $updated = preg_replace('/Student Progress Report\s*-\s*\d{4}/u',
                        'Student Progress Report -'.$report['session'].' · '.$report['assessment'], $title);
                    foreach ($texts as $index => $text) {
                        $text->nodeValue = $index === 0 ? $updated : '';
                    }
                }
            }
            if (! $zip->addFromString('word/document.xml', $xml->saveXML())) {
                throw new RuntimeException('Unable to write the Word report.');
            }
        } finally {
            $zip->close();
        }
    }

    private function cell(DOMXPath $xpath, DOMElement $table, int $row, int $column, string $value): void
    {
        $cell = $xpath->query('./w:tr', $table)->item($row);
        $cell = $cell ? $xpath->query('./w:tc', $cell)->item($column) : null;
        if (! $cell instanceof DOMElement) {
            throw new RuntimeException('A progress report template cell is missing.');
        }
        $paragraphs = $xpath->query('./w:p', $cell);
        $paragraph = $paragraphs->item(0);
        if (! $paragraph) {
            $paragraph = $cell->appendChild($cell->ownerDocument->createElementNS(self::NS, 'w:p'));
        }
        // Retain the original paragraph and first run properties, including font and alignment.
        $runProperties = $xpath->query('.//w:rPr', $paragraph)->item(0)?->cloneNode(true);
        foreach (iterator_to_array($paragraph->childNodes) as $child) {
            if ($child->localName !== 'pPr') {
                $paragraph->removeChild($child);
            }
        }
        foreach (iterator_to_array($paragraphs) as $extra) {
            if ($extra !== $paragraph) {
                $cell->removeChild($extra);
            }
        }
        $run = $paragraph->appendChild($cell->ownerDocument->createElementNS(self::NS, 'w:r'));
        if ($runProperties) {
            $run->appendChild($runProperties);
        }
        foreach (explode("\n", str_replace("\r", '', $value)) as $index => $line) {
            if ($index > 0) {
                $run->appendChild($cell->ownerDocument->createElementNS(self::NS, 'w:br'));
            }
            $text = $run->appendChild($cell->ownerDocument->createElementNS(self::NS, 'w:t'));
            $text->setAttribute('xml:space', 'preserve');
            $text->appendChild($cell->ownerDocument->createTextNode($line));
        }
    }

    private function remarks(DOMXPath $xpath, DOMElement $table, string $value): void
    {
        // Spread short lines over the template's six existing writing rows.
        $lines = explode("\n", wordwrap(str_replace("\r", '', $value), 90, "\n", false));
        foreach (range(1, 6) as $row) {
            $this->cell($xpath, $table, $row, 0, $row === 6
                ? implode("\n", array_slice($lines, 5)) : ($lines[$row - 1] ?? ''));
        }
    }
}
