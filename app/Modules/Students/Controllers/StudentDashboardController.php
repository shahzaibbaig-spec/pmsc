<?php

namespace App\Modules\Students\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class StudentDashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('modules.student.dashboard');
    }
}

