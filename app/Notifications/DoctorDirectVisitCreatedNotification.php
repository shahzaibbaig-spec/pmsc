<?php

namespace App\Notifications;

use App\Models\MedicalReferral;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DoctorDirectVisitCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly MedicalReferral $referral,
        private readonly User $doctor,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $studentName = (string) ($this->referral->student?->name ?? 'student');
        $className = trim((string) ($this->referral->student?->classRoom?->name ?? '').' '.(string) ($this->referral->student?->classRoom?->section ?? ''));

        return [
            'type' => 'doctor_direct_visit_created',
            'title' => 'Direct Medical Visit Added',
            'referral_id' => (int) $this->referral->id,
            'student_name' => $studentName,
            'class_name' => $className !== '' ? $className : null,
            'doctor_name' => (string) $this->doctor->name,
            'visit_date' => optional($this->referral->visit_date)->format('Y-m-d'),
            'problem' => (string) ($this->referral->problem ?? ''),
            'source_type' => (string) ($this->referral->source_type ?? 'doctor_direct'),
            'message' => 'Dr. '.$this->doctor->name.' added a direct medical visit for '.$studentName.'.',
            'url' => route('principal.medical.referrals.index', ['highlight' => $this->referral->id]),
            'created_at' => now()->toDateTimeString(),
        ];
    }
}
