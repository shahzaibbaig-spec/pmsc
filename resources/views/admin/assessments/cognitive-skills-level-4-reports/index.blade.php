<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Cognitive Skills Assessment Test Level 4 Reports
        </h2>
    </x-slot>

    @include('partials.assessments.cognitive-skills-level-4-report-index', [
        'panelTitle' => 'Admin',
        'indexRouteName' => 'admin.assessments.cognitive-skills-level-4-reports.index',
        'showRouteName' => 'admin.assessments.cognitive-skills-level-4.reports.show',
    ])
</x-app-layout>
