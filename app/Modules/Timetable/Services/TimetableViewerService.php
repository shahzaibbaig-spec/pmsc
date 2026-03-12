<?php

namespace App\Modules\Timetable\Services;

use App\Models\ClassSection;
use App\Models\Room;
use App\Models\Subject;
use App\Models\SubjectPeriodRule;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\TeacherAvailability;
use App\Models\TimeSlot;
use App\Models\TimetableConstraint;
use App\Models\TimetableEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RuntimeException;

class TimetableViewerService
{
    public function classTimetable(string $session, int $classSectionId): array
    {
        $classSection = ClassSection::query()
            ->with('classRoom:id,name,section')
            ->find($classSectionId);

        if (! $classSection) {
            throw new RuntimeException('Class section not found.');
        }

        $slotHeaders = $this->slotHeaders();
        $dayList = $this->dayList();

        $entries = TimetableEntry::query()
            ->with([
                'subject:id,name,code',
                'teacher:id,user_id,teacher_id,employee_code',
                'teacher.user:id,name',
                'room:id,name,type',
            ])
            ->where('session', $session)
            ->where('class_section_id', $classSectionId)
            ->get();

        $entryMap = $entries->mapWithKeys(function (TimetableEntry $entry): array {
            return [
                $this->entryMapKey($entry->day_of_week, (int) $entry->slot_index) => $entry,
            ];
        });

        $rows = collect($dayList)->map(function (array $dayRow) use ($slotHeaders, $entryMap): array {
            $day = $dayRow['day_of_week'];

            return [
                'day_of_week' => $day,
                'day_label' => $dayRow['day_label'],
                'cells' => collect($slotHeaders)->map(function (array $header) use ($entryMap, $day): array {
                    $key = $this->entryMapKey($day, (int) $header['slot_index']);
                    /** @var TimetableEntry|null $entry */
                    $entry = $entryMap->get($key);

                    return [
                        'day_of_week' => $day,
                        'slot_index' => (int) $header['slot_index'],
                        'entry' => $entry ? $this->formatClassEntry($entry) : null,
                    ];
                })->values()->all(),
            ];
        })->values()->all();

        $subjectRules = SubjectPeriodRule::query()
            ->with('subject:id,name,code')
            ->where('session', $session)
            ->where('class_section_id', $classSectionId)
            ->get();

        $subjects = $subjectRules
            ->map(fn (SubjectPeriodRule $rule): ?Subject => $rule->subject)
            ->filter()
            ->unique('id')
            ->map(fn (Subject $subject): array => [
                'id' => (int) $subject->id,
                'name' => $subject->name,
                'code' => $subject->code,
            ])
            ->values();

        if ($subjects->isEmpty()) {
            $subjects = Subject::query()
                ->orderByDesc('is_default')
                ->orderBy('name')
                ->get(['id', 'name', 'code'])
                ->map(fn (Subject $subject): array => [
                    'id' => (int) $subject->id,
                    'name' => $subject->name,
                    'code' => $subject->code,
                ])
                ->values();
        }

        $assignments = TeacherAssignment::query()
            ->with('teacher.user:id,name')
            ->where('session', $session)
            ->where('class_id', (int) $classSection->class_id)
            ->whereNotNull('subject_id')
            ->get(['id', 'teacher_id', 'class_id', 'subject_id']);

        $teachersBySubject = [];
        foreach ($assignments as $assignment) {
            $subjectId = (int) $assignment->subject_id;
            $teacher = $assignment->teacher;
            $teacherId = (int) $assignment->teacher_id;

            if (! $teacher) {
                continue;
            }

            $teachersBySubject[$subjectId] ??= [];
            $teachersBySubject[$subjectId][$teacherId] = [
                'id' => $teacherId,
                'name' => $teacher->user?->name ?? 'Teacher',
                'teacher_id' => $teacher->teacher_id,
                'employee_code' => $teacher->employee_code,
            ];
        }

        $teachersBySubject = collect($teachersBySubject)
            ->map(fn (array $teachers): array => array_values($teachers))
            ->all();

        $rooms = Room::query()
            ->orderBy('type')
            ->orderBy('name')
            ->get(['id', 'name', 'type'])
            ->map(fn (Room $room): array => [
                'id' => (int) $room->id,
                'name' => $room->name,
                'type' => Str::lower((string) $room->type),
            ])
            ->values()
            ->all();

        return [
            'session' => $session,
            'class_section' => [
                'id' => (int) $classSection->id,
                'class_id' => (int) $classSection->class_id,
                'display_name' => $this->classSectionLabel($classSection),
            ],
            'slot_headers' => $slotHeaders,
            'rows' => $rows,
            'options' => [
                'subjects' => $subjects->all(),
                'teachers_by_subject' => $teachersBySubject,
                'rooms' => $rooms,
            ],
        ];
    }

