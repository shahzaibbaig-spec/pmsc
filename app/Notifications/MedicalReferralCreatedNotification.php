<?php

namespace App\Notifications;

use App\Models\MedicalReferral;
use App\Notifications\Concerns\SupportsOptionalWebPush;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MedicalReferralCreatedNotification extends Notification
{
    use Queueable, SupportsOptionalWebPush;

    public function __construct(private readonly MedicalReferral $referral)
    {
    }

    public function via(object $notifiable): array
    {
        return $this->channelsWithOptionalWebPush($notifiable);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'medical_referral_created',
            'title' => 'New Medical Referral',
            'referral_id' => $this->referral->id,
            'student_name' => $this->referral->student?->name,
            'illness_type' => $this->referral->illness_type,
            'message' => 'New medical referral submitted by Principal.',
            'url' => route('doctor.medical.referrals.index'),
            'referred_at' => optional($this->referral->referred_at)->toDateTimeString(),
        ];
    }

    public function toWebPush(object $notifiable, object $notification): mixed
    {
        return $this->buildWebPushMessage($this->toArray($notifiable));
    }
}
