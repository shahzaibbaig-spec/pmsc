<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(): View
    {
        return view('modules.notifications.index');
    }

    public function data(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        $search = trim((string) ($validated['search'] ?? ''));
        $perPage = (int) ($validated['per_page'] ?? 10);

        $query = $request->user()
            ->notifications()
            ->when($search !== '', function ($builder) use ($search): void {
                $builder->where(function ($subQuery) use ($search): void {
                    $subQuery
                        ->where('data->title', 'like', '%'.$search.'%')
                        ->orWhere('data->message', 'like', '%'.$search.'%');
                });
            })
            ->latest();

        $paginator = $query->paginate($perPage);

        return response()->json([
            'data' => collect($paginator->items())->map(function (DatabaseNotification $notification): array {
                $title = (string) ($notification->data['title'] ?? 'Notification');
                $message = (string) ($notification->data['message'] ?? 'You have a new update.');

                return [
                    'id' => $notification->id,
                    'title' => $title,
                    'message' => $message,
                    'url' => $notification->data['url'] ?? null,
                    'is_read' => $notification->read_at !== null,
                    'created_at' => $notification->created_at?->toDateTimeString(),
                    'created_at_human' => $notification->created_at?->diffForHumans(),
                ];
            })->values()->all(),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function subscribePush(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string', 'max:500'],
            'keys' => ['required', 'array'],
            'keys.p256dh' => ['required', 'string', 'max:2048'],
            'keys.auth' => ['required', 'string', 'max:2048'],
            'contentEncoding' => ['nullable', 'string', 'max:32'],
        ]);

        $user = $request->user();

        $user->updatePushSubscription(
            (string) $validated['endpoint'],
            (string) $validated['keys']['p256dh'],
            (string) $validated['keys']['auth'],
            (string) ($validated['contentEncoding'] ?? 'aesgcm')
        );

        return response()->json(['message' => 'Push subscription saved.']);
    }

    public function unsubscribePush(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string', 'max:500'],
        ]);

        $request->user()->deletePushSubscription((string) $validated['endpoint']);

        return response()->json(['message' => 'Push subscription removed.']);
    }

    public function read(Request $request, DatabaseNotification $notification): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        abort_unless(
            $user
            && $notification->notifiable_type === $user->getMorphClass()
            && (int) $notification->notifiable_id === (int) $user->id,
            403
        );

        $notification->markAsRead();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Notification marked as read.']);
        }

        return back();
    }

    public function readAll(Request $request): RedirectResponse|JsonResponse
    {
        $request->user()?->unreadNotifications()->update([
            'read_at' => now(),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'All notifications marked as read.']);
        }

        return back();
    }
}
