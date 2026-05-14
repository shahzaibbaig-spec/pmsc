<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\SectionHeadAssignment;
use App\Services\SectionHeadAssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SectionHeadAssignmentController extends Controller
{
    public function __construct(private readonly SectionHeadAssignmentService $sectionHeadAssignmentService)
    {
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'teacher_id' => ['required', 'integer', 'exists:teachers,id'],
            'section_head_type' => ['required', Rule::in(array_keys(SectionHeadAssignment::TYPE_SCOPE_MAP))],
            'scope' => ['required', Rule::in(array_keys(SectionHeadAssignment::SCOPE_LABELS))],
            'session' => ['required', 'string', 'max:20'],
        ]);

        $this->sectionHeadAssignmentService->assignSectionHead($validated, $request->user());

        $indexUrl = route('principal.teacher-assignments.index', [
            'session' => (string) $validated['session'],
            'sh_session' => (string) $validated['session'],
        ]);

        return redirect($indexUrl.'#sectionHeadAssignmentSection')
            ->with('success', 'Section head assigned successfully.');
    }

    public function deactivate(SectionHeadAssignment $assignment, Request $request): RedirectResponse
    {
        $this->sectionHeadAssignmentService->deactivateAssignment($assignment, $request->user());

        return redirect()->to(url()->previous().'#sectionHeadAssignmentSection')
            ->with('success', 'Section head assignment deactivated successfully.');
    }
}
