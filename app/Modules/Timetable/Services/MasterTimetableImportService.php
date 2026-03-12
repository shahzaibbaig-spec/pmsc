<?php

namespace App\Modules\Timetable\Services;

use App\Models\ClassSection;
use App\Models\Room;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\SubjectPeriodRule;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\TeacherSubjectAssignment;
use App\Models\TimetableEntry;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Permission\Models\Role;

class MasterTimetableImportService
{
    private const REQUIRED_SHEETS = [
        'classes',
        'teachers',
        'subjects',
        'class_teachers',
        'teacher_subject_assignments',
        'timetable',
    ];

    public function __construct(
        private readonly XlsxWorkbookReader $workbookReader
    ) {
    }

    /**
     * @return array{
     *     session:string,
     *     classes_imported:int,
     *     teachers_imported:int,
     *     subjects_imported:int,
     *     assignments_imported:int,
     *     timetable_rows_imported:int,
     *     conflicts_found:int,
     *     errors:array<int, array{sheet:string,row:int,message:string}>
     * }
     */
    public function import(UploadedFile $file, ?string $session = null): array
    {
        $resolvedSession = $session && trim($session) !== ''
            ? trim($session)
            : $this->currentSession();

        $workbook = $this->workbookReader->read((string) $file->getRealPath());
        $this->ensureRequiredSheetsPresent($workbook);

        return DB::transaction(function () use ($workbook, $resolvedSession): array {
            $summary = [
                'session' => $resolvedSession,
                'classes_imported' => 0,
                'teachers_imported' => 0,
                'subjects_imported' => 0,
                'assignments_imported' => 0,
                'timetable_rows_imported' => 0,
                'conflicts_found' => 0,
                'errors' => [],
            ];

            $teacherRole = Role::query()
                ->where('name', 'Teacher')
                ->where('guard_name', 'web')
                ->first();

            $classLookup = $this->buildClassLookup();
            $teacherLookup = $this->buildTeacherLookup();
            $subjectLookup = $this->buildSubjectLookup();
            $classSectionLookup = $this->buildClassSectionLookup();

            $teacherSequence = (int) Teacher::query()->max('id') + 1;

            $this->importClassesSheet(
                $workbook['classes'],
                $classLookup,
                $summary
            );

            $this->importTeachersSheet(
                $workbook['teachers'],
                $teacherLookup,
                $teacherRole,
                $teacherSequence,
                $summary
            );

            $this->importSubjectsSheet(
                $workbook['subjects'],
                $subjectLookup,
                $summary
            );

            $this->importClassTeachersSheet(
                $workbook['class_teachers'],
                $classLookup,
                $teacherLookup,
                $resolvedSession,
                $summary
            );

            $this->importTeacherSubjectAssignmentsSheet(
                $workbook['teacher_subject_assignments'],
                $classLookup,
                $teacherLookup,
                $subjectLookup,
                $classSectionLookup,
                $resolvedSession,
                $summary
            );

            $this->importTimetableSheet(
                $workbook['timetable'],
                $classLookup,
                $teacherLookup,
                $subjectLookup,
                $classSectionLookup,
                $resolvedSession,
                $summary
            );

            return $summary;
        });
    }

    /**
     * @param array<string, array<int, array<string, string>>> $workbook
     */
    private function ensureRequiredSheetsPresent(array $workbook): void
    {
        $missing = [];
        foreach (self::REQUIRED_SHEETS as $sheet) {
            if (! array_key_exists($sheet, $workbook)) {
                $missing[] = $sheet;
            }
        }

        if (! empty($missing)) {
            throw new RuntimeException('Missing required sheets: '.implode(', ', $missing));
        }
    }

    /**
     * @return array<string, SchoolClass>
     */
    private function buildClassLookup(): array
    {
        $lookup = [];
        $classes = SchoolClass::query()->get(['id', 'name', 'section', 'class_teacher_id']);

        foreach ($classes as $classRoom) {
            $lookup[$this->classLookupKey((string) $classRoom->name, $classRoom->section)] = $classRoom;
        }

        return $lookup;
    }

    /**
     * @return array<string, Teacher>
     */
    private function buildTeacherLookup(): array
    {
        $lookup = [];
        $teachers = Teacher::query()
            ->with('user:id,name')
            ->get(['id', 'user_id', 'teacher_id', 'designation', 'employee_code']);

        foreach ($teachers as $teacher) {
            $name = trim((string) ($teacher->user?->name ?? ''));
            if ($name === '') {
                continue;
            }

            $lookup[$this->normalizeKey($name)] = $teacher;
        }

        return $lookup;
    }

