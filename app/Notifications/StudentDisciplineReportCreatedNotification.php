<?php

namespace App\Notifications;

use App\Models\StudentDisciplineReport;
use App\Notifications\Concerns\SupportsOptionalWebPush;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class StudentDisciplineReportCreatedNotification extends Notification
{
    use Queueable, SupportsOptionalWebPush;

    public function __construct(private readonly StudentDisciplineReport $report)
    {
    }

    public function via(object $notifiable): array
    {
        return $this->channelsWithOptionalWebPush($notifiable);
    }

    public function toArray(object $notifiable): array
    {
        $studentName = (string) ($this->report->student?->name ?? 'Student');
        $classSection = trim((string) ($this->report->classRoom?->name ?? '').' '.(string) ($this->report->classRoom?->section ?? ''));
        $issueLabel = (string) ($this->report->issue_label ?: StudentDisciplineReport::issueLabelFor((string) $this->report->issue_type));
        $teacherName = (string) ($this->report->teacher?->name ?? 'Teacher');

        return [
            'type' => 'student_discipline_report_created',
            'title' => 'Class Discipline Alert',
            'message' => sprintf(
                'Discipline report: %s of %s for %s (%s).',
                $studentName,
                $classSection !== '' ? $classSection : 'Unknown Class',
                strtolower($issueLabel),
                ucfirst((string) ($this->report->severity ?? 'normal'))
            ),
            'student_name' => $studentName,
            'class_section' => $classSection,
            'issue_type' => (string) $this->report->issue_type,
            'issue_label' => $issueLabel,
            'severity' => (string) ($this->report->severity ?? 'normal'),
            'teacher_name' => $teacherName,
            'auto_message' => (string) ($this->report->auto_message ?? ''),
            'report_date' => optional($this->report->report_date)->toDateString(),
            'report_id' => (int) $this->report->id,
            'url' => $this->resolveUrl($notifiable),
        ];
    }

    public function toWebPush(object $notifiable, object $notification): mixed
    {
        return $this->buildWebPushMessage($this->toArray($notifiable));
    }

    private function resolveUrl(object $notifiable): string
    {
        $date = optional($this->report->report_date)->toDateString() ?: now()->toDateString();

        if (method_exists($notifiable, 'hasRole') && $notifiable->hasRole('Warden')) {
            return route('warden.class-discipline-reports.index', [
                'date_from' => $date,
                'date_to' => $date,
            ]);
        }

        if (method_exists($notifiable, 'hasRole') && $notifiable->hasRole('Teacher')) {
            return route('teacher.discipline-reports.show', $this->report);
        }

        return route('principal.discipline-reports.daily', ['date' => $date]);
    }
}

