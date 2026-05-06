<?php

namespace App\Services;

use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Str;

class StudentUserResolverService
{
    public function resolveForUser(User $user): ?Student
    {
        $emailLocal = mb_strtolower(trim(Str::before((string) $user->email, '@')));
        if ($emailLocal !== '') {
            $byStudentId = Student::query()
                ->with('classRoom:id,name,section')
                ->whereRaw('LOWER(student_id) = ?', [$emailLocal])
                ->first();

            if ($byStudentId) {
                return $byStudentId;
            }

            $byCompoundLogin = $this->resolveByCompoundLoginKey($emailLocal);
            if ($byCompoundLogin) {
                return $byCompoundLogin;
            }

            $emailKey = $this->normalizeLoginKey($emailLocal);
            if ($emailKey !== '') {
                $byLoginKey = $this->resolveByNormalizedNameKey($emailKey);
                if ($byLoginKey) {
                    return $byLoginKey;
                }
            }
        }

        $normalizedName = mb_strtolower(trim((string) $user->name));
        if ($normalizedName !== '') {
            $byExactName = Student::query()
                ->with('classRoom:id,name,section')
                ->whereRaw('LOWER(name) = ?', [$normalizedName])
                ->orderByDesc('id')
                ->get();

            if ($byExactName->count() === 1) {
                return $byExactName->first();
            }

            $nameKey = $this->normalizeLoginKey($normalizedName);
            if ($nameKey !== '') {
                return $this->resolveByNormalizedNameKey($nameKey);
            }
        }

        return null;
    }

    public function preferredEmailLocalForStudent(Student $student): string
    {
        $nameKey = $this->normalizeLoginKey((string) $student->name);
        if ($nameKey !== '') {
            return $nameKey;
        }

        $studentIdKey = $this->normalizeLoginKey((string) $student->student_id);
        if ($studentIdKey !== '') {
            return 'student'.$studentIdKey;
        }

        return 'student'.(int) $student->id;
    }

    public function compoundEmailLocalForStudent(Student $student): string
    {
        $base = $this->preferredEmailLocalForStudent($student);
        $studentIdKey = $this->normalizeLoginKey((string) $student->student_id);

        if ($studentIdKey !== '') {
            return $base.$studentIdKey;
        }

        return $base.(int) $student->id;
    }

    private function resolveByCompoundLoginKey(string $emailLocal): ?Student
    {
        $matches = Student::query()
            ->with('classRoom:id,name,section')
            ->orderByDesc('id')
            ->get(['id', 'student_id', 'name', 'class_id'])
            ->filter(function (Student $student) use ($emailLocal): bool {
                return $this->compoundEmailLocalForStudent($student) === $emailLocal;
            })
            ->values();

        return $matches->count() === 1 ? $matches->first() : null;
    }

    private function resolveByNormalizedNameKey(string $key): ?Student
    {
        $matches = Student::query()
            ->with('classRoom:id,name,section')
            ->whereRaw(
                "LOWER(REPLACE(REPLACE(REPLACE(REPLACE(name, ' ', ''), '.', ''), '-', ''), '_', '')) = ?",
                [$key]
            )
            ->orderByDesc('id')
            ->get();

        return $matches->count() === 1 ? $matches->first() : null;
    }

    private function normalizeLoginKey(string $value): string
    {
        $value = Str::lower(trim(Str::ascii($value)));

        return preg_replace('/[^a-z0-9]+/', '', $value) ?? '';
    }
}
