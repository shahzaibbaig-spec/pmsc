<?php

namespace App\Modules\Teachers\Controllers;

use App\Http\Controllers\Controller;
use App\Models\LessonObservation;
use App\Models\NotebookObservation;
use App\Models\User;
use App\Services\TeacherObservationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeacherObservationCommentController extends Controller
{
    public function __construct(private readonly TeacherObservationService $teacherObservationService)
    {
    }

    public function __invoke(Request $request, string $type, int $id): View|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->hasRole('Teacher'), 403);

        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'teacher_comments' => ['required', 'string', 'max:4000'],
            ]);

            $observation = $this->teacherObservationService->submitTeacherComment(
                $type,
                $id,
                (string) $validated['teacher_comments'],
                $user
            );

            return redirect()
                ->route('teacher.observations.comment', [
                    'type' => $type,
                    'id' => $observation->id,
                ])
                ->with('success', 'Your comment was submitted successfully.');
        }

        $observation = $this->resolveObservationForTeacher($type, $id, $user);

        return view('modules.teacher.observations.comment', [
            'type' => $type,
            'typeLabel' => $this->typeLabel($type),
            'observation' => $observation,
            'pendingComments' => $this->teacherObservationService->getPendingObservationCommentsForTeacher($user),
        ]);
    }

    private function resolveObservationForTeacher(string $type, int $id, User $teacher): LessonObservation|NotebookObservation
    {
        return match ($type) {
            'lesson' => LessonObservation::query()
                ->with([
                    'observedTeacher:id,name',
                    'observer:id,name',
                    'classRoom:id,name,section',
                    'items' => fn ($query) => $query->orderBy('sort_order'),
                ])
                ->where('observed_teacher_id', (int) $teacher->id)
                ->findOrFail($id),
            'notebook' => NotebookObservation::query()
                ->with([
                    'observedTeacher:id,name',
                    'observer:id,name',
                    'classRoom:id,name,section',
                    'subject:id,name',
                    'items' => fn ($query) => $query->orderBy('sort_order'),
                ])
                ->where('observed_teacher_id', (int) $teacher->id)
                ->findOrFail($id),
            default => abort(404),
        };
    }

    private function typeLabel(string $type): string
    {
        return $type === 'lesson' ? 'Lesson Observation' : 'Notebook Observation';
    }
}