    public function teacherTimetable(string $session, int $teacherId): array
    {
        $teacher = Teacher::query()
            ->with('user:id,name,email')
            ->find($teacherId);

        if (! $teacher) {
            throw new RuntimeException('Teacher not found.');
        }

        $slotHeaders = $this->slotHeaders();
        $dayList = $this->dayList();

        $entries = TimetableEntry::query()
            ->with([
                'subject:id,name,code',
                'classSection:id,class_id,section_name',
                'classSection.classRoom:id,name,section',
                'room:id,name,type',
            ])
            ->where('session', $session)
            ->where('teacher_id', $teacherId)
            ->get();

        $entryMap = $entries->mapWithKeys(function (TimetableEntry $entry): array {
            return [
                $this->entryMapKey($entry->day_of_week, (int) $entry->slot_index) => $entry,
            ];
        });

        $rows = collect($dayList)->map(function (array $dayRow) use ($slotHeaders, $entryMap): array {
            $day = $dayRow['day_of_week'];

            return [
                'day_of_week' => $day,
                'day_label' => $dayRow['day_label'],
                'cells' => collect($slotHeaders)->map(function (array $header) use ($entryMap, $day): array {
                    $key = $this->entryMapKey($day, (int) $header['slot_index']);
                    /** @var TimetableEntry|null $entry */
                    $entry = $entryMap->get($key);

                    return [
                        'day_of_week' => $day,
                        'slot_index' => (int) $header['slot_index'],
                        'entry' => $entry ? $this->formatTeacherEntry($entry) : null,
                    ];
                })->values()->all(),
            ];
        })->values()->all();

        return [
            'session' => $session,
            'teacher' => [
                'id' => (int) $teacher->id,
                'name' => $teacher->user?->name ?? 'Teacher',
                'teacher_id' => $teacher->teacher_id,
                'employee_code' => $teacher->employee_code,
            ],
            'slot_headers' => $slotHeaders,
            'rows' => $rows,
        ];
    }

    public function updateEntry(array $payload, bool $validateOnly = false): array
    {
        $session = (string) $payload['session'];
        $classSectionId = (int) $payload['class_section_id'];
        $day = (string) $payload['day_of_week'];
        $slotIndex = (int) $payload['slot_index'];
        $subjectId = (int) $payload['subject_id'];
        $teacherId = (int) $payload['teacher_id'];
        $roomId = (int) $payload['room_id'];
        $entryId = isset($payload['entry_id']) ? (int) $payload['entry_id'] : null;

        $classSection = ClassSection::query()->find($classSectionId);
        if (! $classSection) {
            throw new RuntimeException('Class section not found.');
        }

        $subject = Subject::query()->find($subjectId);
        if (! $subject) {
            throw new RuntimeException('Subject not found.');
        }

        $teacher = Teacher::query()->find($teacherId);
        if (! $teacher) {
            throw new RuntimeException('Teacher not found.');
        }

        $room = Room::query()->find($roomId);
        if (! $room) {
            throw new RuntimeException('Room not found.');
        }

        $slotExists = TimeSlot::query()
            ->where('day_of_week', $day)
            ->where('slot_index', $slotIndex)
            ->exists();
        if (! $slotExists) {
            throw new RuntimeException('Selected day/slot does not exist.');
        }

        $existingEntry = null;
        if ($entryId) {
            $existingEntry = TimetableEntry::query()->find($entryId);
            if (! $existingEntry) {
                throw new RuntimeException('Timetable entry not found.');
            }
        }

        $errors = $this->validateEntryConstraints(
            $session,
            $classSection,
            $subject,
            $teacher,
            $room,
            $day,
            $slotIndex,
            $entryId
        );

        if (! empty($errors)) {
            return [
                'valid' => false,
                'conflicts' => $errors,
                'message' => $errors[0]['message'] ?? 'Validation failed.',
            ];
        }

        if ($validateOnly) {
            return [
                'valid' => true,
                'conflicts' => [],
                'message' => 'Valid slot. No hard conflicts found.',
            ];
        }

        $target = TimetableEntry::query()->firstOrNew([
            'session' => $session,
            'class_section_id' => $classSectionId,
            'day_of_week' => $day,
            'slot_index' => $slotIndex,
        ]);

        if ($existingEntry && $existingEntry->id !== $target->id) {
            $target = $existingEntry;
            $target->session = $session;
            $target->class_section_id = $classSectionId;
            $target->day_of_week = $day;
            $target->slot_index = $slotIndex;
        }

        $target->subject_id = $subjectId;
        $target->teacher_id = $teacherId;
        $target->room_id = $roomId;
        $target->save();

        $target->load([
            'subject:id,name,code',
            'teacher:id,user_id,teacher_id,employee_code',
            'teacher.user:id,name',
            'room:id,name,type',
        ]);

        return [
            'valid' => true,
            'conflicts' => [],
            'message' => 'Timetable entry updated successfully.',
            'entry' => $this->formatClassEntry($target),
        ];
    }

