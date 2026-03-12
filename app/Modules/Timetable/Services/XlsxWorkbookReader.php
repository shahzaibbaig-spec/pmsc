<?php

namespace App\Modules\Timetable\Services;

use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

class XlsxWorkbookReader
{
    /**
     * @return array<string, array<int, array<string, string>>>
     */
    public function read(string $path): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('ZipArchive extension is required for XLSX import.');
        }

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('Unable to open Excel file.');
        }

        try {
            $sharedStrings = $this->readSharedStrings($zip);
            $sheetPaths = $this->readSheetPaths($zip);

            $rowsBySheet = [];
            foreach ($sheetPaths as $sheetName => $sheetPath) {
                $sheetXml = $zip->getFromName($sheetPath);
                if ($sheetXml === false) {
                    continue;
                }

                $rowsBySheet[$this->normalizeSheetName($sheetName)] = $this->toAssociativeRows(
                    $this->parseRawRows($sheetXml, $sharedStrings)
                );
            }

            return $rowsBySheet;
        } finally {
            $zip->close();
        }
    }

    /**
     * @return array<string, string>
     */
    private function readSheetPaths(ZipArchive $zip): array
    {
        $workbookXml = $zip->getFromName('xl/workbook.xml');
        if ($workbookXml === false) {
            throw new RuntimeException('Invalid XLSX: workbook.xml is missing.');
        }

        $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($relsXml === false) {
            throw new RuntimeException('Invalid XLSX: workbook relationships are missing.');
        }

        $workbook = $this->loadXml($workbookXml);
        $workbook->registerXPathNamespace('main', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $workbook->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');

        $relationshipsDoc = $this->loadXml($relsXml);
        $relationshipsDoc->registerXPathNamespace('rel', 'http://schemas.openxmlformats.org/package/2006/relationships');

        $relationships = [];
        foreach ($relationshipsDoc->xpath('//rel:Relationship') ?: [] as $relationship) {
            $id = (string) ($relationship['Id'] ?? '');
            $target = (string) ($relationship['Target'] ?? '');
            if ($id === '' || $target === '') {
                continue;
            }

            $relationships[$id] = $this->normalizeSheetPath($target);
        }

        $sheetPaths = [];
        foreach ($workbook->xpath('//main:sheets/main:sheet') ?: [] as $sheet) {
            $name = trim((string) ($sheet['name'] ?? ''));
            $relationshipId = trim((string) ($sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'] ?? ''));

            if ($name === '' || $relationshipId === '' || ! isset($relationships[$relationshipId])) {
                continue;
            }

            $sheetPaths[$name] = $relationships[$relationshipId];
        }

        return $sheetPaths;
    }

    /**
     * @return array<int, string>
     */
    private function readSharedStrings(ZipArchive $zip): array
    {
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXml === false) {
            return [];
        }

        $document = $this->loadXml($sharedXml);
        $document->registerXPathNamespace('main', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        $strings = [];
        foreach ($document->xpath('//main:si') ?: [] as $sharedItem) {
            $sharedItem->registerXPathNamespace('main', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $parts = [];
            foreach ($sharedItem->xpath('.//main:t') ?: [] as $textNode) {
                $parts[] = (string) $textNode;
            }

            $strings[] = trim(implode('', $parts));
        }

        return $strings;
    }

    /**
     * @param array<int, string> $sharedStrings
     * @return array<int, array<int, string>>
     */
    private function parseRawRows(string $sheetXml, array $sharedStrings): array
    {
        $sheet = $this->loadXml($sheetXml);
        $sheetNamespace = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
        $sheet->registerXPathNamespace('main', $sheetNamespace);

        $rows = [];
        foreach ($sheet->xpath('//main:sheetData/main:row') ?: [] as $rowNode) {
            $row = [];
            $currentColumn = 0;

            $rowNode->registerXPathNamespace('main', $sheetNamespace);
            $cells = $rowNode->xpath('./main:c') ?: [];

            foreach ($cells as $cellNode) {
                $ref = trim((string) ($cellNode['r'] ?? ''));
                $columnIndex = $this->columnIndexFromCellReference($ref);

                // Some exporters omit cell refs; fall back to a sequential index.
                if ($columnIndex === null) {
                    $columnIndex = $currentColumn + 1;
                }

                $currentColumn = max($currentColumn, $columnIndex);

                $type = trim((string) ($cellNode['t'] ?? ''));
                $value = '';
                $cellNode->registerXPathNamespace('main', $sheetNamespace);

                if ($type === 'inlineStr') {
                    $textParts = [];
                    foreach ($cellNode->xpath('.//main:t') ?: [] as $textNode) {
                        $textParts[] = (string) $textNode;
                    }

                    $value = trim(implode('', $textParts));
                } else {
                    $valueNodes = $cellNode->xpath('./main:v') ?: [];
                    $rawValue = trim((string) ($valueNodes[0] ?? ''));

                    if ($type === 's') {
                        $sharedIndex = (int) $rawValue;
                        $value = $sharedStrings[$sharedIndex] ?? '';
                    } elseif ($type === 'b') {
                        $value = $rawValue === '1' ? '1' : '0';
                    } else {
                        $value = $rawValue;
                    }
                }

                $row[$columnIndex] = trim($value);
            }

            if (! empty($row)) {
                ksort($row);
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * @param array<int, array<int, string>> $rawRows
     * @return array<int, array<string, string>>
     */
    private function toAssociativeRows(array $rawRows): array
    {
        if (empty($rawRows)) {
            return [];
        }

        $headerRow = array_shift($rawRows) ?? [];
        if (empty($headerRow)) {
            return [];
        }

        $headers = [];
        foreach ($headerRow as $index => $headerValue) {
            $key = $this->normalizeHeader($headerValue);
            if ($key === '') {
                $key = 'column_'.$index;
            }

            $headers[$index] = $key;
        }

        $rows = [];
        foreach ($rawRows as $row) {
            $mapped = [];
            $hasValue = false;

            foreach ($headers as $index => $key) {
                $value = trim((string) ($row[$index] ?? ''));
                $mapped[$key] = $value;
                $hasValue = $hasValue || $value !== '';
            }

            if ($hasValue) {
                $rows[] = $mapped;
            }
        }

        return $rows;
    }

    private function normalizeSheetPath(string $target): string
    {
        $target = ltrim(str_replace('\\', '/', $target), '/');
        if (str_starts_with($target, 'xl/')) {
            return $target;
        }

        return 'xl/'.$target;
    }

    private function normalizeSheetName(string $name): string
    {
        return $this->normalizeHeader($name);
    }

    private function normalizeHeader(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/i', '_', $value) ?? '';

        return trim($value, '_');
    }

    private function columnIndexFromCellReference(string $reference): ?int
    {
        if ($reference === '' || ! preg_match('/^([A-Z]+)\d+$/i', $reference, $matches)) {
            return null;
        }

        $letters = strtoupper($matches[1]);
        $index = 0;
        $length = strlen($letters);
        for ($i = 0; $i < $length; $i++) {
            $index = ($index * 26) + (ord($letters[$i]) - ord('A') + 1);
        }

        return $index;
    }

    private function loadXml(string $xml): SimpleXMLElement
    {
        $document = simplexml_load_string($xml);
        if ($document === false) {
            throw new RuntimeException('Failed to parse XLSX XML content.');
        }

        return $document;
    }
}
