<?php

namespace Database\Seeders;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;

class TeacherModuleSeeder extends Seeder
{
    public function run(): void
    {
        $teacherUsers = User::role('Teacher')->orderBy('id')->get(['id']);

        foreach ($teacherUsers as $user) {
            if (Teacher::query()->where('user_id', $user->id)->exists()) {
                continue;
            }

            $next = ((int) Teacher::query()->max('id')) + 1;
            Teacher::query()->create([
                'teacher_id' => 'T-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT),
                'user_id' => $user->id,
                'designation' => 'Teacher',
                'employee_code' => null,
            ]);
        }
    }
}

