<?php

namespace App\Modules\Medical\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DoctorMedicalRequestListController extends Controller
{
    public function index(): View
    {
        return view('modules.doctor.medical.requests-list');
    }
}
