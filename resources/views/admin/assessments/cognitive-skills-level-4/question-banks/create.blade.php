<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Create Cognitive Skills Assessment Test Level 4 Question Bank
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            @include('admin.assessments.cognitive-skills-level-4.question-banks.partials.form', [
                'bank' => $bank,
                'action' => route('admin.assessments.cognitive-skills-level-4.question-banks.store'),
                'method' => 'POST',
                'submitLabel' => 'Create Question Bank',
            ])
        </div>
    </div>
</x-app-layout>
