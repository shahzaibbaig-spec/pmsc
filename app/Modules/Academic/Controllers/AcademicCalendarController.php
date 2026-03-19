<?php

namespace App\Modules\Academic\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AcademicEvent;
use App\Modules\Academic\Services\AcademicEventNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AcademicCalendarController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'type' => ['nullable', 'string', 'max:40'],
            'edit' => ['nullable', 'integer'],
        ]);

        $selectedType = trim((string) ($validated['type'] ?? ''));
        $canManage = $this->canManage($request);

        $events = AcademicEvent::query()
            ->with('creator:id,name')
            ->when($selectedType !== '', function ($query) use ($selectedType): void {
                $query->where('type', $selectedType);
            })
            ->orderBy('start_date')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        $storedTypes = AcademicEvent::query()
            ->select('type')
            ->distinct()
            ->orderBy('type')
            ->pluck('type')
            ->map(fn (string $type): string => strtolower(trim($type)))
            ->filter()
            ->values()
            ->all();

        $typeOptions = collect(['exam', 'holiday', 'meeting', 'activity', 'announcement'])
            ->merge($storedTypes)
            ->unique()
            ->values()
            ->all();

        $editId = (int) ($validated['edit'] ?? 0);
        $editingEvent = $canManage && $editId > 0
            ? AcademicEvent::query()->find($editId)
            : null;

        return view('modules.academic.calendar.index', [
            'events' => $events,
            'selectedType' => $selectedType,
            'canManage' => $canManage,
            'editingEvent' => $editingEvent,
            'typeOptions' => $typeOptions,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->canManage($request), 403);

        $payload = $this->validatedPayload($request);
        $payload['created_by'] = (int) ($request->user()?->id ?? 0) ?: null;

        AcademicEvent::query()->create($payload);

        return redirect()
            ->route('academic-calendar.index')
            ->with('status', 'Academic event created successfully.');
    }

    public function update(Request $request, AcademicEvent $academicEvent): RedirectResponse
    {
        abort_unless($this->canManage($request), 403);

        $payload = $this->validatedPayload($request);
        $academicEvent->forceFill($payload)->save();

        return redirect()
            ->route('academic-calendar.index')
            ->with('status', 'Academic event updated successfully.');
    }

    public function destroy(Request $request, AcademicEvent $academicEvent): RedirectResponse
    {
        abort_unless($this->canManage($request), 403);

        $academicEvent->delete();

        return redirect()
            ->route('academic-calendar.index')
            ->with('status', 'Academic event deleted successfully.');
    }

    public function sendReminder(
        Request $request,
        AcademicEvent $academicEvent,
        AcademicEventNotificationService $service
    ): RedirectResponse {
        abort_unless($this->canManage($request), 403);

        $result = $service->sendReminderForEvent($academicEvent, true);

        return redirect()
            ->route('academic-calendar.index')
            ->with(
                'status',
                sprintf(
                    'Reminder sent for "%s": %d delivered, %d skipped.',
                    $academicEvent->title,
                    (int) ($result['sent'] ?? 0),
                    (int) ($result['skipped'] ?? 0)
                )
            );
    }

    private function validatedPayload(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'type' => ['required', 'string', 'max:40'],
            'notify_before' => ['nullable', 'boolean'],
            'notify_days_before' => ['nullable', 'integer', 'min:0', 'max:365', 'required_if:notify_before,1'],
        ]);

        $notifyBefore = $request->boolean('notify_before');

        return [
            'title' => trim((string) $validated['title']),
            'description' => trim((string) ($validated['description'] ?? '')) ?: null,
            'start_date' => (string) $validated['start_date'],
            'end_date' => isset($validated['end_date']) ? (string) $validated['end_date'] : null,
            'type' => strtolower(trim((string) $validated['type'])),
            'notify_before' => $notifyBefore,
            'notify_days_before' => $notifyBefore ? (int) ($validated['notify_days_before'] ?? 0) : 0,
        ];
    }

    private function canManage(Request $request): bool
    {
        return $request->user()?->hasAnyRole(['Admin', 'Principal']) ?? false;
    }
}

