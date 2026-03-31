<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Add Cognitive Bank Question
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            @include('admin.assessments.cognitive-skills-level-4.questions.partials.form', [
                'bank' => $bank,
                'question' => $question,
                'action' => route('admin.assessments.cognitive-skills-level-4.question-banks.questions.store', $bank),
                'method' => 'POST',
                'submitLabel' => 'Create Question',
                'skillOptions' => $skillOptions,
                'questionTypes' => $questionTypes,
                'imageRecommendedTypes' => $imageRecommendedTypes,
            ])
        </div>
    </div>
</x-app-layout>
