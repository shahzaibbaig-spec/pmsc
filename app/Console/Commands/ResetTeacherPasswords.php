<?php

namespace App\Console\Commands;

use App\Models\Teacher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ResetTeacherPasswords extends Command
{
    protected $signature = 'teachers:reset-passwords {--password=Teacher@123 : Temporary password for all teacher accounts}';

    protected $description = 'Reset all teacher user passwords and enforce first-login password change.';

    public function handle(): int
    {
        $temporaryPassword = trim((string) $this->option('password'));
        if ($temporaryPassword === '') {
            $this->error('Temporary password cannot be empty.');

            return self::FAILURE;
        }

        $teachers = Teacher::query()
            ->with('user:id')
            ->get(['id', 'user_id']);

        $userIds = $teachers
            ->pluck('user_id')
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        if ($userIds->isEmpty()) {
            $this->warn('No teacher users found.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($userIds, $temporaryPassword): void {
            DB::table('users')
                ->whereIn('id', $userIds->all())
                ->update([
                    'password' => Hash::make($temporaryPassword),
                    'must_change_password' => true,
                    'password_changed_at' => null,
                    'updated_at' => now(),
                ]);
        });

        $this->table(
            ['Metric', 'Value'],
            [
                ['Teacher Users Updated', (string) $userIds->count()],
                ['Temporary Password', $temporaryPassword],
                ['Force Password Change', 'Enabled'],
            ]
        );

        return self::SUCCESS;
    }
}

