<?php

namespace App\Services;

use App\Models\Hostel;
use App\Models\HostelResident;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class HostelResidentService
{
    public function readCsv(string $path): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Unable to read the resident list.');
        }
        try {
            $header = fgetcsv($handle);
            if (! is_array($header)) {
                throw new RuntimeException('The resident list is empty.');
            }
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
            $columns = array_flip(array_map('trim', $header));
            foreach (['Room group', 'Spreadsheet name', 'DOB'] as $required) {
                if (! array_key_exists($required, $columns)) {
                    throw new RuntimeException('The CSV must contain Room group, Spreadsheet name, and DOB columns.');
                }
            }
            $rows = [];
            while (($cells = fgetcsv($handle)) !== false) {
                if (count($cells) === 1 && trim((string) $cells[0]) === '') {
                    continue;
                }
                $row = [
                    'name' => trim((string) ($cells[$columns['Spreadsheet name']] ?? '')),
                    'date_of_birth' => trim((string) ($cells[$columns['DOB']] ?? '')) ?: null,
                    'room_group' => trim((string) ($cells[$columns['Room group']] ?? '')) ?: null,
                ];
                Validator::make($row, [
                    'name' => ['required', 'string', 'max:255'],
                    'date_of_birth' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
                    'room_group' => ['nullable', 'string', 'max:100'],
                ])->validate();
                $rows[] = $row;
                if (count($rows) > 1000) {
                    throw new RuntimeException('Import up to 1,000 residents at a time.');
                }
            }
            if ($rows === []) {
                throw new RuntimeException('The CSV contains no resident records.');
            }

            return $rows;
        } finally {
            fclose($handle);
        }
    }

    public function import(array $rows, Hostel $hostel, ?User $user = null): array
    {
        if ($user?->isWarden() && (int) $user->hostel_id !== (int) $hostel->id) {
            throw new RuntimeException('You can only add residents to your assigned hostel.');
        }

        return DB::transaction(function () use ($rows, $hostel, $user): array {
            $created = 0;
            foreach ($rows as $row) {
                $key = hash('sha256', $hostel->id.'|'.mb_strtolower(preg_replace('/\s+/u', ' ', trim($row['name']))).'|'.($row['date_of_birth'] ?? ''));
                $resident = HostelResident::query()->firstOrCreate(['import_key' => $key], [
                    ...$row,
                    'hostel_id' => $hostel->id,
                    'gender' => 'male',
                    'is_active' => true,
                    'created_by' => $user?->id,
                    'notes' => 'Hostel resident only; imported from the boys room-wise list.',
                ]);
                $created += $resident->wasRecentlyCreated ? 1 : 0;
            }

            return ['created' => $created, 'unchanged' => count($rows) - $created];
        });
    }
}
