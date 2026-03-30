<?php

namespace App\Services;

use App\Models\InventoryAssetUnit;
use App\Models\TeacherDeviceDeclaration;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TeacherDeviceDeclarationService
{
    public function submitDeclaration(int $teacherId, array $data): TeacherDeviceDeclaration
    {
        return DB::transaction(function () use ($teacherId, $data): TeacherDeviceDeclaration {
            return TeacherDeviceDeclaration::query()->create([
                'teacher_id' => $teacherId,
                'device_type' => (string) Arr::get($data, 'device_type', 'chromebook'),
                'serial_number' => strtoupper(trim((string) Arr::get($data, 'serial_number', ''))),
                'brand' => Arr::get($data, 'brand'),
                'model' => Arr::get($data, 'model'),
                'status' => 'submitted',
                'teacher_note' => Arr::get($data, 'teacher_note'),
            ])->load(['teacher.user:id,name,email']);
        });
    }

    public function verifyDeclaration(
        TeacherDeviceDeclaration $declaration,
        int $reviewedBy,
        ?string $adminNote = null
    ): TeacherDeviceDeclaration {
        return DB::transaction(function () use ($declaration, $reviewedBy, $adminNote): TeacherDeviceDeclaration {
            $declaration = TeacherDeviceDeclaration::query()
                ->lockForUpdate()
                ->findOrFail($declaration->id);

            $linked = $this->autoLinkBySerial($declaration, $reviewedBy, null, $adminNote);
            if ($linked->status === 'linked') {
                return $linked->load(['teacher.user:id,name,email', 'assetUnit']);
            }

            $declaration->update([
                'status' => 'verified',
                'admin_note' => $adminNote,
                'reviewed_by' => $reviewedBy,
                'reviewed_at' => now(),
            ]);

            return $declaration->load(['teacher.user:id,name,email', 'assetUnit']);
        });
    }

    public function rejectDeclaration(
        TeacherDeviceDeclaration $declaration,
        int $reviewedBy,
        string $adminNote
    ): TeacherDeviceDeclaration {
        return DB::transaction(function () use ($declaration, $reviewedBy, $adminNote): TeacherDeviceDeclaration {
            $declaration = TeacherDeviceDeclaration::query()
                ->lockForUpdate()
                ->findOrFail($declaration->id);

            $declaration->update([
                'status' => 'rejected',
                'admin_note' => $adminNote,
                'reviewed_by' => $reviewedBy,
                'reviewed_at' => now(),
            ]);

            return $declaration->load(['teacher.user:id,name,email', 'assetUnit']);
        });
    }

    public function autoLinkBySerial(
        TeacherDeviceDeclaration $declaration,
        ?int $reviewedBy = null,
        ?int $assetUnitId = null,
        ?string $adminNote = null
    ): TeacherDeviceDeclaration {
        return DB::transaction(function () use ($declaration, $reviewedBy, $assetUnitId, $adminNote): TeacherDeviceDeclaration {
            $declaration = TeacherDeviceDeclaration::query()
                ->lockForUpdate()
                ->findOrFail($declaration->id);

            $assetUnit = null;
            if ($assetUnitId !== null) {
                $assetUnit = InventoryAssetUnit::query()->lockForUpdate()->find($assetUnitId);
            }

            if ($assetUnit === null) {
                $serial = strtolower(trim((string) $declaration->serial_number));
                $assetUnit = InventoryAssetUnit::query()
                    ->lockForUpdate()
                    ->whereRaw('LOWER(serial_number) = ?', [$serial])
                    ->first();
            }

            if ($assetUnit === null) {
                return $declaration;
            }

            if (
                $assetUnit->issued_to_teacher_id !== null
                && (int) $assetUnit->issued_to_teacher_id !== (int) $declaration->teacher_id
            ) {
                throw ValidationException::withMessages([
                    'asset_unit_id' => 'This asset is already issued to another teacher.',
                ]);
            }

            $assetUnit->update([
                'issued_to_teacher_id' => $declaration->teacher_id,
                'issued_at' => $assetUnit->issued_at ?: now(),
                'status' => 'issued',
            ]);

            $declaration->update([
                'asset_unit_id' => $assetUnit->id,
                'status' => 'linked',
                'admin_note' => $adminNote,
                'reviewed_by' => $reviewedBy ?: $declaration->reviewed_by,
                'reviewed_at' => $reviewedBy !== null ? now() : $declaration->reviewed_at,
            ]);

            return $declaration->load(['teacher.user:id,name,email', 'assetUnit']);
        });
    }
}
