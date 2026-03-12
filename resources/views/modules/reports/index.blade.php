<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Reports Module
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900">Student Result Card PDF</h3>
                    <form id="studentReportForm" method="GET" action="{{ route('reports.pdf.student-result-card') }}" target="_blank" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <x-input-label for="student_report_class_id" value="Class" />
                            <select id="student_report_class_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach($classes as $classRoom)
                                    <option value="{{ $classRoom->id }}">{{ trim($classRoom->name.' '.($classRoom->section ?? '')) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="student_id" value="Student" />
                            <select id="student_id" name="student_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></select>
                        </div>
                        <div>
                            <x-input-label for="session" value="Session" />
                            <select id="session" name="session" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach($sessions as $session)
                                    <option value="{{ $session }}" @selected($session === $defaultSession)>{{ $session }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="exam_type" value="Exam Type" />
                            <select id="exam_type" name="exam_type" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach($examTypes as $examType)
                                    <option value="{{ $examType['value'] }}">{{ $examType['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <x-primary-button>Generate PDF</x-primary-button>
                        </div>
                    </form>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900">Class Result PDF</h3>
                    <form method="GET" action="{{ route('reports.pdf.class-result') }}" target="_blank" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <x-input-label for="class_result_class_id" value="Class" />
                            <select id="class_result_class_id" name="class_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach($classes as $classRoom)
                                    <option value="{{ $classRoom->id }}">{{ trim($classRoom->name.' '.($classRoom->section ?? '')) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="class_result_session" value="Session" />
                            <select id="class_result_session" name="session" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach($sessions as $session)
                                    <option value="{{ $session }}" @selected($session === $defaultSession)>{{ $session }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="class_result_exam_type" value="Exam Type" />
                            <select id="class_result_exam_type" name="exam_type" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach($examTypes as $examType)
                                    <option value="{{ $examType['value'] }}">{{ $examType['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <x-primary-button>Generate PDF</x-primary-button>
                        </div>
                    </form>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900">Medical Report PDF</h3>
                    <form method="GET" action="{{ route('reports.pdf.medical-report') }}" target="_blank" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <x-input-label for="medical_report_type" value="Report Type" />
                            <select id="medical_report_type" name="report_type" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="monthly">Monthly</option>
                                <option value="yearly">Yearly</option>
                            </select>
                        </div>
                        <div id="medicalMonthWrap">
                            <x-input-label for="medical_month" value="Month" />
                            <select id="medical_month" name="month" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @for($i = 1; $i <= 12; $i++)
                                    <option value="{{ $i }}" @selected($i === now()->month)>{{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                        <div>
                            <x-input-label for="medical_year" value="Year" />
                            <x-text-input id="medical_year" name="year" type="number" class="mt-1 block w-full" value="{{ now()->year }}" />
                        </div>
                        <div>
                            <x-input-label for="medical_student_id" value="Student ID (Optional)" />
                            <x-text-input id="medical_student_id" name="student_id" type="number" class="mt-1 block w-full" />
                        </div>
                        <div class="md:col-span-2">
                            <x-primary-button>Generate PDF</x-primary-button>
                        </div>
                    </form>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900">Attendance Report PDF</h3>
                    <form method="GET" action="{{ route('reports.pdf.attendance-report') }}" target="_blank" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <x-input-label for="attendance_date" value="Date" />
                            <x-text-input id="attendance_date" name="date" type="date" class="mt-1 block w-full" value="{{ now()->toDateString() }}" />
                        </div>
                        <div>
                            <x-input-label for="attendance_class_id" value="Class (Optional)" />
                            <select id="attendance_class_id" name="class_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All Classes</option>
                                @foreach($classes as $classRoom)
                                    <option value="{{ $classRoom->id }}">{{ trim($classRoom->name.' '.($classRoom->section ?? '')) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <x-primary-button>Generate PDF</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        const studentReportClassInput = document.getElementById('student_report_class_id');
        const studentSelectInput = document.getElementById('student_id');
        const medicalReportTypeInput = document.getElementById('medical_report_type');
        const medicalMonthWrap = document.getElementById('medicalMonthWrap');

        async function loadStudentsForSelectedClass() {
            const classId = Number(studentReportClassInput.value);
            if (!classId) {
                studentSelectInput.innerHTML = '<option value="">No students</option>';
                return;
            }

            const params = new URLSearchParams({ class_id: classId });
            const response = await fetch(`{{ route('principal.results.students') }}?${params.toString()}`, {
                headers: { 'Accept': 'application/json' }
            });

            if (!response.ok) {
                studentSelectInput.innerHTML = '<option value="">No students</option>';
                return;
            }

            const result = await response.json();
            const students = result.students || [];

            if (!students.length) {
                studentSelectInput.innerHTML = '<option value="">No students</option>';
                return;
            }

            studentSelectInput.innerHTML = students
                .map(student => `<option value="${student.id}">${student.name} (${student.student_id})</option>`)
                .join('');
        }

        studentReportClassInput.addEventListener('change', loadStudentsForSelectedClass);

        medicalReportTypeInput.addEventListener('change', () => {
            if (medicalReportTypeInput.value === 'yearly') {
                medicalMonthWrap.classList.add('hidden');
            } else {
                medicalMonthWrap.classList.remove('hidden');
            }
        });

        loadStudentsForSelectedClass();
    </script>
</x-app-layout>

