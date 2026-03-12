<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('modules.admin.dashboard', [
            'stats' => [
                'users' => User::query()->count(),
                'students' => Student::query()->count(),
                'teachers' => Teacher::query()->count(),
                'roles' => Role::query()->count(),
            ],
        ]);
    }
}
