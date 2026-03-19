<?php

namespace App\Modules\Academic\Services;

use App\Models\AcademicEvent;
use App\Models\AcademicNotification;
use App\Models\User;
use Illuminate\Support\Carbon;

class AcademicEventNotificationService
{
    public function sendScheduledReminders(?Carbon $targetDate = null): array
    {
        $date = ($targetDate?->copy() ?? now())->startOfDay();

        $events = AcademicEvent::query()
            ->where('notify_before', true)
            ->whereDate('start_date', '>=', $date->toDateString())
            ->orderBy('start_date')
            ->get();

        $eventsDue = $events->filter(
            fn (AcademicEvent $event): bool => $this->isReminderDue($event, $date)
        );

        $eventsNotified = 0;
        $sent = 0;
        $skipped = 0;

        foreach ($eventsDue as $event) {
            $result = $this->sendReminderForEvent($event, false);
            if ((int) $result['sent'] > 0) {
                $eventsNotified++;
            }

            $sent += (int) $result['sent'];
            $skipped += (int) $result['skipped'];
        }

        return [
            'date' => $date->toDateString(),
            'events_checked' => $events->count(),
            'events_due' => $eventsDue->count(),
            'events_notified' => $eventsNotified,
            'sent' => $sent,
            'skipped' => $skipped,
        ];
    }

    public function sendReminderForEvent(AcademicEvent $event, bool $force = true): array
    {
        $teachers = $this->activeTeachers();
        $sent = 0;
        $skipped = 0;
        $now = now();

        $title = 'Academic Event Reminder: '.$event->title;
        $message = $this->buildMessage($event);

        foreach ($teachers as $teacher) {
            if (! $force && $this->alreadySentForEvent((int) $teacher->id, (int) $event->id)) {
                $skipped++;
                continue;
            }

            AcademicNotification::query()->create([
                'user_id' => (int) $teacher->id,
                'event_id' => (int) $event->id,
                'title' => $title,
                'message' => $message,
                'is_read' => false,
                'sent_at' => $now,
            ]);

            $sent++;
        }

        return [
            'event_id' => (int) $event->id,
            'teachers' => $teachers->count(),
            'sent' => $sent,
            'skipped' => $skipped,
        ];
    }

    private function activeTeachers()
    {
        return User::query()
            ->role('Teacher')
            ->where(function ($query): void {
                $query->whereNull('status')
                    ->orWhere('status', 'active');
            })
            ->orderBy('name')
            ->get(['id', 'name', 'status']);
    }

    private function alreadySentForEvent(int $userId, int $eventId): bool
    {
        return AcademicNotification::query()
            ->where('user_id', $userId)
            ->where('event_id', $eventId)
            ->exists();
    }

    private function isReminderDue(AcademicEvent $event, Carbon $targetDate): bool
    {
        if (! $event->notify_before || ! $event->start_date) {
            return false;
        }

        $daysBefore = max((int) $event->notify_days_before, 0);
        $reminderDate = $event->start_date->copy()->subDays($daysBefore);

        return $reminderDate->isSameDay($targetDate);
    }

    private function buildMessage(AcademicEvent $event): string
    {
        $startLabel = $event->start_date?->format('d M Y') ?? '-';
        $endLabel = $event->end_date?->format('d M Y');
        $dateLabel = $startLabel;
        if ($endLabel !== null && $endLabel !== $startLabel) {
            $dateLabel = $startLabel.' to '.$endLabel;
        }

        $description = trim((string) $event->description);
        $suffix = $description !== '' ? ' '.$description : '';

        return sprintf(
            '%s (%s) is scheduled for %s.%s',
            $event->title,
            strtoupper($event->type),
            $dateLabel,
            $suffix
        );
    }
}

