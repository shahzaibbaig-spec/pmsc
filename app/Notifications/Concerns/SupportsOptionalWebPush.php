<?php

namespace App\Notifications\Concerns;

trait SupportsOptionalWebPush
{
    protected function channelsWithOptionalWebPush(object $notifiable): array
    {
        $channels = ['database'];

        if (! $this->canUseWebPush($notifiable)) {
            return $channels;
        }

        $channels[] = 'NotificationChannels\\WebPush\\WebPushChannel';

        return $channels;
    }

    protected function buildWebPushMessage(array $payload): mixed
    {
        $messageClass = 'NotificationChannels\\WebPush\\WebPushMessage';
        if (! class_exists($messageClass)) {
            return null;
        }

        $message = new $messageClass();

        if (method_exists($message, 'title')) {
            $message = $message->title((string) ($payload['title'] ?? 'Notification'));
        }

        if (method_exists($message, 'body')) {
            $message = $message->body((string) ($payload['message'] ?? 'You have a new update.'));
        }

        if (method_exists($message, 'icon')) {
            $message = $message->icon((string) ($payload['icon'] ?? '/favicon.ico'));
        }

        if (method_exists($message, 'badge')) {
            $message = $message->badge((string) ($payload['badge'] ?? '/favicon.ico'));
        }

        if (! empty($payload['url']) && method_exists($message, 'action')) {
            $message = $message->action('Open', (string) $payload['url']);
        }

        if (method_exists($message, 'data')) {
            $message = $message->data($payload);
        }

        return $message;
    }

    private function canUseWebPush(object $notifiable): bool
    {
        if (! class_exists('NotificationChannels\\WebPush\\WebPushChannel')) {
            return false;
        }

        if (! method_exists($notifiable, 'pushSubscriptions')) {
            return false;
        }

        try {
            return $notifiable->pushSubscriptions()->exists();
        } catch (\Throwable) {
            return false;
        }
    }
}