    /**
     * @return array<string, Subject>
     */
    private function buildSubjectLookup(): array
    {
        $lookup = [];
        $subjects = Subject::query()->get(['id', 'name', 'code', 'status', 'is_default']);

        foreach ($subjects as $subject) {
            $lookup[$this->normalizeKey((string) $subject->name)] = $subject;
        }

        return $lookup;
    }

    /**
     * @return array<string, ClassSection>
     */
    private function buildClassSectionLookup(): array
    {
        $lookup = [];
        $sections = ClassSection::query()->get(['id', 'class_id', 'section_name']);

        foreach ($sections as $section) {
            $lookup[$this->classSectionLookupKey((int) $section->class_id, (string) $section->section_name)] = $section;
        }

        return $lookup;
    }

    /**
     * @param array<int, array<string, string>> $rows
     * @param array<string, SchoolClass> $classLookup
     * @param array<string, mixed> $summary
     */
    private function importClassesSheet(array $rows, array &$classLookup, array &$summary): void
    {
        foreach ($rows as $index => $row) {
            $classNameRaw = $this->rowValue($row, ['class_name', 'class']);
            if ($classNameRaw === '') {
                continue;
            }

            [$name, $section] = $this->splitClassLabel($classNameRaw);
            $lookupKey = $this->classLookupKey($name, $section);

            if (isset($classLookup[$lookupKey])) {
                continue;
            }

            $classRoom = SchoolClass::query()->create([
                'name' => $name,
                'section' => $section,
                'status' => 'active',
            ]);

            $classLookup[$lookupKey] = $classRoom;
            $summary['classes_imported']++;
        }
    }

    /**
     * @param array<int, array<string, string>> $rows
     * @param array<string, Teacher> $teacherLookup
     * @param array<string, mixed> $summary
     */
    private function importTeachersSheet(
        array $rows,
        array &$teacherLookup,
        ?Role $teacherRole,
        int &$teacherSequence,
        array &$summary
    ): void {
        $defaultPassword = 'newuser123';

        foreach ($rows as $row) {
            $teacherName = $this->rowValue($row, ['teacher_name', 'teacher']);
            if ($teacherName === '') {
                continue;
            }

            $lookupKey = $this->normalizeKey($teacherName);
            $teacher = $teacherLookup[$lookupKey] ?? null;
            $existingUser = $teacher?->user;

            if (! $existingUser) {
                $existingUser = User::query()
                    ->whereRaw('LOWER(name) = ?', [mb_strtolower($teacherName)])
                    ->first();
            }

            $desiredEmail = $this->generateUniqueEmail(
                $teacherName,
                $existingUser ? (int) $existingUser->id : null
            );

            if (! $existingUser) {
                $existingUser = User::query()->create([
                    'name' => $teacherName,
                    'email' => $desiredEmail,
                    'password' => Hash::make($defaultPassword),
                    'status' => 'active',
                    'email_verified_at' => now(),
                ]);
            } else {
                $existingUser->fill([
                    'email' => $desiredEmail,
                    'password' => Hash::make($defaultPassword),
                    'status' => 'active',
                ]);
                $existingUser->save();
            }

            if ($teacherRole && ! $existingUser->hasRole('Teacher')) {
                $existingUser->assignRole($teacherRole);
            }

            if (! $teacher) {
                $teacher = Teacher::query()->where('user_id', (int) $existingUser->id)->first();
            }

            if (! $teacher) {
                $teacher = Teacher::query()->create([
                    'teacher_id' => 'T-'.str_pad((string) $teacherSequence, 4, '0', STR_PAD_LEFT),
                    'user_id' => (int) $existingUser->id,
                    'designation' => 'Teacher',
                    'employee_code' => null,
                ]);
                $teacherSequence++;
                $summary['teachers_imported']++;
            }

            $teacherLookup[$lookupKey] = $teacher;
        }
    }

