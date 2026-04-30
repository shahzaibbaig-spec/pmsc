<?php

namespace App\Services\Kcat;

use App\Models\KcatQuestion;
use App\Models\KcatQuestionOption;
use App\Models\KcatSection;
use App\Models\KcatTest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class KcatTestService
{
    public const DEFAULT_SECTIONS = [
        'verbal_reasoning' => 'Verbal Reasoning',
        'quantitative_reasoning' => 'Quantitative Reasoning',
        'non_verbal_reasoning' => 'Non-Verbal Reasoning',
        'spatial_reasoning' => 'Spatial Reasoning',
    ];

    public function createTest(array $data, User $user): KcatTest
    {
        return DB::transaction(function () use ($data, $user): KcatTest {
            $test = KcatTest::query()->create([
                ...collect($data)->only(['title', 'description', 'grade_from', 'grade_to', 'duration_minutes', 'status', 'session'])->all(),
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            foreach (array_values(self::DEFAULT_SECTIONS) as $index => $name) {
                $code = array_keys(self::DEFAULT_SECTIONS)[$index];
                $this->addSection($test, ['name' => $name, 'code' => $code, 'sort_order' => $index + 1]);
            }

            return $test->fresh(['sections']);
        });
    }

    public function updateTest(KcatTest $test, array $data, User $user): KcatTest
    {
        return DB::transaction(function () use ($test, $data, $user): KcatTest {
            $test->update([
                ...collect($data)->only(['title', 'description', 'grade_from', 'grade_to', 'duration_minutes', 'status', 'session'])->all(),
                'updated_by' => $user->id,
            ]);

            return $test->fresh(['sections']);
        });
    }

    public function activateTest(KcatTest $test, User $user): KcatTest
    {
        $test->update(['status' => 'active', 'updated_by' => $user->id]);
        return $test->fresh();
    }

    public function archiveTest(KcatTest $test, User $user): KcatTest
    {
        $test->update(['status' => 'archived', 'updated_by' => $user->id]);
        return $test->fresh();
    }

    public function addSection(KcatTest $test, array $data): KcatSection
    {
        return DB::transaction(function () use ($test, $data): KcatSection {
            return $test->sections()->create([
                'name' => $data['name'],
                'code' => $data['code'],
                'description' => $data['description'] ?? null,
                'sort_order' => (int) ($data['sort_order'] ?? 0),
            ]);
        });
    }

    public function addQuestion(KcatSection $section, array $data, User $user): KcatQuestion
    {
        return DB::transaction(function () use ($section, $data, $user): KcatQuestion {
            $question = $section->questions()->create([
                'kcat_test_id' => $section->kcat_test_id,
                'question_type' => $data['question_type'],
                'difficulty' => $data['difficulty'],
                'question_text' => $data['question_text'] ?? '',
                'question_image' => $data['question_image'] ?? null,
                'explanation' => $data['explanation'] ?? null,
                'marks' => (int) $data['marks'],
                'sort_order' => (int) ($data['sort_order'] ?? 0),
                'is_active' => (bool) ($data['is_active'] ?? true),
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            $this->syncOptions($question, $data['options'] ?? []);
            $this->refreshTotals($section->test);

            return $question->fresh(['options', 'section']);
        });
    }

    public function updateQuestion(KcatQuestion $question, array $data, User $user): KcatQuestion
    {
        return DB::transaction(function () use ($question, $data, $user): KcatQuestion {
            if ($question->answers()->exists()) {
                throw ValidationException::withMessages(['question' => 'Questions with submitted answers cannot be edited. Archive and create a new question instead.']);
            }

            $question->update([
                'question_type' => $data['question_type'],
                'difficulty' => $data['difficulty'],
                'question_text' => $data['question_text'] ?? '',
                'question_image' => $data['question_image'] ?? null,
                'explanation' => $data['explanation'] ?? null,
                'marks' => (int) $data['marks'],
                'sort_order' => (int) ($data['sort_order'] ?? 0),
                'is_active' => (bool) ($data['is_active'] ?? true),
                'updated_by' => $user->id,
            ]);

            $question->options()->delete();
            $this->syncOptions($question, $data['options'] ?? []);
            $this->refreshTotals($question->test);

            return $question->fresh(['options', 'section']);
        });
    }

    public function refreshTotals(KcatTest $test): void
    {
        $test->load('sections.questions');
        foreach ($test->sections as $section) {
            $questions = $section->questions->where('is_active', true);
            $section->update([
                'total_questions' => $questions->count(),
                'total_marks' => $questions->sum('marks'),
            ]);
        }

        $test->load('sections');
        $test->update([
            'total_questions' => $test->sections->sum('total_questions'),
            'total_marks' => $test->sections->sum('total_marks'),
        ]);
    }

    private function syncOptions(KcatQuestion $question, array $options): void
    {
        foreach (array_values($options) as $index => $option) {
            KcatQuestionOption::query()->create([
                'kcat_question_id' => $question->id,
                'option_text' => $option['option_text'] ?? null,
                'option_image' => $option['option_image'] ?? null,
                'is_correct' => (bool) ($option['is_correct'] ?? false),
                'sort_order' => (int) ($option['sort_order'] ?? ($index + 1)),
            ]);
        }
    }
}
