<?php

namespace App\Notifications;

use App\Models\CareerCounselingSession;
use App\Models\Student;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CareerCounselorPrincipalNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Student $student,
        private readonly User $counselor,
        private readonly string $reason,
        private readonly ?CareerCounselingSession $session = null
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $classLabel = trim((string) ($this->student->classRoom?->name ?? '').' '.(string) ($this->student->classRoom?->section ?? ''));

        return [
            'title' => 'Career Counselor Alert',
            'message' => $this->student->name.' ('.$classLabel.') needs attention: '.$this->reason,
            'student_name' => $this->student->name,
            'class_section' => $classLabel,
            'counselor_name' => $this->counselor->name,
            'reason' => $this->reason,
            'url' => $this->session
                ? route('principal.counseling-sessions.show', $this->session)
                : route('principal.career-counseling.index'),
        ];
    }
}
