<?php

namespace App\Http\Controllers\Psychiatrist;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class DashboardController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        return redirect()->route('psychiatrist.discipline-reports.index');
    }
}