    public function exportRows(string $session, ?int $classSectionId = null, ?int $teacherId = null): Collection
    {
        $query = TimetableEntry::query()
            ->with([
                'classSection:id,class_id,section_name',
                'classSection.classRoom:id,name,section',
                'subject:id,name,code',
                'teacher:id,user_id,teacher_id,employee_code',
                'teacher.user:id,name',
                'room:id,name,type',
            ])
            ->where('session', $session);

        if ($classSectionId) {
            $query->where('class_section_id', $classSectionId);
        }

        if ($teacherId) {
            $query->where('teacher_id', $teacherId);
        }

        return $query
            ->orderByRaw($this->dayOrderSql('day_of_week'))
            ->orderBy('slot_index')
            ->get();
    }

    private function validateEntryConstraints(
        string $session,
        ClassSection $classSection,
        Subject $subject,
        Teacher $teacher,
        Room $room,
        string $day,
        int $slotIndex,
        ?int $entryId = null
    ): array {
        $errors = [];

        $classConflict = TimetableEntry::query()
            ->where('session', $session)
            ->where('class_section_id', $classSection->id)
            ->where('day_of_week', $day)
            ->where('slot_index', $slotIndex)
            ->when($entryId, fn (Builder $query) => $query->where('id', '!=', $entryId))
            ->exists();

        if ($classConflict) {
            $errors[] = $this->error('class_conflict', 'Class section already has an entry in this slot.');
        }

        $teacherConflict = TimetableEntry::query()
            ->where('session', $session)
            ->where('teacher_id', $teacher->id)
            ->where('day_of_week', $day)
            ->where('slot_index', $slotIndex)
            ->when($entryId, fn (Builder $query) => $query->where('id', '!=', $entryId))
            ->exists();

        if ($teacherConflict) {
            $errors[] = $this->error('teacher_conflict', 'Teacher is already assigned in this slot.');
        }

        $roomConflict = TimetableEntry::query()
            ->where('session', $session)
            ->where('room_id', $room->id)
            ->where('day_of_week', $day)
            ->where('slot_index', $slotIndex)
            ->when($entryId, fn (Builder $query) => $query->where('id', '!=', $entryId))
            ->exists();

        if ($roomConflict) {
            $errors[] = $this->error('room_conflict', 'Room is already occupied in this slot.');
        }

        $availability = TeacherAvailability::query()
            ->where('teacher_id', $teacher->id)
            ->where('day_of_week', $day)
            ->where('slot_index', $slotIndex)
            ->value('is_available');

        if ($availability !== null && ! (bool) $availability) {
            $errors[] = $this->error('teacher_unavailable', 'Teacher is marked unavailable for this slot.');
        }

        $assignmentExists = TeacherAssignment::query()
            ->where('session', $session)
            ->where('class_id', $classSection->class_id)
            ->where('subject_id', $subject->id)
            ->where('teacher_id', $teacher->id)
            ->exists();

        if (! $assignmentExists) {
            $errors[] = $this->error('assignment_missing', 'Teacher is not assigned to this class and subject for the selected session.');
        }

        $requiredRoomType = $this->requiredRoomType((string) $subject->name);
        if ($requiredRoomType && Str::lower((string) $room->type) !== $requiredRoomType) {
            $errors[] = $this->error('room_type_mismatch', 'Selected subject requires a '.$requiredRoomType.' room.');
        }

        $constraint = TimetableConstraint::query()->where('session', $session)->first();
        if ($constraint) {
            $teacherDayLoad = TimetableEntry::query()
                ->where('session', $session)
                ->where('teacher_id', $teacher->id)
                ->where('day_of_week', $day)
                ->when($entryId, fn (Builder $query) => $query->where('id', '!=', $entryId))
                ->count();

            if ($teacherDayLoad >= (int) $constraint->max_periods_per_day_teacher) {
                $errors[] = $this->error('teacher_day_limit', 'Teacher daily limit exceeded.');
            }

            $teacherWeekLoad = TimetableEntry::query()
                ->where('session', $session)
                ->where('teacher_id', $teacher->id)
                ->when($entryId, fn (Builder $query) => $query->where('id', '!=', $entryId))
                ->count();

            if ($teacherWeekLoad >= (int) $constraint->max_periods_per_week_teacher) {
                $errors[] = $this->error('teacher_week_limit', 'Teacher weekly limit exceeded.');
            }

            $classDayLoad = TimetableEntry::query()
                ->where('session', $session)
                ->where('class_section_id', $classSection->id)
                ->where('day_of_week', $day)
                ->when($entryId, fn (Builder $query) => $query->where('id', '!=', $entryId))
                ->count();

            if ($classDayLoad >= (int) $constraint->max_periods_per_day_class) {
                $errors[] = $this->error('class_day_limit', 'Class daily period limit exceeded.');
            }
        }

        return $errors;
    }

