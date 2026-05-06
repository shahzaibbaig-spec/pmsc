<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Models\User;
use App\Services\StudentUserResolverService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ProvisionStudentPortals extends Command
{
    protected $signature = 'students:provision-portals
        {--domain=kort.edu.pk : Email domain for student logins}
        {--password=Student@123 : Default password for student accounts}
        {--dry-run : Preview changes without writing to the database}';

    protected $description = 'Create or update student portal users with @domain logins and Student role access.';

    public function __construct(
        private readonly StudentUserResolverService $studentUserResolver
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $domain = $this->normalizeDomain((string) $this->option('domain'));
        $password = trim((string) $this->option('password'));
        $dryRun = (bool) $this->option('dry-run');

        if ($password === '') {
            $this->error('Default password cannot be empty.');

            return self::FAILURE;
        }

        $students = Student::query()
            ->orderBy('id')
            ->get(['id', 'student_id', 'name', 'status', 'class_id']);

        if ($students->isEmpty()) {
            $this->warn('No students found.');

            return self::SUCCESS;
        }

        $studentRole = Role::query()->firstOrCreate(['name' => 'Student', 'guard_name' => 'web']);
        foreach (['view_student_daily_diary'] as $permissionName) {
            $permission = Permission::query()
                ->where('name', $permissionName)
                ->where('guard_name', 'web')
                ->first();

            if ($permission && ! $studentRole->hasPermissionTo($permission->name)) {
                $studentRole->givePermissionTo($permission);
            }
        }

        $users = User::query()->with('roles:id,name')->get(['id', 'name', 'email', 'status']);
        $usersByEmail = $users->keyBy(fn (User $user): string => mb_strtolower((string) $user->email));
        $claimedUserIds = [];
        $claimedEmails = [];
        $usedStudentIds = Student::query()
            ->whereNotNull('student_id')
            ->pluck('student_id')
            ->map(fn ($value): string => mb_strtolower(trim((string) $value)))
            ->filter(fn (string $value): bool => $value !== '')
            ->flip()
            ->all();

        $passwordHash = Hash::make($password);
        $created = 0;
        $updated = 0;
        $studentIdGenerated = 0;
        $skipped = 0;
        $previewRows = [];

        foreach ($students as $student) {
            $studentId = trim((string) $student->student_id);
            if ($studentId === '') {
                $studentId = $this->generateMissingStudentId($student, $usedStudentIds);
                $studentIdGenerated++;

                if (! $dryRun) {
                    $student->forceFill(['student_id' => $studentId])->save();
                }
            }

            $localParts = $this->candidateEmailLocals($student, $studentId);
            [$chosenEmail, $existingUser] = $this->selectEmailAndUser(
                $localParts,
                $domain,
                $usersByEmail,
                $claimedUserIds,
                $claimedEmails
            );

            if ($chosenEmail === null) {
                $skipped++;
                if (count($previewRows) < 20) {
                    $previewRows[] = [
                        (string) $student->id,
                        (string) $studentId,
                        (string) $student->name,
                        'SKIPPED',
                        '-',
                    ];
                }
                continue;
            }

            if ($existingUser) {
                if (! $dryRun) {
                    $existingUser->forceFill([
                        'name' => (string) $student->name,
                        'email' => $chosenEmail,
                        'password' => $passwordHash,
                        'status' => 'active',
                        'must_change_password' => false,
                        'password_changed_at' => now(),
                        'email_verified_at' => now(),
                    ])->save();

                    if (! $existingUser->hasRole($studentRole->name)) {
                        $existingUser->assignRole($studentRole);
                    }
                }

                $updated++;
                $claimedUserIds[$existingUser->id] = true;
                $claimedEmails[mb_strtolower($chosenEmail)] = true;
                $usersByEmail[mb_strtolower($chosenEmail)] = $existingUser;

                if (count($previewRows) < 20) {
                    $previewRows[] = [
                        (string) $student->id,
                        (string) $studentId,
                        (string) $student->name,
                        'UPDATED',
                        $chosenEmail,
                    ];
                }

                continue;
            }

            if (! $dryRun) {
                $newUser = User::query()->create([
                    'name' => (string) $student->name,
                    'email' => $chosenEmail,
                    'password' => $passwordHash,
                    'status' => 'active',
                    'must_change_password' => false,
                    'password_changed_at' => now(),
                    'email_verified_at' => now(),
                ]);
                $newUser->assignRole($studentRole);
                $usersByEmail[mb_strtolower($chosenEmail)] = $newUser;
                $claimedUserIds[$newUser->id] = true;
            } else {
                $claimedEmails[mb_strtolower($chosenEmail)] = true;
            }

            $created++;
            if (count($previewRows) < 20) {
                $previewRows[] = [
                    (string) $student->id,
                    (string) $studentId,
                    (string) $student->name,
                    'CREATED',
                    $chosenEmail,
                ];
            }
        }

        $this->table(
            ['Metric', 'Value'],
            [
                ['Mode', $dryRun ? 'DRY RUN' : 'APPLIED'],
                ['Students Processed', (string) $students->count()],
                ['Accounts Created', (string) $created],
                ['Accounts Updated', (string) $updated],
                ['Student IDs Generated', (string) $studentIdGenerated],
                ['Skipped', (string) $skipped],
                ['Email Domain', $domain],
            ]
        );

        if ($previewRows !== []) {
            $this->newLine();
            $this->info('Sample rows (first 20):');
            $this->table(['Student #', 'Student ID', 'Name', 'Action', 'Email'], $previewRows);
        }

        if ($dryRun) {
            $this->warn('Dry run completed. No data was written.');
        }

        return self::SUCCESS;
    }

    private function normalizeDomain(string $domain): string
    {
        $domain = mb_strtolower(trim($domain));
        $domain = ltrim($domain, '@');

        return $domain !== '' ? $domain : 'kort.edu.pk';
    }

    /**
     * @param array<string, int> $usedStudentIds
     */
    private function generateMissingStudentId(Student $student, array &$usedStudentIds): string
    {
        $counter = 0;

        do {
            $candidate = 'KORT-'.str_pad((string) ((int) $student->id + $counter), 6, '0', STR_PAD_LEFT);
            $normalized = mb_strtolower(trim($candidate));
            $counter++;
        } while (isset($usedStudentIds[$normalized]));

        $usedStudentIds[$normalized] = 1;

        return $candidate;
    }

    /**
     * @return array<int, string>
     */
    private function candidateEmailLocals(Student $student, string $studentId): array
    {
        $base = $this->studentUserResolver->preferredEmailLocalForStudent($student);
        $compound = $this->studentUserResolver->compoundEmailLocalForStudent($student);
        $studentIdKey = $this->normalizeKey($studentId);
        $numericId = (string) (int) $student->id;

        $candidates = array_filter([
            $base,
            $compound,
            $studentIdKey !== '' ? 'student'.$studentIdKey : null,
            'student'.$numericId,
            $base.$numericId,
        ]);

        return array_values(array_unique(array_map(
            fn (string $value): string => mb_strtolower(trim($value)),
            $candidates
        )));
    }

    /**
     * @param array<string, User> $usersByEmail
     * @param array<int, bool> $claimedUserIds
     * @param array<string, bool> $claimedEmails
     * @return array{0:?string,1:?User}
     */
    private function selectEmailAndUser(
        array $localParts,
        string $domain,
        array $usersByEmail,
        array $claimedUserIds,
        array $claimedEmails
    ): array {
        foreach ($localParts as $localPart) {
            $email = $localPart.'@'.$domain;
            $emailKey = mb_strtolower($email);

            if (isset($claimedEmails[$emailKey])) {
                continue;
            }

            $user = $usersByEmail[$emailKey] ?? null;
            if ($user instanceof User) {
                if (isset($claimedUserIds[(int) $user->id])) {
                    continue;
                }

                if (! $this->canReuseExistingUser($user)) {
                    continue;
                }
            }

            return [$email, $user];
        }

        return [null, null];
    }

    private function hasStudentRole(User $user): bool
    {
        if ($user->relationLoaded('roles')) {
            return $user->roles->contains(fn ($role): bool => mb_strtolower((string) $role->name) === 'student');
        }

        return $user->hasRole('Student');
    }

    private function canReuseExistingUser(User $user): bool
    {
        if ($this->hasStudentRole($user)) {
            return true;
        }

        if ($user->relationLoaded('roles')) {
            return $user->roles->isEmpty();
        }

        return $user->roles()->count() === 0;
    }

    private function normalizeKey(string $value): string
    {
        $value = Str::lower(trim(Str::ascii($value)));

        return preg_replace('/[^a-z0-9]+/', '', $value) ?? '';
    }
}
