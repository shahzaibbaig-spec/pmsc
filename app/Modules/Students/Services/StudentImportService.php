<?php

namespace App\Modules\Students\Services;

use App\Models\SchoolClass;
use App\Models\Student;
use App\Modules\Timetable\Services\XlsxWorkbookReader;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class StudentImportService
{
    public function __construct(
        private readonly XlsxWorkbookReader $xlsxReader
    ) {
    }

    /**
     * @return array{
     *     total_rows:int,
     *     created:int,
     *     updated:int,
     *     skipped:int,
     *     update_existing:bool,
     *     errors:array<int,array{row:int,message:string}>
     * }
     */
    public function importUploadedFile(UploadedFile $file, bool $updateExisting = true): array
    {
        return $this->importFromPath((string) $file->getRealPath(), $updateExisting, $file->getClientOriginalName());
    }

    /**
     * @return array{
     *     total_rows:int,
     *     created:int,
     *     updated:int,
     *     skipped:int,
     *     update_existing:bool,
     *     errors:array<int,array{row:int,message:string}>
     * }
     */
    public function importFromPath(string $path, bool $updateExisting = true, ?string $originalName = null): array
    {
        if (! is_file($path)) {
            throw new RuntimeException('Import file not found.');
        }

        $extension = Str::lower(pathinfo($originalName ?: $path, PATHINFO_EXTENSION));
        $rows = match ($extension) {
            'xls' => $this->rowsFromXls($path),
            'xlsx' => $this->rowsFromXlsx($path),
            'csv', 'txt' => $this->rowsFromCsv($path),
            default => throw new RuntimeException('Unsupported file format. Use xls, xlsx, or csv.'),
        };

        return $this->importRows($rows, $updateExisting);
    }

    /**
     * @return array{
     *     total_rows:int,
     *     created:int,
     *     updated:int,
     *     skipped:int,
     *     update_existing:bool,
     *     errors:array<int,array{row:int,message:string}>
     * }
     */
    public function importFromBulkText(string $rowsText, bool $updateExisting = false): array
    {
        $rows = $this->rowsFromBulkText($rowsText);

        return $this->importRows($rows, $updateExisting);
    }

    /**
     * @param array<int, array<string, string>> $rows
     * @return array{
     *     total_rows:int,
     *     created:int,
     *     updated:int,
     *     skipped:int,
     *     update_existing:bool,
     *     errors:array<int,array{row:int,message:string}>
     * }
     */
    private function importRows(array $rows, bool $updateExisting): array
    {
        $summary = [
            'total_rows' => count($rows),
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'update_existing' => $updateExisting,
            'errors' => [],
        ];

        $classCache = [];
        $usedStudentIds = Student::withTrashed()
            ->pluck('student_id')
            ->filter()
            ->map(fn ($value) => Str::lower(trim((string) $value)))
            ->flip()
            ->all();

        $generatedIdCounter = 1;

        DB::transaction(function () use (
            &$summary,
            $rows,
            $updateExisting,
            &$classCache,
            &$usedStudentIds,
            &$generatedIdCounter
        ): void {
            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;

                $name = $this->rowValue($row, ['student_name', 'name']);
                if ($name === '') {
                    $summary['skipped']++;
                    $summary['errors'][] = [
                        'row' => $rowNumber,
                        'message' => 'Student name is required.',
                    ];
                    continue;
                }

                $classLabel = $this->rowValue($row, ['class', 'class_name']);
                if ($classLabel === '') {
                    $summary['skipped']++;
                    $summary['errors'][] = [
                        'row' => $rowNumber,
                        'message' => 'Class is required.',
                    ];
                    continue;
                }

                $class = $this->resolveClass($classLabel, $classCache);

                $baseStudentId = $this->resolveStudentId($row);
                $studentId = $this->ensureUniqueStudentId(
                    $baseStudentId,
                    $usedStudentIds,
                    $generatedIdCounter
                );

                $dateOfBirth = $this->normalizeDate($this->rowValue($row, ['date_of_birth', 'dob']));
                $age = $this->normalizeAge($this->rowValue($row, ['age']), $dateOfBirth);

                $data = [
                    'student_id' => $studentId,
                    'name' => $name,
                    'father_name' => $this->truncate($this->rowValue($row, ['father_name']), 255),
                    'class_id' => (int) $class->id,
                    'date_of_birth' => $dateOfBirth,
                    'age' => $age,
                    'contact' => $this->truncate($this->compactMultiline($this->rowValue($row, ['contact_number', 'contact'])), 30),
                    'address' => $this->truncate($this->rowValue($row, ['address']), 65535),
                    'status' => $this->normalizeStatus($this->rowValue($row, ['status'])),
                ];

                $existing = Student::withTrashed()->where('student_id', $studentId)->first();

                if ($existing) {
                    if (! $updateExisting) {
                        $summary['skipped']++;
                        continue;
                    }

                    if ($existing->trashed()) {
                        $existing->restore();
                    }

                    $existing->update($data);
                    $summary['updated']++;
                } else {
                    Student::query()->create($data);
                    $summary['created']++;
                }
            }
        });

        return $summary;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function rowsFromXls(string $path): array
    {
        $script = base_path('scripts/parse_students_xls.py');
        if (! is_file($script)) {
            throw new RuntimeException('XLS parser script is missing.');
        }

        $process = new Process(['python', $script, $path]);
        $process->setTimeout(120);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException('Failed to parse XLS file: '.trim($process->getErrorOutput() ?: $process->getOutput()));
        }

        $payload = json_decode($process->getOutput(), true);
        if (! is_array($payload) || ! isset($payload['rows']) || ! is_array($payload['rows'])) {
            throw new RuntimeException('Invalid XLS parser response.');
        }

        return array_values(array_filter($payload['rows'], 'is_array'));
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function rowsFromXlsx(string $path): array
    {
        $workbook = $this->xlsxReader->read($path);
        if (empty($workbook)) {
            return [];
        }

        $firstSheetRows = reset($workbook);
        if (! is_array($firstSheetRows)) {
            return [];
        }

        return array_values(array_filter($firstSheetRows, 'is_array'));
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function rowsFromCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new RuntimeException('Unable to open CSV file.');
        }

        try {
            $header = fgetcsv($handle);
            if (! is_array($header)) {
                return [];
            }

            $headers = array_map(fn ($value) => $this->normalizeHeader((string) $value), $header);
            $rows = [];

            while (($line = fgetcsv($handle)) !== false) {
                if (! is_array($line)) {
                    continue;
                }

                $row = [];
                foreach ($headers as $i => $key) {
                    if ($key === '') {
                        continue;
                    }

                    $row[$key] = trim((string) ($line[$i] ?? ''));
                }

                if (collect($row)->filter(fn ($value) => $value !== '')->isNotEmpty()) {
                    $rows[] = $row;
                }
            }

            return $rows;
        } finally {
            fclose($handle);
        }
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function rowsFromBulkText(string $rowsText): array
    {
        $text = trim(str_replace("\r", '', $rowsText));
        if ($text === '') {
            return [];
        }

        $lines = array_values(array_filter(explode("\n", $text), fn ($line) => trim($line) !== ''));
        if (empty($lines)) {
            return [];
        }

        $header = str_getcsv(array_shift($lines));
        $headers = array_map(fn ($value) => $this->normalizeHeader((string) $value), $header ?: []);

        if (! in_array('name', $headers, true) && ! in_array('student_name', $headers, true)) {
            throw new RuntimeException('Bulk Add header must include `name` or `student_name`.');
        }

        if (! in_array('class', $headers, true) && ! in_array('class_name', $headers, true)) {
            throw new RuntimeException('Bulk Add header must include `class` or `class_name`.');
        }

        $rows = [];
        foreach ($lines as $line) {
            $values = str_getcsv($line);
            if (! is_array($values)) {
                continue;
            }

            $mapped = [];
            foreach ($headers as $index => $key) {
                if ($key === '') {
                    continue;
                }

                $mapped[$key] = trim((string) ($values[$index] ?? ''));
            }

            if (collect($mapped)->filter(fn ($value) => $value !== '')->isNotEmpty()) {
                $rows[] = $mapped;
            }
        }

        return $rows;
    }

    /**
     * @param array<string, string> $row
     * @param array<int, string> $keys
     */
    private function rowValue(array $row, array $keys): string
    {
        foreach ($keys as $key) {
            $value = trim((string) ($row[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    /**
     * @param array<string, SchoolClass> $classCache
     */
    private function resolveClass(string $rawClass, array &$classCache): SchoolClass
    {
        [$name, $section] = $this->normalizeClassNameAndSection($rawClass);
        $cacheKey = Str::lower(trim($name.'|'.($section ?? '')));

        if (isset($classCache[$cacheKey])) {
            return $classCache[$cacheKey];
        }

        $class = SchoolClass::query()->firstOrCreate(
            [
                'name' => $name,
                'section' => $section,
            ],
            ['status' => 'active']
        );

        $classCache[$cacheKey] = $class;

        return $class;
    }

    /**
     * @return array{0:string,1:?string}
     */
    private function normalizeClassNameAndSection(string $rawClass): array
    {
        $value = preg_replace('/\s+/', ' ', trim($rawClass)) ?? '';
        $value = str_replace(['–', '—'], '-', $value);

        if (preg_match('/^(\d+)\s*(st|nd|rd|th)$/i', $value, $matches)) {
            return ['Class '.$matches[1], null];
        }

        if (preg_match('/^class\s*(\d+)$/i', $value, $matches)) {
            return ['Class '.$matches[1], null];
        }

        if (preg_match('/^(\d+)$/', $value, $matches)) {
            return ['Class '.$matches[1], null];
        }

        if (preg_match('/^(class\s*\d+|nursery|kg|prep)\s+([a-z])$/i', $value, $matches)) {
            $name = Str::title(trim($matches[1]));
            if (Str::lower($name) === 'nursery') {
                $name = 'Nursery';
            }

            return [$name, Str::upper($matches[2])];
        }

        return [Str::title($value), null];
    }

    /**
     * @param array<string, string> $row
     */
    private function resolveStudentId(array $row): string
    {
        $cnic = $this->rowValue($row, ['student_cnic']);
        if ($cnic !== '') {
            return $this->cleanIdentifier($cnic);
        }

        $studentId = $this->rowValue($row, ['student_id']);
        if ($studentId !== '') {
            return $this->cleanIdentifier($studentId);
        }

        $id = $this->rowValue($row, ['id']);
        if ($id !== '') {
            $id = preg_replace('/\.0$/', '', $id) ?? $id;
            return 'KORT-'.str_pad($id, 6, '0', STR_PAD_LEFT);
        }

        return '';
    }

    /**
     * @param array<string, int> $usedStudentIds
     */
    private function ensureUniqueStudentId(string $base, array &$usedStudentIds, int &$generatedCounter): string
    {
        $candidate = $base !== '' ? $base : 'IMP-'.str_pad((string) $generatedCounter, 6, '0', STR_PAD_LEFT);
        $generatedCounter++;

        $normalized = Str::lower(trim($candidate));
        while ($normalized === '' || isset($usedStudentIds[$normalized])) {
            $candidate = 'IMP-'.str_pad((string) $generatedCounter, 6, '0', STR_PAD_LEFT);
            $generatedCounter++;
            $normalized = Str::lower(trim($candidate));
        }

        $usedStudentIds[$normalized] = 1;

        return $candidate;
    }

    private function cleanIdentifier(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/\s+/', '', $value) ?? $value;
        $value = preg_replace('/\.0$/', '', $value) ?? $value;

        return mb_substr($value, 0, 50);
    }

    private function normalizeDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (is_numeric($value)) {
            $serial = (int) round((float) $value);
            if ($serial > 1000 && $serial < 80000) {
                try {
                    return Carbon::create(1899, 12, 30)->addDays($serial)->format('Y-m-d');
                } catch (Throwable) {
                    return null;
                }
            }
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (Throwable) {
            return null;
        }
    }

    private function normalizeAge(string $value, ?string $dateOfBirth): ?int
    {
        if ($value !== '' && is_numeric($value)) {
            $age = (int) round((float) $value);
            if ($age >= 1 && $age <= 100) {
                return $age;
            }
        }

        if ($dateOfBirth) {
            try {
                $age = Carbon::parse($dateOfBirth)->age;
                if ($age >= 1 && $age <= 100) {
                    return $age;
                }
            } catch (Throwable) {
            }
        }

        return null;
    }

    private function normalizeStatus(string $value): string
    {
        $value = Str::lower(trim($value));
        if (in_array($value, ['inactive', 'left', 'dropout', 'deleted'], true)) {
            return 'inactive';
        }

        return 'active';
    }

    private function compactMultiline(string $value): string
    {
        $value = str_replace(["\r\n", "\r", "\n"], ' ', $value);
        $value = preg_replace('/\s+/', ' ', trim($value)) ?? '';

        return $value;
    }

    private function truncate(string $value, int $max): ?string
    {
        $value = trim($value);

        return $value === '' ? null : mb_substr($value, 0, $max);
    }

    private function normalizeHeader(string $header): string
    {
        $header = Str::lower(trim($header));
        $header = preg_replace('/[^a-z0-9]+/i', '_', $header) ?? '';

        return trim($header, '_');
    }
}