    /**
     * @param array<int, array<string, string>> $rows
     * @param array<string, Subject> $subjectLookup
     * @param array<string, mixed> $summary
     */
    private function importSubjectsSheet(array $rows, array &$subjectLookup, array &$summary): void
    {
        foreach ($rows as $row) {
            $subjectName = $this->rowValue($row, ['subject_name']);
            if ($subjectName === '') {
                continue;
            }

            $shortCode = $this->rowValue($row, ['short', 'code']);
            $lookupKey = $this->normalizeKey($subjectName);

            $subject = $subjectLookup[$lookupKey] ?? null;
            if ($subject) {
                if ($shortCode !== '' && trim((string) $subject->code) !== $shortCode) {
                    $subject->code = $shortCode;
                    $subject->save();
                }
                continue;
            }

            $subject = Subject::query()->create([
                'name' => $subjectName,
                'code' => $shortCode !== '' ? $shortCode : null,
                'is_default' => false,
                'status' => 'active',
            ]);

            $subjectLookup[$lookupKey] = $subject;
            $summary['subjects_imported']++;
        }
    }

    /**
     * @param array<int, array<string, string>> $rows
     * @param array<string, SchoolClass> $classLookup
     * @param array<string, Teacher> $teacherLookup
     * @param array<string, mixed> $summary
     */
    private function importClassTeachersSheet(
        array $rows,
        array $classLookup,
        array $teacherLookup,
        string $session,
        array &$summary
    ): void {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $className = $this->rowValue($row, ['class']);
            $teacherName = $this->rowValue($row, ['class_teacher']);

            if ($className === '' && $teacherName === '') {
                continue;
            }

            $classRoom = $this->findClassFromLookup($classLookup, $className);
            if (! $classRoom) {
                $this->addError($summary, 'class_teachers', $rowNumber, 'Unknown class: '.$className);
                continue;
            }

            $teacher = $this->resolveTeacherFromLookup($teacherLookup, $teacherName);
            if (! $teacher) {
                $this->addError($summary, 'class_teachers', $rowNumber, 'Unknown teacher: '.$teacherName);
                continue;
            }

            if ((int) ($classRoom->class_teacher_id ?? 0) !== (int) $teacher->id) {
                $classRoom->class_teacher_id = (int) $teacher->id;
                $classRoom->save();
            }

            TeacherAssignment::query()
                ->where('class_id', (int) $classRoom->id)
                ->where('session', $session)
                ->where('is_class_teacher', true)
                ->delete();

            TeacherAssignment::query()->firstOrCreate([
                'teacher_id' => (int) $teacher->id,
                'class_id' => (int) $classRoom->id,
                'subject_id' => null,
                'is_class_teacher' => true,
                'session' => $session,
            ]);
        }
    }

    /**
     * @param array<int, array<string, string>> $rows
     * @param array<string, SchoolClass> $classLookup
     * @param array<string, Teacher> $teacherLookup
     * @param array<string, Subject> $subjectLookup
     * @param array<string, ClassSection> $classSectionLookup
     * @param array<string, mixed> $summary
     */
    private function importTeacherSubjectAssignmentsSheet(
        array $rows,
        array $classLookup,
        array $teacherLookup,
        array $subjectLookup,
        array &$classSectionLookup,
        string $session,
        array &$summary
    ): void {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $className = $this->rowValue($row, ['class']);
            $subjectName = $this->rowValue($row, ['subject']);
            $teacherName = $this->rowValue($row, ['teacher']);
            $group = $this->sanitizeGroup($this->rowValue($row, ['group']));
            $lessonsPerWeek = (int) $this->rowValue($row, ['lessons_per_week']);

            if (
                $className === '' &&
                $subjectName === '' &&
                $teacherName === ''
            ) {
                continue;
            }

            $classRoom = $this->findClassFromLookup($classLookup, $className);
            if (! $classRoom) {
                $this->addError($summary, 'teacher_subject_assignments', $rowNumber, 'Unknown class: '.$className);
                continue;
            }

            $subject = $subjectLookup[$this->normalizeKey($subjectName)] ?? null;
            if (! $subject) {
                $this->addError($summary, 'teacher_subject_assignments', $rowNumber, 'Unknown subject: '.$subjectName);
                continue;
            }

            $teacher = $this->resolveTeacherFromLookup($teacherLookup, $teacherName);
            if (! $teacher) {
                $this->addError($summary, 'teacher_subject_assignments', $rowNumber, 'Unknown teacher: '.$teacherName);
                continue;
            }

            if ($lessonsPerWeek <= 0) {
                $this->addError($summary, 'teacher_subject_assignments', $rowNumber, 'lessons_per_week must be greater than 0.');
                continue;
            }

            $classSection = $this->getOrCreateClassSection($classRoom, $group, $classSectionLookup);

            TeacherSubjectAssignment::query()->updateOrCreate(
                [
                    'session' => $session,
                    'class_id' => (int) $classRoom->id,
                    'subject_id' => (int) $subject->id,
                    'teacher_id' => (int) $teacher->id,
                    'group_name' => $group,
                ],
                [
                    'class_section_id' => (int) $classSection->id,
                    'lessons_per_week' => $lessonsPerWeek,
                ]
            );

            TeacherAssignment::query()->firstOrCreate([
                'teacher_id' => (int) $teacher->id,
                'class_id' => (int) $classRoom->id,
                'subject_id' => (int) $subject->id,
                'is_class_teacher' => false,
                'session' => $session,
            ]);

            SubjectPeriodRule::query()->updateOrCreate(
                [
                    'session' => $session,
                    'class_section_id' => (int) $classSection->id,
                    'subject_id' => (int) $subject->id,
                ],
                [
                    'periods_per_week' => $lessonsPerWeek,
                ]
            );

            DB::table('class_subject')->insertOrIgnore([
                'class_id' => (int) $classRoom->id,
                'subject_id' => (int) $subject->id,
            ]);

            $summary['assignments_imported']++;
        }
    }

    /**
     * @param array<int, array<string, string>> $rows
     * @param array<string, SchoolClass> $classLookup
     * @param array<string, Teacher> $teacherLookup
     * @param array<string, Subject> $subjectLookup
     * @param array<string, ClassSection> $classSectionLookup
     * @param array<string, mixed> $summary
     */
    private function importTimetableSheet(
        array $rows,
        array $classLookup,
        array $teacherLookup,
        array $subjectLookup,
        array &$classSectionLookup,
        string $session,
        array &$summary
    ): void {
        $existingEntries = TimetableEntry::query()
            ->where('session', $session)
            ->get(['class_section_id', 'teacher_id', 'day_of_week', 'slot_index', 'room_id']);

        $classSlots = [];
        $teacherSlots = [];
        $roomSlots = [];
        foreach ($existingEntries as $entry) {
            $classSlots[$this->classSlotKey((int) $entry->class_section_id, (string) $entry->day_of_week, (int) $entry->slot_index)] = true;
            $teacherSlots[$this->teacherSlotKey((int) $entry->teacher_id, (string) $entry->day_of_week, (int) $entry->slot_index)] = true;
            $roomSlots[$this->roomSlotKey((int) $entry->room_id, (string) $entry->day_of_week, (int) $entry->slot_index)] = true;
        }

        $roomsByType = [
            'classroom' => [],
            'lab' => [],
        ];
        $allRooms = Room::query()->orderBy('name')->get(['id', 'name', 'type']);
        foreach ($allRooms as $room) {
            $type = Str::lower((string) $room->type) === 'lab' ? 'lab' : 'classroom';
            $roomsByType[$type][] = $room;
        }

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            $className = $this->rowValue($row, ['class']);
            $day = $this->normalizeDay($this->rowValue($row, ['day']));
            $period = (int) $this->rowValue($row, ['period']);
            $subjectName = $this->rowValue($row, ['subject']);
            $teacherName = $this->rowValue($row, ['teacher']);
            $group = $this->sanitizeGroup($this->rowValue($row, ['group']));

            if (
                $className === '' &&
                $subjectName === '' &&
                $teacherName === '' &&
                $period === 0
            ) {
                continue;
            }

            $classRoom = $this->findClassFromLookup($classLookup, $className);
            if (! $classRoom) {
                $this->addError($summary, 'timetable', $rowNumber, 'Unknown class: '.$className);
                continue;
            }

            if ($day === null) {
                $this->addError($summary, 'timetable', $rowNumber, 'Invalid day: '.$this->rowValue($row, ['day']));
                continue;
            }

            if ($period < 0) {
                $this->addError($summary, 'timetable', $rowNumber, 'Invalid period value.');
                continue;
            }

            $subject = $subjectLookup[$this->normalizeKey($subjectName)] ?? null;
            if (! $subject) {
                $this->addError($summary, 'timetable', $rowNumber, 'Unknown subject: '.$subjectName);
                continue;
            }

            $teacher = $this->resolveTeacherFromLookup($teacherLookup, $teacherName);
            if (! $teacher) {
                $this->addError($summary, 'timetable', $rowNumber, 'Unknown teacher: '.$teacherName);
                continue;
            }

            $classSection = $this->getOrCreateClassSection($classRoom, $group, $classSectionLookup);

            $classSlotKey = $this->classSlotKey((int) $classSection->id, $day, $period);
            if (isset($classSlots[$classSlotKey])) {
                $this->addConflict(
                    $summary,
                    'timetable',
                    $rowNumber,
                    'Class/group already has an entry for '.$day.' period '.$period.'.'
                );
                continue;
            }

            $teacherSlotKey = $this->teacherSlotKey((int) $teacher->id, $day, $period);
            if (isset($teacherSlots[$teacherSlotKey])) {
                $this->addConflict(
                    $summary,
                    'timetable',
                    $rowNumber,
                    'Teacher already assigned in '.$day.' period '.$period.'.'
                );
                continue;
            }

            $requiredRoomType = $this->requiredRoomType((string) $subject->name);
            $room = $this->resolveRoomForSlot($roomsByType, $roomSlots, $day, $period, $requiredRoomType);

            TimetableEntry::query()->create([
                'session' => $session,
                'class_section_id' => (int) $classSection->id,
                'day_of_week' => $day,
                'slot_index' => $period,
                'subject_id' => (int) $subject->id,
                'teacher_id' => (int) $teacher->id,
                'room_id' => (int) $room->id,
            ]);

            TeacherAssignment::query()->firstOrCreate([
                'teacher_id' => (int) $teacher->id,
                'class_id' => (int) $classRoom->id,
                'subject_id' => (int) $subject->id,
                'is_class_teacher' => false,
                'session' => $session,
            ]);

            $classSlots[$classSlotKey] = true;
            $teacherSlots[$teacherSlotKey] = true;
            $roomSlots[$this->roomSlotKey((int) $room->id, $day, $period)] = true;
            $summary['timetable_rows_imported']++;
        }
    }

    /**
     * @param array<string, Room[]> $roomsByType
     * @param array<string, bool> $roomSlots
     */
    private function resolveRoomForSlot(
        array &$roomsByType,
        array $roomSlots,
        string $day,
        int $period,
        ?string $requiredType
    ): Room {
        $poolOrder = $requiredType === 'lab'
            ? ['lab', 'classroom']
            : ['classroom', 'lab'];

        foreach ($poolOrder as $type) {
            foreach ($roomsByType[$type] ?? [] as $room) {
                $key = $this->roomSlotKey((int) $room->id, $day, $period);
                if (! isset($roomSlots[$key])) {
                    return $room;
                }
            }
        }

        $type = $requiredType ?? 'classroom';
        $room = Room::query()->create([
            'name' => $this->nextAutoRoomName($type),
            'type' => $type,
            'capacity' => null,
        ]);

        $roomsByType[$type][] = $room;

        return $room;
    }

    private function nextAutoRoomName(string $type): string
    {
        $prefix = $type === 'lab' ? 'Auto Lab ' : 'Auto Classroom ';
        $counter = 1;

        do {
            $name = $prefix.$counter;
            $exists = Room::query()->where('name', $name)->exists();
            $counter++;
        } while ($exists);

        return $name;
    }

    /**
     * @param array<string, SchoolClass> $classLookup
     */
    private function findClassFromLookup(array $classLookup, string $rawClassName): ?SchoolClass
    {
        if ($rawClassName === '') {
            return null;
        }

        [$name, $section] = $this->splitClassLabel($rawClassName);
        $key = $this->classLookupKey($name, $section);

        if (isset($classLookup[$key])) {
            return $classLookup[$key];
        }

        $fallbackKey = $this->classLookupKey($rawClassName, null);

        return $classLookup[$fallbackKey] ?? null;
    }

    /**
     * @param array<string, ClassSection> $classSectionLookup
     */
    private function getOrCreateClassSection(
        SchoolClass $classRoom,
        string $group,
        array &$classSectionLookup
    ): ClassSection {
        $key = $this->classSectionLookupKey((int) $classRoom->id, $group);

        if (isset($classSectionLookup[$key])) {
            return $classSectionLookup[$key];
        }

        $section = ClassSection::query()->firstOrCreate([
            'class_id' => (int) $classRoom->id,
            'section_name' => $group,
        ]);

        $classSectionLookup[$key] = $section;

        return $section;
    }

    private function classLookupKey(string $name, ?string $section): string
    {
        return $this->normalizeKey(trim($name.' '.($section ?? '')));
    }

    private function classSectionLookupKey(int $classId, string $group): string
    {
        return $classId.'|'.$this->normalizeKey($group);
    }

    private function normalizeKey(string $value): string
    {
        $value = preg_replace('/\s+/', ' ', trim($value)) ?? '';

        return mb_strtolower($value);
    }

    /**
     * @return array{0:string,1:?string}
     */
    private function splitClassLabel(string $label): array
    {
        $label = preg_replace('/\s+/', ' ', trim($label)) ?? '';

        if (preg_match('/^(.*)\s+([A-Za-z])$/', $label, $matches)) {
            $name = trim($matches[1]);
            $section = strtoupper(trim($matches[2]));

            if ($name !== '') {
                return [$name, $section];
            }
        }

        return [$label, null];
    }

    private function sanitizeGroup(string $value): string
    {
        $value = strtoupper(trim($value));
        if ($value === '') {
            $value = 'A';
        }

        return mb_substr($value, 0, 10);
    }

    private function normalizeDay(string $value): ?string
    {
        $value = $this->normalizeKey($value);
        if ($value === '') {
            return null;
        }

        $map = [
            'monday' => 'mon',
            'mon' => 'mon',
            'tuesday' => 'tue',
            'tue' => 'tue',
            'wednesday' => 'wed',
            'wed' => 'wed',
            'thursday' => 'thu',
            'thu' => 'thu',
            'friday' => 'fri',
            'fri' => 'fri',
            'saturday' => 'sat',
            'sat' => 'sat',
        ];

        return $map[$value] ?? null;
    }

    /**
     * @param array<string, Teacher> $teacherLookup
     */
    private function resolveTeacherFromLookup(array $teacherLookup, string $rawTeacherName): ?Teacher
    {
        $normalized = $this->normalizeKey($rawTeacherName);
        if ($normalized !== '' && isset($teacherLookup[$normalized])) {
            return $teacherLookup[$normalized];
        }

        // Some rows contain multiple teacher names in one cell.
        $parts = preg_split('/\s*(?:,|\/|&|\+|\band\b)\s*/i', $rawTeacherName) ?: [];

        foreach ($parts as $part) {
            $candidate = $this->normalizeKey((string) $part);
            if ($candidate === '') {
                continue;
            }

            if (isset($teacherLookup[$candidate])) {
                return $teacherLookup[$candidate];
            }
        }

        return null;
    }

    private function requiredRoomType(string $subjectName): ?string
    {
        $name = Str::lower($subjectName);

        if (Str::contains($name, ['physics', 'chemistry', 'biology', 'computer', 'lab', 'practical'])) {
            return 'lab';
        }

        return null;
    }

    /**
     * @param array<string, mixed> $summary
     */
    private function addError(array &$summary, string $sheet, int $row, string $message): void
    {
        $summary['errors'][] = [
            'sheet' => $sheet,
            'row' => $row,
            'message' => $message,
        ];
    }

    /**
     * @param array<string, mixed> $summary
     */
    private function addConflict(array &$summary, string $sheet, int $row, string $message): void
    {
        $summary['conflicts_found']++;
        $this->addError($summary, $sheet, $row, $message);
    }

    /**
     * @param array<string, string> $row
     * @param array<int, string> $keys
     */
    private function rowValue(array $row, array $keys): string
    {
        foreach ($keys as $key) {
            $value = trim((string) ($row[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function generateUniqueEmail(string $name, ?int $ignoreUserId = null): string
    {
        $base = Str::slug($name);
        if ($base === '') {
            $base = 'teacher';
        }

        $counter = 1;
        do {
            $email = $base.($counter > 1 ? $counter : '').'@kort.edu.pk';
            $exists = User::query()
                ->where('email', $email)
                ->when($ignoreUserId !== null, fn ($query) => $query->whereKeyNot($ignoreUserId))
                ->exists();
            $counter++;
        } while ($exists);

        return $email;
    }

    private function currentSession(): string
    {
        $now = now();
        $startYear = $now->month >= 7 ? $now->year : ($now->year - 1);

        return $startYear.'-'.($startYear + 1);
    }

    private function classSlotKey(int $classSectionId, string $day, int $period): string
    {
        return $classSectionId.'|'.$day.'|'.$period;
    }

    private function teacherSlotKey(int $teacherId, string $day, int $period): string
    {
        return $teacherId.'|'.$day.'|'.$period;
    }

    private function roomSlotKey(int $roomId, string $day, int $period): string
    {
        return $roomId.'|'.$day.'|'.$period;
    }
}