    private function slotHeaders(): array
    {
        return TimeSlot::query()
            ->select('slot_index')
            ->selectRaw('MIN(start_time) as start_time')
            ->selectRaw('MAX(end_time) as end_time')
            ->groupBy('slot_index')
            ->orderBy('slot_index')
            ->get()
            ->map(fn (TimeSlot $slot): array => [
                'slot_index' => (int) $slot->slot_index,
                'start_time' => substr((string) $slot->start_time, 0, 5),
                'end_time' => substr((string) $slot->end_time, 0, 5),
            ])
            ->values()
            ->all();
    }

    private function dayList(): array
    {
        $days = TimeSlot::query()
            ->select('day_of_week')
            ->distinct()
            ->orderByRaw($this->dayOrderSql('day_of_week'))
            ->pluck('day_of_week')
            ->values()
            ->all();

        if (empty($days)) {
            $days = config('timetable.days', ['mon', 'tue', 'wed', 'thu', 'fri', 'sat']);
        }

        return collect($days)->map(fn (string $day): array => [
            'day_of_week' => $day,
            'day_label' => strtoupper($day),
        ])->values()->all();
    }

    private function formatClassEntry(TimetableEntry $entry): array
    {
        return [
            'id' => (int) $entry->id,
            'subject_id' => (int) $entry->subject_id,
            'subject_name' => $entry->subject?->name ?? 'Subject',
            'teacher_id' => (int) $entry->teacher_id,
            'teacher_name' => $entry->teacher?->user?->name ?? 'Teacher',
            'teacher_code' => $entry->teacher?->teacher_id ?? null,
            'room_id' => (int) $entry->room_id,
            'room_name' => $entry->room?->name ?? 'Room',
            'room_type' => Str::lower((string) ($entry->room?->type ?? '')),
        ];
    }

    private function formatTeacherEntry(TimetableEntry $entry): array
    {
        $label = $this->classSectionLabel($entry->classSection);

        return [
            'id' => (int) $entry->id,
            'subject_name' => $entry->subject?->name ?? 'Subject',
            'class_section' => $label,
            'room_name' => $entry->room?->name ?? 'Room',
            'room_type' => Str::lower((string) ($entry->room?->type ?? '')),
        ];
    }

    private function classSectionLabel(?ClassSection $classSection): string
    {
        if (! $classSection) {
            return 'Class Section';
        }

        $className = trim(($classSection->classRoom?->name ?? 'Class').' '.($classSection->classRoom?->section ?? ''));

        return trim($className.' - '.($classSection->section_name ?? 'Section'));
    }

    private function requiredRoomType(string $subjectName): ?string
    {
        $name = Str::lower($subjectName);

        if (Str::contains($name, ['physics', 'chemistry', 'biology', 'computer', 'lab', 'practical'])) {
            return 'lab';
        }

        return null;
    }

    private function error(string $code, string $message): array
    {
        return [
            'code' => $code,
            'message' => $message,
        ];
    }

    private function entryMapKey(string $day, int $slot): string
    {
        return $day.'|'.$slot;
    }

    private function dayOrderSql(string $column): string
    {
        return "CASE {$column}
            WHEN 'mon' THEN 1
            WHEN 'tue' THEN 2
            WHEN 'wed' THEN 3
            WHEN 'thu' THEN 4
            WHEN 'fri' THEN 5
            WHEN 'sat' THEN 6
            ELSE 99 END";
    }
}
