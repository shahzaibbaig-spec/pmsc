<?php

namespace App\Services;

use App\Models\SectionHeadAssignment;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class SectionHeadAssignmentService
{
    /**
     * @param array<string, mixed> $data
     */
    public function assignSectionHead(array $data, User $principal): SectionHeadAssignment
    {
        $teacher = Teacher::query()
            ->with('user:id,name,email')
            ->findOrFail((int) $data['teacher_id']);

        if (! $teacher->user instanceof User) {
            throw ValidationException::withMessages([
                'teacher_id' => 'Selected teacher is not linked to a user account.',
            ]);
        }

        $scope = $this->resolveScope($data);
        $sectionHeadType = $this->resolveSectionHeadType($data, $scope);
        $session = trim((string) ($data['session'] ?? ''));
        if ($session === '') {
            throw ValidationException::withMessages([
                'session' => 'Session is required.',
            ]);
        }

        return DB::transaction(function () use (
            $teacher,
            $principal,
            $scope,
            $sectionHeadType,
            $session
        ): SectionHeadAssignment {
            SectionHeadAssignment::query()
                ->where('teacher_id', (int) $teacher->id)
                ->where('scope', $scope)
                ->where('session', $session)
                ->where('status', SectionHeadAssignment::STATUS_ACTIVE)
                ->update([
                    'status' => SectionHeadAssignment::STATUS_INACTIVE,
                    'updated_by' => (int) $principal->id,
                    'updated_at' => now(),
                ]);

            $assignment = SectionHeadAssignment::query()->create([
                'teacher_id' => (int) $teacher->id,
                'user_id' => (int) $teacher->user_id,
                'section_head_type' => $sectionHeadType,
                'scope' => $scope,
                'session' => $session,
                'status' => SectionHeadAssignment::STATUS_ACTIVE,
                'assigned_by' => (int) $principal->id,
                'assigned_at' => now(),
                'created_by' => (int) $principal->id,
                'updated_by' => (int) $principal->id,
            ]);

            $this->ensureRoleAssignment($teacher->user, $sectionHeadType);

            return $assignment->fresh([
                'teacher.user:id,name,email',
                'assignedBy:id,name',
                'createdBy:id,name',
                'updatedBy:id,name',
            ]);
        });
    }

    /**
     * @param SectionHeadAssignment|int $assignment
     */
    public function deactivateAssignment(SectionHeadAssignment|int $assignment, User $principal): SectionHeadAssignment
    {
        $resolved = $assignment instanceof SectionHeadAssignment
            ? $assignment
            : SectionHeadAssignment::query()->findOrFail((int) $assignment);

        if ((string) $resolved->status !== SectionHeadAssignment::STATUS_ACTIVE) {
            return $resolved->fresh([
                'teacher.user:id,name,email',
                'assignedBy:id,name',
                'createdBy:id,name',
                'updatedBy:id,name',
            ]);
        }

        $resolved->forceFill([
            'status' => SectionHeadAssignment::STATUS_INACTIVE,
            'updated_by' => (int) $principal->id,
        ])->save();

        $this->removeRoleIfNoActiveAssignments($resolved);

        return $resolved->fresh([
            'teacher.user:id,name,email',
            'assignedBy:id,name',
            'createdBy:id,name',
            'updatedBy:id,name',
        ]);
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{
     *   assignments:LengthAwarePaginator,
     *   filters:array<string, mixed>,
     *   sessions:array<int, string>,
     *   section_head_types:array<int, string>,
     *   scope_options:array<string, string>
     * }
     */
    public function getActiveSectionHeads(array $filters = []): array
    {
        $normalized = $this->normalizeFilters($filters);
        $query = SectionHeadAssignment::query()
            ->with([
                'teacher.user:id,name,email',
                'assignedBy:id,name',
                'createdBy:id,name',
                'updatedBy:id,name',
            ]);

        $status = (string) ($normalized['status'] ?? SectionHeadAssignment::STATUS_ACTIVE);
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($normalized['session'] !== null) {
            $query->where('session', (string) $normalized['session']);
        }

        if ($normalized['scope'] !== null) {
            $query->where('scope', (string) $normalized['scope']);
        }

        if ($normalized['teacher_name'] !== null) {
            $needle = '%'.(string) $normalized['teacher_name'].'%';
            $query->whereHas('teacher.user', function (Builder $builder) use ($needle): void {
                $builder->where('name', 'like', $needle)
                    ->orWhere('email', 'like', $needle);
            });
        }

        $assignments = $query
            ->orderByDesc('assigned_at')
            ->orderByDesc('id')
            ->paginate((int) $normalized['per_page'])
            ->withQueryString();

        return [
            'assignments' => $assignments,
            'filters' => $normalized,
            'sessions' => $this->sessionOptions(),
            'section_head_types' => array_keys(SectionHeadAssignment::TYPE_SCOPE_MAP),
            'scope_options' => SectionHeadAssignment::SCOPE_LABELS,
        ];
    }

    /**
     * @return array<int, string>
     */
    public function getObserverScopes(User $observer, ?string $session = null): array
    {
        $resolvedSession = trim((string) $session);

        $query = SectionHeadAssignment::query()
            ->where('status', SectionHeadAssignment::STATUS_ACTIVE)
            ->where(function (Builder $builder) use ($observer): void {
                $builder->where('user_id', (int) $observer->id)
                    ->orWhereHas('teacher', fn (Builder $teacherQuery) => $teacherQuery->where('user_id', (int) $observer->id));
            });

        if ($resolvedSession !== '') {
            $query->where('session', $resolvedSession);
        }

        return $query
            ->pluck('scope')
            ->map(static fn ($scope): string => trim((string) $scope))
            ->filter(static fn (string $scope): bool => array_key_exists($scope, SectionHeadAssignment::SCOPE_LABELS))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param array<string, mixed> $data
     */
    private function resolveScope(array $data): string
    {
        $scope = trim((string) ($data['scope'] ?? ''));
        if ($scope !== '' && array_key_exists($scope, SectionHeadAssignment::SCOPE_LABELS)) {
            return $scope;
        }

        $type = trim((string) ($data['section_head_type'] ?? ''));
        if ($type !== '' && isset(SectionHeadAssignment::TYPE_SCOPE_MAP[$type])) {
            return SectionHeadAssignment::TYPE_SCOPE_MAP[$type];
        }

        throw ValidationException::withMessages([
            'scope' => 'Please select a valid section head scope.',
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function resolveSectionHeadType(array $data, string $scope): string
    {
        $type = trim((string) ($data['section_head_type'] ?? ''));
        if ($type !== '' && isset(SectionHeadAssignment::TYPE_SCOPE_MAP[$type])) {
            if (SectionHeadAssignment::TYPE_SCOPE_MAP[$type] !== $scope) {
                throw ValidationException::withMessages([
                    'section_head_type' => 'Section head type does not match selected scope.',
                ]);
            }

            return $type;
        }

        $mappedType = array_search($scope, SectionHeadAssignment::TYPE_SCOPE_MAP, true);
        if (is_string($mappedType) && $mappedType !== '') {
            return $mappedType;
        }

        throw ValidationException::withMessages([
            'section_head_type' => 'Please select a valid section head type.',
        ]);
    }

    private function ensureRoleAssignment(User $teacherUser, string $roleName): void
    {
        Role::firstOrCreate([
            'name' => $roleName,
            'guard_name' => 'web',
        ]);

        if (! $teacherUser->hasRole($roleName)) {
            $teacherUser->assignRole($roleName);
        }
    }

    private function removeRoleIfNoActiveAssignments(SectionHeadAssignment $assignment): void
    {
        $roleName = (string) ($assignment->section_head_type ?? '');
        $userId = (int) ($assignment->user_id ?? 0);
        if ($roleName === '' || $userId <= 0) {
            return;
        }

        $activeCount = SectionHeadAssignment::query()
            ->where('user_id', $userId)
            ->where('section_head_type', $roleName)
            ->where('status', SectionHeadAssignment::STATUS_ACTIVE)
            ->count();

        if ($activeCount > 0) {
            return;
        }

        $user = User::query()->find($userId, ['id']);
        if (! $user instanceof User) {
            return;
        }

        if ($user->hasRole($roleName)) {
            $user->removeRole($roleName);
        }
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    private function normalizeFilters(array $filters): array
    {
        $perPage = isset($filters['per_page']) ? (int) $filters['per_page'] : 20;
        $perPage = max(10, min($perPage, 100));

        return [
            'session' => trim((string) ($filters['session'] ?? '')) ?: null,
            'scope' => trim((string) ($filters['scope'] ?? '')) ?: null,
            'status' => trim((string) ($filters['status'] ?? SectionHeadAssignment::STATUS_ACTIVE)) ?: SectionHeadAssignment::STATUS_ACTIVE,
            'teacher_name' => trim((string) ($filters['teacher_name'] ?? '')) ?: null,
            'per_page' => $perPage,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function sessionOptions(): array
    {
        return SectionHeadAssignment::query()
            ->orderByDesc('session')
            ->pluck('session')
            ->filter(static fn ($session): bool => is_string($session) && trim($session) !== '')
            ->unique()
            ->values()
            ->all();
    }
}
