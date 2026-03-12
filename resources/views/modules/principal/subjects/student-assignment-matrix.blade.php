<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">Subject Assignment Matrix</h2>
            <p class="mt-1 text-sm text-slate-500">Manage subjects assigned to students</p>
        </div>
    </x-slot>

    <div
        class="py-8"
        x-data="subjectAssignmentMatrixPage({
            defaultSession: @js($defaultSession),
            defaultClassId: @js($defaultClassId),
            classOptions: @js($classes->map(fn($classRoom) => [
                'id' => (int) $classRoom->id,
                'label' => trim($classRoom->name.' '.($classRoom->section ?? '')),
            ])->values()->all()),
            matrixUrl: @js(route('principal.student-subjects.data')),
            updateUrl: @js(route('principal.student-subjects.update')),
            assignClassUrl: @js(route('principal.student-subjects.assign-class')),
            subjectGroupsUrl: @js(route('principal.subject-groups.index')),
            storeGroupUrl: @js(route('principal.subject-groups.store')),
            assignGroupUrl: @js(route('principal.student-subjects.assign-group')),
            csrfToken: @js(csrf_token()),
        })"
        x-init="init()"
    >
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="p-6">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-6">
                        <div>
                            <x-input-label for="session_filter" value="Session" />
                            <select
                                id="session_filter"
                                x-model="session"
                                class="mt-1 block min-h-11 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                @foreach($sessions as $session)
                                    <option value="{{ $session }}">{{ $session }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <x-input-label for="class_filter" value="Class" />
                            <select
                                id="class_filter"
                                x-model.number="classId"
                                class="mt-1 block min-h-11 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">Select class</option>
                                @foreach($classes as $classRoom)
                                    <option value="{{ $classRoom->id }}">
                                        {{ trim($classRoom->name.' '.($classRoom->section ?? '')) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="lg:col-span-2">
                            <x-input-label for="student_search" value="Search Student" />
                            <input
                                id="student_search"
                                x-model="search"
                                @keydown.enter.prevent="loadStudents(true)"
                                type="text"
                                placeholder="Search by name, student id, father name"
                                class="mt-1 block min-h-11 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                        </div>

                        <div>
                            <x-input-label for="per_page" value="Rows Per Page" />
                            <select
                                id="per_page"
                                x-model.number="perPage"
                                class="mt-1 block min-h-11 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="20">20</option>
                                <option value="40">40</option>
                                <option value="60">60</option>
                            </select>
                        </div>

                        <div class="flex items-end">
                            <button
                                type="button"
                                @click="loadStudents(true)"
                                :disabled="loading"
                                class="inline-flex min-h-11 w-full items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <span x-text="loading ? 'Loading...' : 'Load Students'"></span>
                            </button>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                        <div class="inline-flex rounded-lg border border-slate-200 bg-slate-50 p-1">
                            <button
                                type="button"
                                @click="viewMode = 'dropdown'"
                                class="rounded-md px-3 py-1.5 text-xs font-medium"
                                :class="viewMode === 'dropdown' ? 'bg-white text-slate-900 shadow' : 'text-slate-600'"
                            >
                                Dropdown View
                            </button>
                            <button
                                type="button"
                                @click="viewMode = 'matrix'"
                                class="rounded-md px-3 py-1.5 text-xs font-medium"
                                :class="viewMode === 'matrix' ? 'bg-white text-slate-900 shadow' : 'text-slate-600'"
                            >
                                Matrix View
                            </button>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <button
                                type="button"
                                @click="saveAllStudentChanges()"
                                :disabled="savingAllChanges || dirtyStudentIds.length === 0"
                                class="inline-flex min-h-10 items-center rounded-md bg-emerald-600 px-3 py-2 text-xs font-medium text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <span x-text="savingAllChanges ? 'Saving...' : `Save Changes (${dirtyStudentIds.length})`"></span>
                            </button>
                            <p class="text-xs text-slate-500" x-text="paginationText()"></p>
                        </div>
                    </div>

                    <div
                        x-show="status.message !== ''"
                        x-cloak
                        class="mt-4 rounded-md px-4 py-3 text-sm"
                        :class="status.type === 'error' ? 'border border-red-200 bg-red-50 text-red-700' : 'border border-emerald-200 bg-emerald-50 text-emerald-700'"
                        x-text="status.message"
                    ></div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 p-6 pb-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h3 class="text-base font-semibold text-slate-900">Subject Groups</h3>
                            <p class="mt-1 text-xs text-slate-500">Define elective groups for the selected class and assign each student to one group.</p>
                        </div>
                        <button
                            type="button"
                            @click="openCreateGroupModal()"
                            :disabled="!classId || !session || creatingGroup"
                            class="inline-flex min-h-10 items-center rounded-md bg-indigo-600 px-3 py-2 text-xs font-medium text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <span x-text="creatingGroup ? 'Saving...' : 'Create Group'"></span>
                        </button>
                    </div>
                </div>
                <div class="p-6">
                    <div class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="text-xs text-slate-600">Session: <span class="font-medium text-slate-800" x-text="session || '-'"></span> | Class: <span class="font-medium text-slate-800" x-text="selectedClassLabel()"></span></p>
                        <button
                            type="button"
                            @click="loadSubjectGroups()"
                            :disabled="loadingGroups || !classId || !session"
                            class="inline-flex min-h-9 items-center rounded-md border border-slate-300 bg-white px-3 py-1.5 text-[11px] font-medium text-slate-700 hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <span x-text="loadingGroups ? 'Refreshing...' : 'Refresh Groups'"></span>
                        </button>
                    </div>

                    <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                        <template x-if="!loadingGroups && subjectGroups.length === 0">
                            <div class="rounded-lg border border-dashed border-slate-300 px-4 py-5 text-sm text-slate-500">
                                No subject groups found for this class/session.
                            </div>
                        </template>

                        <template x-for="group in subjectGroups" :key="'group-card-'+group.id">
                            <div class="rounded-lg border border-slate-200 bg-white px-4 py-4">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <h4 class="text-sm font-semibold text-slate-900" x-text="group.name"></h4>
                                        <p class="mt-1 text-xs text-slate-500" x-text="group.description || 'No description'"></p>
                                    </div>
                                    <span class="inline-flex items-center rounded-full bg-indigo-50 px-2 py-1 text-[10px] font-medium text-indigo-700" x-text="`${group.subjects_count} subjects`"></span>
                                </div>
                                <div class="mt-3 flex flex-wrap gap-1">
                                    <template x-for="subject in group.subjects" :key="'group-sub-'+group.id+'-'+subject.id">
                                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[10px] text-slate-700" x-text="subject.name"></span>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 p-6 pb-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h3 class="text-base font-semibold text-slate-900">Common Subjects (Entire Class)</h3>
                            <p class="mt-1 text-xs text-slate-500">Pick common subjects once and update all students in this class.</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <button
                                type="button"
                                @click="assignSelectedToClass()"
                                :disabled="savingBulk || !classId || !session || bulkSubjects.length === 0"
                                class="inline-flex min-h-10 items-center justify-center rounded-md bg-slate-900 px-3 py-2 text-xs font-medium text-white hover:bg-black disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <span x-text="savingBulk ? 'Assigning...' : 'Assign Selected Subjects'"></span>
                            </button>
                            <div class="inline-flex items-center rounded-full bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-700">
                                <span x-text="`${bulkSubjects.length} selected`"></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <x-input-label value="Subjects" />
                            <div class="relative mt-1 max-w-3xl" x-data="{ open: false }" @click.outside="open = false">
                                <button
                                    type="button"
                                    @click="open = !open"
                                    class="flex min-h-11 w-full items-center justify-between rounded-md border border-slate-300 bg-white px-3 py-2 text-left text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                >
                                    <span class="truncate" x-text="bulkSelectionLabel()"></span>
                                    <span class="text-slate-400">&#9662;</span>
                                </button>

                                <div
                                    x-show="open"
                                    x-cloak
                                    class="absolute z-30 mt-1 w-full rounded-md border border-slate-200 bg-white shadow-lg"
                                >
                                    <div class="border-b border-slate-100 px-3 py-2">
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <span class="text-xs font-medium text-slate-500" x-text="`${bulkSubjects.length} selected`"></span>
                                            <div class="inline-flex items-center gap-2">
                                                <button
                                                    type="button"
                                                    @click="selectAllBulkSubjects()"
                                                    :disabled="subjects.length === 0"
                                                    class="inline-flex min-h-8 items-center rounded-md border border-slate-300 px-2.5 py-1 text-[11px] font-medium text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                                                >
                                                    Select All
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="clearBulkSubjects()"
                                                    :disabled="bulkSubjects.length === 0"
                                                    class="inline-flex min-h-8 items-center rounded-md border border-slate-300 px-2.5 py-1 text-[11px] font-medium text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                                                >
                                                    Clear
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="border-b border-slate-100 px-3 py-2">
                                        <input
                                            type="text"
                                            x-model.trim="bulkSubjectSearch"
                                            placeholder="Search subjects"
                                            class="block min-h-10 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                    </div>

                                    <div class="max-h-72 overflow-y-auto overscroll-contain p-2 pr-1" style="scrollbar-gutter: stable;">
                                        <p class="px-2 py-1 text-[11px] text-slate-400">Scroll to view more subjects</p>
                                        <template x-if="filteredBulkSubjects().length === 0">
                                            <p class="px-2 py-2 text-sm text-slate-500">No matching subjects found.</p>
                                        </template>
                                        <div class="grid grid-cols-1 gap-1 sm:grid-cols-2">
                                            <template x-for="subject in filteredBulkSubjects()" :key="'bulk-'+subject.id">
                                                <label class="inline-flex items-center gap-2 rounded-md px-2 py-2 text-sm text-slate-700 hover:bg-slate-50">
                                                    <input
                                                        type="checkbox"
                                                        class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                                        :value="subject.id"
                                                        x-model="bulkSubjects"
                                                        @change="bulkSubjects = normalizeIds(bulkSubjects)"
                                                    >
                                                    <span x-text="subject.name"></span>
                                                </label>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                            <p class="text-xs text-slate-600">
                                Assign selected subjects to every student in the selected class and session.
                            </p>
                            <button
                                type="button"
                                @click="assignSelectedToClass()"
                                :disabled="savingBulk || !classId || !session || bulkSubjects.length === 0"
                                class="inline-flex min-h-11 items-center justify-center rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-black disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <span x-text="savingBulk ? 'Assigning...' : 'Assign to Entire Class'"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <template x-if="viewMode === 'dropdown'">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Student Name</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Group</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Subjects</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Last Updated</th>
                                </tr>
                            </thead>
                        </template>

                        <template x-if="viewMode === 'matrix'">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Student Name</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Group</th>
                                    <template x-for="subject in subjects" :key="'head-'+subject.id">
                                        <th class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wider text-slate-600" x-text="subject.name"></th>
                                    </template>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Last Updated</th>
                                </tr>
                            </thead>
                        </template>

                        <tbody class="divide-y divide-slate-200 bg-white">
                            <template x-if="loading">
                                <tr>
                                    <td :colspan="viewMode === 'matrix' ? (subjects.length + 3) : 4" class="px-4 py-8 text-center text-sm text-slate-500">
                                        Loading students...
                                    </td>
                                </tr>
                            </template>

                            <template x-if="!loading && students.length === 0">
                                <tr>
                                    <td :colspan="viewMode === 'matrix' ? (subjects.length + 3) : 4" class="px-4 py-8 text-center text-sm text-slate-500">
                                        No students found for the selected filters.
                                    </td>
                                </tr>
                            </template>

                            <template x-for="student in students" :key="'row-'+student.id">
                                <tr class="align-top">
                                    <td class="px-4 py-3 text-sm text-slate-800">
                                        <div class="font-medium" x-text="student.name"></div>
                                        <div class="text-xs text-slate-500" x-text="student.student_id"></div>
                                    </td>

                                    <td class="px-4 py-3 text-sm text-slate-800">
                                        <select
                                            :value="groupByStudent[student.id] ?? ''"
                                            @change="onStudentGroupChange(student.id, $event.target.value)"
                                            :disabled="groupSavingByStudent[student.id] || !session || !classId"
                                            class="block min-h-10 w-full min-w-44 rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:cursor-not-allowed disabled:opacity-60"
                                        >
                                            <option value="">No Group</option>
                                            <template x-for="group in subjectGroups" :key="'group-option-'+student.id+'-'+group.id">
                                                <option :value="group.id" x-text="group.name"></option>
                                            </template>
                                        </select>
                                        <div class="mt-1 flex flex-wrap items-center gap-1 text-[10px] text-slate-500">
                                            <span>Elective Group</span>
                                            <span x-show="groupSavingByStudent[student.id]" class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 font-medium text-amber-700">Assigning</span>
                                        </div>
                                    </td>

                                    <template x-if="viewMode === 'dropdown'">
                                        <td class="px-4 py-3 text-sm text-slate-800">
                                            <div class="relative max-w-md" x-data="{ open: false }" @click.outside="open = false">
                                                <button
                                                    type="button"
                                                    @click="open = !open"
                                                    class="flex min-h-11 w-full items-center justify-between rounded-md border border-slate-300 bg-white px-3 py-2 text-left text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                                >
                                                    <span class="truncate" x-text="studentSelectionLabel(student.id)"></span>
                                                    <span class="text-slate-400">&#9662;</span>
                                                </button>

                                                <div
                                                    x-show="open"
                                                    x-cloak
                                                    class="absolute z-20 mt-1 max-h-64 w-full overflow-auto rounded-md border border-slate-200 bg-white shadow-lg"
                                                >
                                                    <template x-if="subjects.length === 0">
                                                        <div class="px-3 py-2 text-xs text-slate-500">No class subjects defined.</div>
                                                    </template>
                                                    <template x-for="subject in subjects" :key="'student-'+student.id+'-subject-'+subject.id">
                                                        <label class="flex items-center gap-2 px-3 py-2 text-sm hover:bg-slate-50">
                                                            <input
                                                                type="checkbox"
                                                                class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                                                :checked="selectedByStudent[student.id] && selectedByStudent[student.id].includes(subject.id)"
                                                                @change="onStudentSubjectChange(student.id, subject.id, $event.target.checked)"
                                                            >
                                                            <span x-text="subject.name"></span>
                                                        </label>
                                                    </template>
                                                </div>
                                            </div>
                                        </td>
                                    </template>

                                    <template x-if="viewMode === 'matrix'">
                                        <template x-for="subject in subjects" :key="'cell-'+student.id+'-'+subject.id">
                                            <td class="px-3 py-3 text-center">
                                                <input
                                                    type="checkbox"
                                                    class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                                    :checked="selectedByStudent[student.id] && selectedByStudent[student.id].includes(subject.id)"
                                                    @change="onStudentSubjectChange(student.id, subject.id, $event.target.checked)"
                                                >
                                            </td>
                                        </template>
                                    </template>

                                    <td class="px-4 py-3 text-sm text-slate-600">
                                        <span x-text="formatDateTime(student.last_updated_at)"></span>
                                        <span x-show="isDirtyStudent(student.id)" class="ms-2 inline-flex items-center rounded-full bg-sky-100 px-2 py-0.5 text-[10px] font-medium text-sky-700">Pending</span>
                                        <span x-show="savingByStudent[student.id]" class="ms-2 inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-medium text-amber-700">Saving</span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-200 p-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <p class="text-xs text-slate-500" x-text="paginationText()"></p>
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="changePage(pagination.current_page - 1)"
                                :disabled="loading || pagination.current_page <= 1"
                                class="inline-flex min-h-10 items-center rounded-md border border-slate-300 px-3 text-xs font-medium text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                Previous
                            </button>
                            <span class="text-xs text-slate-600" x-text="`Page ${pagination.current_page} of ${pagination.last_page}`"></span>
                            <button
                                type="button"
                                @click="changePage(pagination.current_page + 1)"
                                :disabled="loading || pagination.current_page >= pagination.last_page"
                                class="inline-flex min-h-10 items-center rounded-md border border-slate-300 px-3 text-xs font-medium text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                Next
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div
                x-show="groupModalOpen"
                x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
            >
                <div class="absolute inset-0 bg-slate-900/50" @click="closeCreateGroupModal()"></div>

                <div class="relative z-10 w-full max-w-2xl rounded-2xl border border-slate-200 bg-white shadow-2xl">
                    <div class="border-b border-slate-100 p-5">
                        <h3 class="text-base font-semibold text-slate-900">Create Subject Group</h3>
                        <p class="mt-1 text-xs text-slate-500">Create a class/session-specific elective group and assign subjects to it.</p>
                    </div>

                    <div class="space-y-4 p-5">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <x-input-label value="Session" />
                                <select
                                    x-model="groupForm.session"
                                    class="mt-1 block min-h-10 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    @foreach($sessions as $session)
                                        <option value="{{ $session }}">{{ $session }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <x-input-label value="Class" />
                                <select
                                    x-model.number="groupForm.class_id"
                                    class="mt-1 block min-h-10 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">Select class</option>
                                    @foreach($classes as $classRoom)
                                        <option value="{{ $classRoom->id }}">
                                            {{ trim($classRoom->name.' '.($classRoom->section ?? '')) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <x-input-label value="Group Name" />
                                <input
                                    x-model.trim="groupForm.name"
                                    type="text"
                                    placeholder="e.g. Group A - Biology"
                                    class="mt-1 block min-h-10 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                            </div>

                            <div>
                                <x-input-label value="Description" />
                                <input
                                    x-model.trim="groupForm.description"
                                    type="text"
                                    placeholder="Optional details"
                                    class="mt-1 block min-h-10 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                            </div>
                        </div>

                        <div>
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <x-input-label value="Subjects" />
                                <span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-1 text-[11px] font-medium text-indigo-700" x-text="`${groupForm.subjects.length} selected`"></span>
                            </div>
                            <input
                                x-model.trim="groupFormSearch"
                                type="text"
                                placeholder="Search subjects"
                                class="mt-2 block min-h-10 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                            <div class="mt-2 max-h-64 overflow-y-auto rounded-lg border border-slate-200 p-2">
                                <template x-if="filteredGroupFormSubjects().length === 0">
                                    <p class="px-2 py-2 text-sm text-slate-500">No matching subjects found.</p>
                                </template>
                                <div class="grid grid-cols-1 gap-1 sm:grid-cols-2">
                                    <template x-for="subject in filteredGroupFormSubjects()" :key="'group-form-'+subject.id">
                                        <label class="inline-flex items-center gap-2 rounded-md px-2 py-2 text-sm text-slate-700 hover:bg-slate-50">
                                            <input
                                                type="checkbox"
                                                class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                                :checked="groupForm.subjects.includes(subject.id)"
                                                @change="toggleGroupFormSubject(subject.id, $event.target.checked)"
                                            >
                                            <span x-text="subject.name"></span>
                                        </label>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-100 p-5">
                        <button
                            type="button"
                            @click="closeCreateGroupModal()"
                            :disabled="creatingGroup"
                            class="inline-flex min-h-10 items-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            @click="createSubjectGroup()"
                            :disabled="creatingGroup"
                            class="inline-flex min-h-10 items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            <span x-text="creatingGroup ? 'Saving...' : 'Save Group'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function subjectAssignmentMatrixPage(config) {
            return {
                session: config.defaultSession || '',
                classId: config.defaultClassId ? Number(config.defaultClassId) : '',
                classOptions: config.classOptions || [],
                search: '',
                perPage: 20,
                viewMode: 'dropdown',
                loading: false,
                savingBulk: false,
                savingAllChanges: false,
                loadingGroups: false,
                creatingGroup: false,
                savingByStudent: {},
                groupSavingByStudent: {},
                status: {
                    message: '',
                    type: 'success',
                },
                subjects: [],
                subjectGroups: [],
                students: [],
                selectedByStudent: {},
                groupByStudent: {},
                dirtyStudentIds: [],
                bulkSubjects: [],
                bulkSubjectSearch: '',
                groupModalOpen: false,
                groupFormSearch: '',
                groupForm: {
                    session: '',
                    class_id: '',
                    name: '',
                    description: '',
                    subjects: [],
                },
                pagination: {
                    current_page: 1,
                    last_page: 1,
                    per_page: 20,
                    total: 0,
                    from: null,
                    to: null,
                },

                init() {
                    if (this.classId && this.session) {
                        this.loadStudents(true);
                    }
                },

                setStatus(message, type = 'success') {
                    this.status = { message, type };
                },

                clearStatus() {
                    this.status = { message: '', type: 'success' };
                },

                selectedClassLabel() {
                    const id = Number(this.classId || 0);
                    const match = this.classOptions.find((item) => Number(item.id) === id);

                    return match ? match.label : '-';
                },

                resetGroupForm() {
                    this.groupForm = {
                        session: this.session || '',
                        class_id: this.classId ? Number(this.classId) : '',
                        name: '',
                        description: '',
                        subjects: [],
                    };
                    this.groupFormSearch = '';
                },

                openCreateGroupModal() {
                    this.clearStatus();
                    if (!this.session || !this.classId) {
                        this.setStatus('Select session and class before creating a subject group.', 'error');
                        return;
                    }

                    if (this.subjects.length === 0) {
                        this.setStatus('Load class students first so available class subjects can be used in the group.', 'error');
                        return;
                    }

                    this.resetGroupForm();
                    this.groupModalOpen = true;
                },

                closeCreateGroupModal() {
                    if (this.creatingGroup) {
                        return;
                    }

                    this.groupModalOpen = false;
                    this.resetGroupForm();
                },

                normalizeIds(values) {
                    return [...new Set(
                        (values || [])
                            .map((value) => Number(value))
                            .filter((value) => Number.isInteger(value) && value > 0)
                    )];
                },

                paginationText() {
                    if (!this.pagination.total || !this.pagination.from || !this.pagination.to) {
                        return 'No records loaded';
                    }

                    return `Showing ${this.pagination.from}-${this.pagination.to} of ${this.pagination.total} students`;
                },

                bulkSelectionLabel() {
                    if (!this.bulkSubjects.length) {
                        return '0 selected';
                    }

                    const names = this.subjects
                        .filter((subject) => this.bulkSubjects.includes(subject.id))
                        .map((subject) => subject.name);

                    if (!names.length) {
                        return `${this.bulkSubjects.length} selected`;
                    }

                    const preview = names.slice(0, 3).join(', ');
                    const remaining = names.length - 3;

                    return remaining > 0
                        ? `${preview} +${remaining} more`
                        : preview;
                },

                filteredBulkSubjects() {
                    const term = (this.bulkSubjectSearch || '').trim().toLowerCase();
                    if (!term) {
                        return this.subjects;
                    }

                    return this.subjects.filter((subject) =>
                        String(subject.name || '').toLowerCase().includes(term)
                    );
                },

                filteredGroupFormSubjects() {
                    const term = (this.groupFormSearch || '').trim().toLowerCase();
                    if (!term) {
                        return this.subjects;
                    }

                    return this.subjects.filter((subject) =>
                        String(subject.name || '').toLowerCase().includes(term)
                    );
                },

                studentSelectionLabel(studentId) {
                    const selected = this.selectedByStudent[studentId] || [];
                    if (!selected.length) {
                        return 'Select common subjects';
                    }

                    const names = this.subjects
                        .filter((subject) => selected.includes(subject.id))
                        .map((subject) => subject.name);

                    return names.length ? names.join(', ') : 'Select common subjects';
                },

                toggleGroupFormSubject(subjectId, checked) {
                    const id = Number(subjectId);
                    const current = this.groupForm.subjects ? this.groupForm.subjects.slice() : [];

                    if (checked && !current.includes(id)) {
                        current.push(id);
                    }

                    if (!checked) {
                        this.groupForm.subjects = current.filter((value) => value !== id);
                        return;
                    }

                    this.groupForm.subjects = this.normalizeIds(current);
                },

                isDirtyStudent(studentId) {
                    return this.dirtyStudentIds.includes(Number(studentId));
                },

                markStudentDirty(studentId) {
                    const sid = Number(studentId);
                    if (!this.dirtyStudentIds.includes(sid)) {
                        this.dirtyStudentIds.push(sid);
                    }
                },

                clearStudentDirty(studentId) {
                    const sid = Number(studentId);
                    this.dirtyStudentIds = this.dirtyStudentIds.filter((value) => value !== sid);
                },

                toggleBulkSubject(subjectId, checked) {
                    const id = Number(subjectId);
                    const current = this.bulkSubjects.slice();

                    if (checked && !current.includes(id)) {
                        current.push(id);
                    }

                    if (!checked) {
                        this.bulkSubjects = current.filter((value) => value !== id);
                        return;
                    }

                    this.bulkSubjects = this.normalizeIds(current);
                },

                selectAllBulkSubjects() {
                    this.bulkSubjects = this.normalizeIds(this.subjects.map((subject) => Number(subject.id)));
                },

                clearBulkSubjects() {
                    this.bulkSubjects = [];
                },

                formatDateTime(value) {
                    if (!value) {
                        return '-';
                    }

                    const parsed = new Date(value);
                    if (Number.isNaN(parsed.getTime())) {
                        return value;
                    }

                    return parsed.toLocaleString();
                },

                changePage(nextPage) {
                    if (nextPage < 1 || nextPage > this.pagination.last_page) {
                        return;
                    }

                    this.loadStudents(false, nextPage);
                },

                async loadSubjectGroups(silent = false) {
                    if (!this.session || !this.classId) {
                        this.subjectGroups = [];
                        return;
                    }

                    this.loadingGroups = true;
                    if (!silent) {
                        this.clearStatus();
                    }

                    try {
                        const params = new URLSearchParams({
                            session: this.session,
                            class_id: String(this.classId),
                        });

                        const response = await fetch(`${config.subjectGroupsUrl}?${params.toString()}`, {
                            headers: { Accept: 'application/json' },
                        });

                        const result = await response.json();
                        if (!response.ok) {
                            if (!silent) {
                                this.setStatus(result.message || 'Failed to load subject groups.', 'error');
                            }
                            return;
                        }

                        this.subjectGroups = (result.groups || []).map((group) => ({
                            id: Number(group.id),
                            session: group.session,
                            class_id: Number(group.class_id),
                            name: group.name || '',
                            description: group.description || '',
                            is_active: Boolean(group.is_active),
                            subjects_count: Number(group.subjects_count || 0),
                            subject_ids: this.normalizeIds(group.subject_ids || []),
                            subjects: (group.subjects || []).map((subject) => ({
                                id: Number(subject.id),
                                name: subject.name || '',
                                code: subject.code || '',
                            })),
                        }));
                    } catch (error) {
                        if (!silent) {
                            this.setStatus('Unexpected error while loading subject groups.', 'error');
                        }
                    } finally {
                        this.loadingGroups = false;
                    }
                },

                async createSubjectGroup() {
                    this.clearStatus();
                    if (!this.groupForm.session || !this.groupForm.class_id) {
                        this.setStatus('Session and class are required for creating a group.', 'error');
                        return;
                    }

                    if (!this.groupForm.name) {
                        this.setStatus('Group name is required.', 'error');
                        return;
                    }

                    const selectedSubjects = this.normalizeIds(this.groupForm.subjects || []);
                    if (selectedSubjects.length === 0) {
                        this.setStatus('Select at least one subject for this group.', 'error');
                        return;
                    }

                    this.creatingGroup = true;
                    try {
                        const response = await fetch(config.storeGroupUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                Accept: 'application/json',
                                'X-CSRF-TOKEN': config.csrfToken,
                            },
                            body: JSON.stringify({
                                session: this.groupForm.session,
                                class_id: Number(this.groupForm.class_id),
                                name: this.groupForm.name,
                                description: this.groupForm.description || null,
                                subjects: selectedSubjects,
                            }),
                        });

                        const result = await response.json();
                        if (!response.ok) {
                            this.setStatus(result.message || 'Failed to create subject group.', 'error');
                            return;
                        }

                        this.setStatus(result.message || 'Subject group created successfully.');
                        this.groupModalOpen = false;
                        this.resetGroupForm();
                        await this.loadSubjectGroups(true);
                    } catch (error) {
                        this.setStatus('Unexpected error while creating subject group.', 'error');
                    } finally {
                        this.creatingGroup = false;
                    }
                },

                async onStudentGroupChange(studentId, rawGroupId) {
                    const sid = Number(studentId);
                    const previousGroupId = this.groupByStudent[sid] !== undefined && this.groupByStudent[sid] !== null
                        ? Number(this.groupByStudent[sid])
                        : null;

                    const nextGroupId = rawGroupId === '' ? null : Number(rawGroupId);
                    if (previousGroupId === nextGroupId) {
                        return;
                    }

                    this.groupByStudent[sid] = nextGroupId;
                    this.groupSavingByStudent[sid] = true;
                    this.clearStatus();

                    try {
                        const response = await fetch(config.assignGroupUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                Accept: 'application/json',
                                'X-CSRF-TOKEN': config.csrfToken,
                            },
                            body: JSON.stringify({
                                student_id: sid,
                                session: this.session,
                                group_id: nextGroupId,
                            }),
                        });

                        const result = await response.json();
                        if (!response.ok) {
                            this.groupByStudent[sid] = previousGroupId;
                            this.setStatus(result.message || 'Failed to assign subject group.', 'error');
                            return;
                        }

                        const row = this.students.find((student) => student.id === sid);
                        if (row) {
                            row.last_updated_at = result.updated_at || (new Date()).toISOString();
                        }

                        if (Number(result.skipped_due_common || 0) > 0) {
                            this.setStatus(`Group assigned, but ${result.skipped_due_common} overlapping subjects were skipped because they are already common subjects.`);
                        } else {
                            this.setStatus('Student group assignment updated.');
                        }
                    } catch (error) {
                        this.groupByStudent[sid] = previousGroupId;
                        this.setStatus('Unexpected error while assigning subject group.', 'error');
                    } finally {
                        this.groupSavingByStudent[sid] = false;
                    }
                },

                async loadStudents(resetPage = true, targetPage = null) {
                    this.clearStatus();
                    if (!this.session || !this.classId) {
                        this.setStatus('Session and class are required.', 'error');
                        this.students = [];
                        this.subjects = [];
                        this.subjectGroups = [];
                        this.selectedByStudent = {};
                        this.groupByStudent = {};
                        return;
                    }

                    this.loading = true;
                    try {
                        const page = targetPage !== null
                            ? Number(targetPage)
                            : (resetPage ? 1 : Number(this.pagination.current_page || 1));

                        const params = new URLSearchParams({
                            session: this.session,
                            class_id: String(this.classId),
                            search: this.search || '',
                            page: String(Math.max(page, 1)),
                            per_page: String(this.perPage || 20),
                        });

                        const response = await fetch(`${config.matrixUrl}?${params.toString()}`, {
                            headers: { Accept: 'application/json' },
                        });

                        const result = await response.json();
                        if (!response.ok) {
                            this.setStatus(result.message || 'Failed to load students.', 'error');
                            return;
                        }

                        this.subjects = (result.subjects || []).map((subject) => ({
                            id: Number(subject.id),
                            name: subject.name,
                        }));
                        this.bulkSubjectSearch = '';

                        this.students = (result.students || []).map((student) => ({
                            id: Number(student.id),
                            student_id: student.student_id || '',
                            name: student.name || '',
                            last_updated_at: student.last_updated_at || '',
                            assigned_subject_ids: this.normalizeIds(student.common_subject_ids || student.assigned_subject_ids || []),
                            subject_group_id: student.subject_group_id ? Number(student.subject_group_id) : null,
                        }));

                        const nextMap = {};
                        const nextGroupMap = {};
                        this.students.forEach((student) => {
                            nextMap[student.id] = this.normalizeIds(student.assigned_subject_ids);
                            nextGroupMap[student.id] = student.subject_group_id;
                        });
                        this.selectedByStudent = nextMap;
                        this.groupByStudent = nextGroupMap;
                        this.dirtyStudentIds = [];
                        this.savingByStudent = {};
                        this.groupSavingByStudent = {};

                        const incomingPagination = result.pagination || {};
                        this.pagination = {
                            current_page: Number(incomingPagination.current_page || 1),
                            last_page: Number(incomingPagination.last_page || 1),
                            per_page: Number(incomingPagination.per_page || this.perPage),
                            total: Number(incomingPagination.total || 0),
                            from: incomingPagination.from,
                            to: incomingPagination.to,
                        };

                        await this.loadSubjectGroups(true);
                    } catch (error) {
                        this.setStatus('Unexpected error while loading students.', 'error');
                    } finally {
                        this.loading = false;
                    }
                },

                onStudentSubjectChange(studentId, subjectId, checked) {
                    const sid = Number(studentId);
                    const subId = Number(subjectId);
                    const current = this.selectedByStudent[sid] ? this.selectedByStudent[sid].slice() : [];

                    if (checked && !current.includes(subId)) {
                        current.push(subId);
                    }
                    if (!checked) {
                        this.selectedByStudent[sid] = current.filter((value) => value !== subId);
                    } else {
                        this.selectedByStudent[sid] = this.normalizeIds(current);
                    }

                    this.markStudentDirty(sid);
                },

                async persistStudentSubjects(studentId, silent = false) {
                    const sid = Number(studentId);
                    this.savingByStudent[sid] = true;
                    if (!silent) {
                        this.clearStatus();
                    }

                    try {
                        const response = await fetch(config.updateUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                Accept: 'application/json',
                                'X-CSRF-TOKEN': config.csrfToken,
                            },
                            body: JSON.stringify({
                                student_id: sid,
                                session: this.session,
                                subjects: this.normalizeIds(this.selectedByStudent[sid] || []),
                            }),
                        });

                        const result = await response.json();
                        if (!response.ok) {
                            if (!silent) {
                                this.setStatus(result.message || 'Failed to save student subjects.', 'error');
                            }
                            return false;
                        }

                        const row = this.students.find((student) => student.id === sid);
                        if (row) {
                            row.last_updated_at = result.updated_at || (new Date()).toISOString();
                        }

                        this.clearStudentDirty(sid);

                        return true;
                    } catch (error) {
                        if (!silent) {
                            this.setStatus('Unexpected error while saving student subjects.', 'error');
                        }
                        return false;
                    } finally {
                        this.savingByStudent[sid] = false;
                    }
                },

                async saveAllStudentChanges() {
                    this.clearStatus();
                    const pending = this.dirtyStudentIds.slice();
                    if (pending.length === 0) {
                        this.setStatus('No pending student changes to save.');
                        return;
                    }

                    this.savingAllChanges = true;
                    let successCount = 0;

                    for (const studentId of pending) {
                        const ok = await this.persistStudentSubjects(studentId, true);
                        if (ok) {
                            successCount++;
                        }
                    }

                    if (successCount === pending.length) {
                        this.setStatus(`Saved changes for ${successCount} students.`);
                    } else {
                        this.setStatus(`Saved ${successCount} of ${pending.length} students. Please retry remaining pending rows.`, 'error');
                    }

                    this.savingAllChanges = false;
                },

                async assignSelectedToClass() {
                    this.clearStatus();
                    if (!this.classId || !this.session) {
                        this.setStatus('Session and class are required for bulk assignment.', 'error');
                        return;
                    }

                    if (this.bulkSubjects.length === 0) {
                        this.setStatus('Select at least one subject for class assignment.', 'error');
                        return;
                    }

                    this.savingBulk = true;
                    try {
                        const response = await fetch(config.assignClassUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                Accept: 'application/json',
                                'X-CSRF-TOKEN': config.csrfToken,
                            },
                            body: JSON.stringify({
                                class_id: Number(this.classId),
                                session: this.session,
                                subject_ids: this.normalizeIds(this.bulkSubjects),
                            }),
                        });

                        const result = await response.json();
                        if (!response.ok) {
                            this.setStatus(result.message || 'Bulk assignment failed.', 'error');
                            return;
                        }

                        const studentsCount = Number(result.students_count || 0);
                        const subjectsCount = Number(result.subjects_count || 0);
                        const assignmentsCreated = Number(result.assignments_created || 0);
                        this.setStatus(`Class assignment saved. Students: ${studentsCount}, Subjects: ${subjectsCount}, New assignments: ${assignmentsCreated}.`);

                        await this.loadStudents(true);
                    } catch (error) {
                        this.setStatus('Unexpected error while assigning subjects to class.', 'error');
                    } finally {
                        this.savingBulk = false;
                    }
                },
            };
        }
    </script>
</x-app-layout>
