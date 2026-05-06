<?php

namespace App\Http\Controllers\CareerCounselor\Kcat;

use App\Http\Controllers\Controller;
use App\Models\KcatAttempt;
use App\Services\Kcat\KcatAttemptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class KcatAdaptiveAttemptController extends Controller
{
    public function __construct(private readonly KcatAttemptService $attemptService) {}

    public function nextQuestion(KcatAttempt $attempt): JsonResponse
    {
        if (! $attempt->is_adaptive) {
            throw ValidationException::withMessages(['attempt' => 'This attempt is not adaptive.']);
        }
        if ($attempt->status !== 'in_progress') {
            return response()->json([
                'completed' => true,
                'message' => 'Attempt is already submitted.',
            ]);
        }

        $question = $this->attemptService->getNextAdaptiveQuestion($attempt);
        if (! $question) {
            return response()->json([
                'completed' => true,
                'message' => 'Adaptive attempt is completed.',
            ]);
        }
        $question->loadMissing(['section', 'options']);

        return response()->json([
            'completed' => false,
            'question' => [
                'id' => $question->id,
                'question_text' => $question->question_text,
                'question_image_url' => $question->question_image_url,
                'question_type' => $question->question_type,
                'difficulty' => $question->difficulty,
                'section' => $question->section?->code,
                'options' => $question->options->map(fn ($option): array => [
                    'id' => $option->id,
                    'option_text' => $option->option_text,
                    'option_image' => $option->option_image,
                    'option_image_url' => $option->option_image_url,
                ])->values(),
            ],
        ]);
    }

    public function submitAnswer(Request $request, KcatAttempt $attempt): JsonResponse
    {
        $validated = $request->validate([
            'selected_option_id' => ['nullable', 'exists:kcat_question_options,id'],
            'answer_text' => ['nullable', 'string'],
            'response_time_seconds' => ['nullable', 'integer', 'min:0', 'max:3600'],
        ]);

        $result = $this->attemptService->saveAdaptiveAnswer($attempt, $validated);

        return response()->json([
            'completed' => (bool) ($result['completed'] ?? false),
            'attempt_status' => $result['attempt']?->status,
            'next_route' => route('career-counselor.kcat.adaptive.next-question', $attempt),
            'report_route' => route('career-counselor.kcat.reports.show', $attempt),
        ]);
    }
}
