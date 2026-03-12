<?php

namespace App\Modules\Search\Services;

use App\Models\Student;

class StudentSearchService
{
    public function search(string $query, int $limit = 10): array
    {
        $query = trim($query);

        if ($query === '' || mb_strlen($query) < 2) {
            return [];
        }

        $prefix = $query.'%';

        $students = Student::query()
            ->with('classRoom:id,name,section')
            ->where('status', 'active')
            ->where(function ($q) use ($query): void {
                $prefix = $query.'%';
                $contains = '%'.$query.'%';

                $q->where('student_id', 'like', $prefix)
                    ->orWhere('name', 'like', $contains)
                    ->orWhere('father_name', 'like', $contains)
                    ->orWhereHas('classRoom', function ($classQuery) use ($query): void {
                        $contains = '%'.$query.'%';
                        $classQuery->where('name', 'like', $contains)
                            ->orWhere('section', 'like', $contains);
                    });
            })
            ->orderByRaw("CASE WHEN student_id LIKE ? THEN 0 ELSE 1 END", [$prefix])
            ->orderByRaw("CASE WHEN name LIKE ? THEN 0 ELSE 1 END", [$prefix])
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'student_id', 'name', 'father_name', 'class_id']);

        return $students->map(function (Student $student): array {
            $user = auth()->user();
            $profileRoute = $user?->hasRole('Admin')
                ? route('admin.students.show', $student)
                : route('principal.students.show', $student);

            return [
                'id' => $student->id,
                'student_id' => $student->student_id,
                'name' => $student->name,
                'father_name' => $student->father_name,
                'class_name' => trim(($student->classRoom?->name ?? '').' '.($student->classRoom?->section ?? '')),
                'profile_url' => $profileRoute,
            ];
        })->values()->all();
    }
}
