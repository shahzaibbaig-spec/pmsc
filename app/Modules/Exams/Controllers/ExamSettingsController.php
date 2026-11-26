<?php

namespace App\Modules\Exams\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SchoolSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ExamSettingsController extends Controller
{
    public function edit(): View
    {
        return view('modules.principal.exams.settings', [
            'passPercentage' => SchoolSetting::examPassPercentage(),
            'gradingBands' => \App\Services\FederalBoardGradingService::BANDS,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'exam_pass_percentage' => ['required', 'numeric', 'between:0,100', 'decimal:0,2'],
        ]);

        if (! Schema::hasColumn('school_settings', 'exam_pass_percentage')) {
            throw ValidationException::withMessages([
                'exam_pass_percentage' => 'Exam settings require a database update. Please ask the administrator to run the exam pass percentage migration.',
            ]);
        }

        $setting = SchoolSetting::query()->firstOrCreate(['id' => 1], [
            'school_name' => 'School Management System',
        ]);
        $setting->update($data);

        return redirect()->route('principal.exams.settings.edit')
            ->with('status', 'Exam pass percentage updated successfully.');
    }
}
