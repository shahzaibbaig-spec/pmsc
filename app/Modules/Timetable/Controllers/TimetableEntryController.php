<?php

namespace App\Modules\Timetable\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Timetable\Requests\UpdateTimetableEntryRequest;
use App\Modules\Timetable\Services\TimetableViewerService;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class TimetableEntryController extends Controller
{
    public function update(
        UpdateTimetableEntryRequest $request,
        TimetableViewerService $viewerService
    ): JsonResponse {
        $data = $request->validated();
        $validateOnly = (bool) ($data['validate_only'] ?? false);

        try {
            $result = $viewerService->updateEntry($data, $validateOnly);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        if (! ($result['valid'] ?? false)) {
            return response()->json($result, 422);
        }

        return response()->json($result);
    }
}
