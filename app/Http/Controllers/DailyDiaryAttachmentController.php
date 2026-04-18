<?php

namespace App\Http\Controllers;

use App\Models\DailyDiary;
use App\Services\DailyDiaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DailyDiaryAttachmentController extends Controller
{
    public function __construct(
        private readonly DailyDiaryService $dailyDiaryService
    ) {
    }

    public function __invoke(Request $request, DailyDiary $dailyDiary): BinaryFileResponse
    {
        $user = $request->user();
        abort_if($user === null, 403);

        abort_unless($this->dailyDiaryService->userCanViewDiary($user, $dailyDiary), 403);

        $attachment = $this->dailyDiaryService->resolveAttachmentMeta($dailyDiary);
        abort_if($attachment === null, 404, 'Attachment not found.');

        $disk = Storage::disk('public');
        abort_unless($disk->exists($attachment['path']), 404, 'Attachment file is not available.');

        return response()->download(
            $disk->path($attachment['path']),
            $attachment['name']
        );
    }
}

