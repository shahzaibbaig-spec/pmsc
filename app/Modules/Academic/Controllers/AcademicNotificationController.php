<?php

namespace App\Modules\Academic\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AcademicNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class AcademicNotificationController extends Controller
{
    public function read(Request $request, AcademicNotification $academicNotification): RedirectResponse|JsonResponse
    {
        abort_unless(Schema::hasTable('academic_notifications'), 503, 'Academic notification table is not ready.');

        $user = $request->user();

        abort_unless($user && (int) $academicNotification->user_id === (int) $user->id, 403);

        $academicNotification->forceFill([
            'is_read' => true,
        ])->save();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Notification marked as read.']);
        }

        return back();
    }

    public function readAll(Request $request): RedirectResponse|JsonResponse
    {
        abort_unless(Schema::hasTable('academic_notifications'), 503, 'Academic notification table is not ready.');

        $request->user()?->academicNotifications()
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'updated_at' => now(),
            ]);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'All academic notifications marked as read.']);
        }

        return back();
    }
}
