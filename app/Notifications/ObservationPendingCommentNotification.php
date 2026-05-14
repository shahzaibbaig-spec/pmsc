<?php

namespace App\Notifications;

use App\Models\LessonObservation;
use App\Models\NotebookObservation;
use App\Models\User;
use App\Notifications\Concerns\SupportsOptionalWebPush;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ObservationPendingCommentNotification extends Notification
{
    use Queueable, SupportsOptionalWebPush;

    public function __construct(
        private readonly string $type,
        private readonly LessonObservation|NotebookObservation $observation,
        private readonly User $observer
    ) {
    }

    public function via(object $notifiable): array
    {
        return $this->channelsWithOptionalWebPush($notifiable);
    }

    public function toArray(object $notifiable): array
    {
        $label = $this->type === 'lesson' ? 'Lesson Observation' : 'Notebook Observation';
        $date = $this->observation->observation_date?->toDateString() ?? now()->toDateString();

        return [
            'type' => 'observation_pending_comment',
            'title' => 'Observation Comment Required',
            'message' => sprintf(
                '%s by %s on %s requires your comments.',
                $label,
                (string) ($this->observer->name ?? 'Observer'),
                $date
            ),
            'observation_type' => $this->type,
            'observation_type_label' => $label,
            'observation_id' => (int) $this->observation->id,
            'observer_name' => (string) ($this->observer->name ?? 'Observer'),
            'observation_date' => $date,
            'url' => route('teacher.observations.comment', [
                'type' => $this->type,
                'id' => (int) $this->observation->id,
            ]),
        ];
    }

    public function toWebPush(object $notifiable, object $notification): mixed
    {
        return $this->buildWebPushMessage($this->toArray($notifiable));
    }
}
