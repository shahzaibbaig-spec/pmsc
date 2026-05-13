<?php

namespace App\Notifications;

use App\Models\StudentSportsObservation;
use App\Notifications\Concerns\SupportsOptionalWebPush;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SportsObservationCreatedNotification extends Notification
{
    use Queueable, SupportsOptionalWebPush;

    public function __construct(private readonly StudentSportsObservation $observation)
    {
    }

    public function via(object $notifiable): array
    {
        return $this->channelsWithOptionalWebPush($notifiable);
    }

    public function toArray(object $notifiable): array
    {
        $studentName = (string) ($this->observation->student?->name ?? 'Student');
        $classSection = trim((string) ($this->observation->classRoom?->name ?? '').' '.(string) ($this->observation->classRoom?->section ?? ''));
        $issueLabels = $this->observation->resolvedIssueLabels();
        $issueLabel = $issueLabels !== []
            ? implode(', ', $issueLabels)
            : (string) ($this->observation->issue_label ?: StudentSportsObservation::issueLabelFor((string) $this->observation->issue_type));
        $teacherName = (string) ($this->observation->sportsTeacher?->name ?? 'Sports Teacher');

        return [
            'type' => 'sports_observation_created',
            'title' => 'Sports Observation Alert',
            'message' => sprintf(
                'Sports observation: %s of %s needs attention for %s.',
                $studentName,
                $classSection !== '' ? $classSection : 'Unknown Class',
                strtolower($issueLabel)
            ),
            'student_name' => $studentName,
            'class_section' => $classSection,
            'issue_label' => $issueLabel,
            'issue_labels' => $issueLabels,
            'auto_message' => $this->observation->resolvedCombinedMessage(),
            'observation_date' => optional($this->observation->observation_date)->toDateString(),
            'sports_teacher_name' => $teacherName,
            'observation_id' => (int) $this->observation->id,
            'url' => $this->resolveUrl($notifiable),
        ];
    }

    public function toWebPush(object $notifiable, object $notification): mixed
    {
        return $this->buildWebPushMessage($this->toArray($notifiable));
    }

    private function resolveUrl(object $notifiable): string
    {
        $date = optional($this->observation->observation_date)->toDateString() ?: now()->toDateString();

        if (method_exists($notifiable, 'hasRole') && $notifiable->hasRole('Warden')) {
            return route('warden.sports-observations.index', ['date' => $date]);
        }

        if (method_exists($notifiable, 'hasRole') && $notifiable->hasRole('Sports Teacher')) {
            return route('sports-teacher.observations.show', $this->observation);
        }

        return route('principal.sports-observations.daily', ['date' => $date]);
    }
}
