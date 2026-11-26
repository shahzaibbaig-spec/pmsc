<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-900">Exam Settings</h2>
    </x-slot>
    <div class="mx-auto max-w-2xl">
        <x-ui.card title="Exam Pass Percentage" subtitle="Set the minimum percentage required to pass an exam.">
            @if (session('status'))
                <p role="status" class="mb-4 text-sm text-emerald-700">{{ session('status') }}</p>
            @endif
            <form method="POST" action="{{ route('principal.exams.settings.update') }}" class="space-y-4">
                @csrf
                <x-ui.input label="Pass Percentage (%)" id="exam_pass_percentage" name="exam_pass_percentage"
                    type="number" min="0" max="100" step="0.01" required
                    :value="old('exam_pass_percentage', $passPercentage)" />
                <p class="text-sm text-slate-500">Scores equal to this percentage pass the school threshold. FBISE uses 40%. This school rule controls promotion checks and pass-rate analytics; letter grades follow the fixed table below. Previously published results are not rewritten.</p>
                <x-ui.button type="submit">Save Exam Settings</x-ui.button>
            </form>
        </x-ui.card>
        <div class="mt-6">
            <x-ui.card title="Federal Board Grading Scheme" subtitle="Applied to numeric result grades across the school.">
                <p class="mb-4 text-sm text-slate-600">FBISE rollout: SSC-I / HSSC-I from 2026; SSC-II / HSSC-II from 2027. Grade-only assessments retain their existing scale. Grade points are not assigned by this table.</p>
                <table class="w-full text-left text-sm">
                    <thead><tr class="border-b"><th class="py-2">Grade</th><th class="py-2">Percentage</th><th class="py-2">Description</th></tr></thead>
                    <tbody>
                        @foreach ($gradingBands as $band)
                            <tr class="border-b border-slate-100">
                                <td class="py-2 font-semibold">{{ $band['grade'] }}</td>
                                <td class="py-2">
                                    @if ($loop->first)
                                        96–100%
                                    @elseif ($loop->last)
                                        Below 40%
                                    @else
                                        {{ $band['minimum'] }}% to below {{ $gradingBands[$loop->index - 1]['minimum'] }}%
                                    @endif
                                </td>
                                <td class="py-2">{{ $band['description'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <p class="mt-3 text-xs text-slate-500">Fractional percentages use the lower boundaries shown without rounding up.</p>
                <a class="mt-3 inline-block text-sm text-indigo-600 underline" href="{{ \App\Services\FederalBoardGradingService::SOURCE_URL }}" target="_blank" rel="noopener noreferrer">FBISE notification — 7 October 2025</a>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
