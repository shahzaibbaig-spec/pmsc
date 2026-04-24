<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $title ?? 'CBC Report Detail' }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">CBC Report #{{ $report->id }}</h3>
                            <p class="text-sm text-gray-600 mt-1">
                                Student: {{ $report->student?->name ?? '-' }} ({{ $report->student?->student_id ?? '-' }})
                            </p>
                        </div>
                        <a href="{{ $printUrl }}" target="_blank" class="inline-flex items-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                            Print CBC Report
                        </a>
                    </div>

                    <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                        <div><span class="font-semibold">Date:</span> {{ optional($report->report_date)->format('Y-m-d') }}</div>
                        <div><span class="font-semibold">Session:</span> {{ $report->session }}</div>
                        <div><span class="font-semibold">Machine No:</span> {{ $report->machine_report_no ?: '-' }}</div>
                        <div><span class="font-semibold">Doctor:</span> {{ $report->doctor?->name ?? '-' }}</div>
                        <div><span class="font-semibold">Class:</span> {{ trim(($report->student?->classRoom?->name ?? '').' '.($report->student?->classRoom?->section ?? '')) ?: '-' }}</div>
                        <div><span class="font-semibold">Visit Link:</span> {{ $report->student_medical_record_id ? '#'.$report->student_medical_record_id : 'Standalone' }}</div>
                    </div>

                    <div class="mt-6 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Parameter</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-600">Value</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white text-sm">
                                @foreach([
                                    'Hemoglobin' => $report->hemoglobin,
                                    'RBC Count' => $report->rbc_count,
                                    'WBC Count' => $report->wbc_count,
                                    'Platelet Count' => $report->platelet_count,
                                    'Hematocrit (PCV)' => $report->hematocrit_pcv,
                                    'MCV' => $report->mcv,
                                    'MCH' => $report->mch,
                                    'MCHC' => $report->mchc,
                                    'Neutrophils' => $report->neutrophils,
                                    'Lymphocytes' => $report->lymphocytes,
                                    'Monocytes' => $report->monocytes,
                                    'Eosinophils' => $report->eosinophils,
                                    'Basophils' => $report->basophils,
                                    'ESR' => $report->esr,
                                ] as $label => $value)
                                    <tr>
                                        <td class="px-4 py-2 text-gray-800">{{ $label }}</td>
                                        <td class="px-4 py-2 text-gray-800">{{ $value !== null ? $value : '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        <h4 class="text-sm font-semibold text-gray-800">Remarks</h4>
                        <p class="mt-1 text-sm text-gray-700 whitespace-pre-line">{{ $report->remarks ?: '-' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
