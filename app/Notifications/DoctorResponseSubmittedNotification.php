<?php

namespace App\Notifications;

use App\Models\MedicalReferral;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DoctorResponseSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly MedicalReferral $referral)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $studentName = $this->referral->student?->name ?? 'student';

        return [
            'type' => 'doctor_response_submitted',
            'title' => 'Doctor Response Submitted',
            'referral_id' => $this->referral->id,
            'student_name' => $studentName,
            'status' => $this->referral->status,
            'message' => 'Doctor updated medical referral for '.$studentName.'.',
            'url' => route('principal.medical.referrals.index'),
            'responded_at' => now()->toDateTimeString(),
        ];
    }
}
