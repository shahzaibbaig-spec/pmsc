<?php

namespace App\Notifications;

use App\Models\PrincipalTeacherMessage;
use App\Models\PrincipalTeacherThread;
use App\Models\User;
use App\Notifications\Concerns\SupportsOptionalWebPush;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class PrincipalTeacherMessageNotification extends Notification
{
    use Queueable, SupportsOptionalWebPush;

    public function __construct(
        private readonly PrincipalTeacherThread $thread,
        private readonly PrincipalTeacherMessage $message,
        private readonly User $sender
    ) {
    }

    public function via(object $notifiable): array
    {
        return $this->channelsWithOptionalWebPush($notifiable);
    }

    public function toArray(object $notifiable): array
    {
        $subject = trim((string) ($this->thread->subject ?? 'Principal-Teacher Communication'));
        $preview = Str::limit(trim((string) $this->message->message), 120);

        return [
            'type' => 'principal_teacher_message',
            'title' => 'New Principal-Teacher Message',
            'message' => sprintf(
                '%s sent a new message: %s',
                (string) ($this->sender->name ?? 'User'),
                $preview
            ),
            'thread_id' => (int) $this->thread->id,
            'message_id' => (int) $this->message->id,
            'subject' => $subject,
            'sender_name' => (string) ($this->sender->name ?? 'User'),
            'preview' => $preview,
            'url' => $this->resolveUrl($notifiable),
        ];
    }

    public function toWebPush(object $notifiable, object $notification): mixed
    {
        return $this->buildWebPushMessage($this->toArray($notifiable));
    }

    private function resolveUrl(object $notifiable): string
    {
        if (method_exists($notifiable, 'hasAnyRole') && $notifiable->hasAnyRole(['Principal', 'Admin'])) {
            return route('principal.teacher-communications.show', $this->thread);
        }

        return route('teacher.communications.show', $this->thread);
    }
}
