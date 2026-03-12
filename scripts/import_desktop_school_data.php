<?php

declare(strict_types=1);

use App\Models\ClassSection;
use App\Models\Room;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\TeacherSubjectAssignment;
use App\Models\TimeSlot;
use App\Models\TimetableEntry;
use App\Models\User;
use App\Modules\Students\Services\StudentImportService;
use App\Modules\Timetable\Services\XlsxWorkbookReader;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(ConsoleKernel::class)->bootstrap();

$teacherFile = 'C:\\Users\\Shahzaib\\Desktop\\teacher detail..xlsx';
$studentFile = 'C:\\Users\\Shahzaib\\Desktop\\challan Detail..xlsx';
$timetableFile = 'C:\\Users\\Shahzaib\\Desktop\\NEW TIME TABLE..docx';

$summary = [
    'teachers_created' => 0,
    'students_created' => 0,
    'students_updated' => 0,
    'students_skipped' => 0,
    'classes_created' => 0,
    'subjects_created' => 0,
    'teacher_assignments_created' => 0,
    'teacher_subject_assignments_upserted' => 0,
    'class_sections_created' => 0,
    'timetable_entries_upserted' => 0,
    'timeslots_upserted' => 0,
    'rooms_created' => 0,
    'warnings' => [],
];

if (! is_file($teacherFile) || ! is_file($studentFile) || ! is_file($timetableFile)) {
    fwrite(STDERR, "One or more import files are missing on Desktop.\n");
    exit(1);
}

$teacherRole = Role::query()->where('name', 'Teacher')->where('guard_name', 'web')->first();
if (! $teacherRole) {
    fwrite(STDERR, "Teacher role not found. Run RolePermissionSeeder first.\n");
    exit(1);
}

$session = currentSession();
$days = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat'];
$teacherAliasMap = [];
$assignmentCounter = [];

DB::transaction(function () use (
    &$summary,
    $teacherFile,
    $teacherRole,
    $session,
    &$teacherAliasMap,
    &$assignmentCounter
): void {
    $xlsxReader = app(XlsxWorkbookReader::class);
    $teacherWorkbook = $xlsxReader->read($teacherFile);
    $teacherRows = reset($teacherWorkbook);
    if (! is_array($teacherRows)) {
        $summary['warnings'][] = 'Teacher workbook has no readable rows.';
        return;
    }

    foreach ($teacherRows as $rowIndex => $row) {
        $teacherName = sanitizeText((string) ($row['teacher_name'] ?? ''));
        if ($teacherName === '') {
            continue;
        }

        $teacher = resolveOrCreateTeacher($teacherName, $teacherRole, $summary);
        mapTeacherAliases($teacher->user?->name ?? $teacherName, $teacher->id, $teacherAliasMap);

        foreach ($row as $key => $value) {
            if ($key === 'teacher_name' || str_starts_with($key, 'column_') === false) {
                continue;
            }

            $token = sanitizeText((string) $value);
            if ($token === '') {
                continue;
            }

            $parsed = parseTeacherToken($token);

            if ($parsed['class_label'] === null) {
                $summary['warnings'][] = "Teacher sheet row ".($rowIndex + 2).": could not map class for `{$token}`.";
                continue;
            }

            [$className, $classSection] = normalizeClassLabel($parsed['class_label']);
            $classRoom = resolveOrCreateClass($className, $classSection, $summary);
            $classSectionModel = resolveOrCreateClassSection($classRoom, $classSection, $summary);

            if ($parsed['subject_name'] === null) {
                $already = TeacherAssignment::query()
                    ->where('teacher_id', (int) $teacher->id)
                    ->where('class_id', (int) $classRoom->id)
                    ->where('session', $session)
                    ->where('is_class_teacher', true)
                    ->exists();

                if (! $already) {
                    TeacherAssignment::query()->create([
                        'teacher_id' => (int) $teacher->id,
                        'class_id' => (int) $classRoom->id,
                        'subject_id' => null,
                        'is_class_teacher' => true,
                        'session' => $session,
                    ]);
                    $summary['teacher_assignments_created']++;
                }

                if ((int) ($classRoom->class_teacher_id ?? 0) !== (int) $teacher->id) {
                    $classRoom->class_teacher_id = (int) $teacher->id;
                    $classRoom->save();
                }

                continue;
            }

            $subject = resolveOrCreateSubject($parsed['subject_name'], $summary);
            DB::table('class_subject')->insertOrIgnore([
                'class_id' => (int) $classRoom->id,
                'subject_id' => (int) $subject->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $assignmentExists = TeacherAssignment::query()
                ->where('teacher_id', (int) $teacher->id)
                ->where('class_id', (int) $classRoom->id)
                ->where('subject_id', (int) $subject->id)
                ->where('session', $session)
                ->where('is_class_teacher', false)
                ->exists();
            if (! $assignmentExists) {
                TeacherAssignment::query()->create([
                    'teacher_id' => (int) $teacher->id,
                    'class_id' => (int) $classRoom->id,
                    'subject_id' => (int) $subject->id,
                    'is_class_teacher' => false,
                    'session' => $session,
                ]);
                $summary['teacher_assignments_created']++;
            }

            $group = sectionGroupName($classSectionModel);
            $counterKey = assignmentCounterKey($session, (int) $classRoom->id, (int) $subject->id, (int) $teacher->id, $group);
            $assignmentCounter[$counterKey] = max(1, (int) ($assignmentCounter[$counterKey] ?? 0));

            TeacherSubjectAssignment::query()->updateOrCreate(
                [
                    'session' => $session,
                    'class_id' => (int) $classRoom->id,
                    'subject_id' => (int) $subject->id,
                    'teacher_id' => (int) $teacher->id,
                    'group_name' => $group,
                ],
                [
                    'class_section_id' => (int) $classSectionModel->id,
                    'lessons_per_week' => (int) $assignmentCounter[$counterKey],
                ]
            );
            $summary['teacher_subject_assignments_upserted']++;
        }
    }
});

$studentSummary = app(StudentImportService::class)->importFromPath(
    $studentFile,
    true,
    basename($studentFile)
);
$summary['students_created'] = (int) ($studentSummary['created'] ?? 0);
$summary['students_updated'] = (int) ($studentSummary['updated'] ?? 0);
$summary['students_skipped'] = (int) ($studentSummary['skipped'] ?? 0);

$tableRows = readDocxTableRows($timetableFile);
if (count($tableRows) >= 3) {
    $header = $tableRows[0];
    $classHeaders = array_slice($header, 1);
    $slotIndex = 0;

    DB::transaction(function () use (
        &$summary,
        $tableRows,
        $classHeaders,
        &$slotIndex,
        $days,
        $session,
        &$teacherAliasMap,
        $teacherRole,
        &$assignmentCounter
    ): void {
        foreach (array_slice($tableRows, 1) as $row) {
            if (count($row) < 2) {
                continue;
            }

            $timeCell = sanitizeText((string) ($row[0] ?? ''));
            if ($timeCell === '' || isBreakLike($timeCell)) {
                continue;
            }
            $firstPayloadCell = sanitizeText((string) ($row[1] ?? ''));
            if (isBreakLike($firstPayloadCell) && count($row) <= 2) {
                continue;
            }

            $slotIndex++;
            [$start, $end] = parseTimeRange($timeCell);

            foreach ($days as $day) {
                TimeSlot::query()->updateOrCreate(
                    ['day_of_week' => $day, 'slot_index' => $slotIndex],
                    ['start_time' => $start, 'end_time' => $end]
                );
                $summary['timeslots_upserted']++;
            }

            foreach ($classHeaders as $offset => $classHeader) {
                $classLabel = sanitizeText((string) $classHeader);
                if ($classLabel === '') {
                    continue;
                }

                $cellText = sanitizeText((string) ($row[$offset + 1] ?? ''));
                if ($cellText === '' || isBreakLike($cellText)) {
                    continue;
                }

                [$className, $classSection] = normalizeClassLabel($classLabel);
                $classRoom = resolveOrCreateClass($className, $classSection, $summary);
                $classSectionModel = resolveOrCreateClassSection($classRoom, $classSection, $summary);

                $parsedCell = parseTimetableCell($cellText);
                if ($parsedCell['subject'] === null) {
                    $summary['warnings'][] = "Timetable slot {$slotIndex} {$classLabel}: unable to parse subject from `{$cellText}`.";
                    continue;
                }

                $subject = resolveOrCreateSubject($parsedCell['subject'], $summary);

                $teacher = null;
                if ($parsedCell['teacher'] !== null) {
                    $teacher = resolveOrCreateTeacher($parsedCell['teacher'], $teacherRole, $summary);
                    mapTeacherAliases($teacher->user?->name ?? $parsedCell['teacher'], (int) $teacher->id, $teacherAliasMap);
                } else {
                    $teacher = resolveTeacherByAliases($cellText, $teacherAliasMap);
                    if (! $teacher) {
                        $teacher = resolveOrCreateTeacher('Staff TBD', $teacherRole, $summary);
                        mapTeacherAliases('Staff TBD', (int) $teacher->id, $teacherAliasMap);
                    }
                }

                $roomName = trim($className.' '.($classSection ?? '').' Room');
                $room = Room::query()->firstOrCreate(
                    ['name' => $roomName],
                    ['type' => requiredRoomType((string) $subject->name)]
                );
                if ($room->wasRecentlyCreated) {
                    $summary['rooms_created']++;
                }

                DB::table('class_subject')->insertOrIgnore([
                    'class_id' => (int) $classRoom->id,
                    'subject_id' => (int) $subject->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $assignmentExists = TeacherAssignment::query()
                    ->where('teacher_id', (int) $teacher->id)
                    ->where('class_id', (int) $classRoom->id)
                    ->where('subject_id', (int) $subject->id)
                    ->where('session', $session)
                    ->where('is_class_teacher', false)
                    ->exists();
                if (! $assignmentExists) {
                    TeacherAssignment::query()->create([
                        'teacher_id' => (int) $teacher->id,
                        'class_id' => (int) $classRoom->id,
                        'subject_id' => (int) $subject->id,
                        'is_class_teacher' => false,
                        'session' => $session,
                    ]);
                    $summary['teacher_assignments_created']++;
                }

                $group = sectionGroupName($classSectionModel);
                $counterKey = assignmentCounterKey($session, (int) $classRoom->id, (int) $subject->id, (int) $teacher->id, $group);
                $assignmentCounter[$counterKey] = (int) ($assignmentCounter[$counterKey] ?? 0) + count($days);

                TeacherSubjectAssignment::query()->updateOrCreate(
                    [
                        'session' => $session,
                        'class_id' => (int) $classRoom->id,
                        'subject_id' => (int) $subject->id,
                        'teacher_id' => (int) $teacher->id,
                        'group_name' => $group,
                    ],
                    [
                        'class_section_id' => (int) $classSectionModel->id,
                        'lessons_per_week' => max(1, (int) $assignmentCounter[$counterKey]),
                    ]
                );
                $summary['teacher_subject_assignments_upserted']++;

                foreach ($days as $day) {
                    TimetableEntry::query()->updateOrCreate(
                        [
                            'session' => $session,
                            'class_section_id' => (int) $classSectionModel->id,
                            'day_of_week' => $day,
                            'slot_index' => $slotIndex,
                        ],
                        [
                            'subject_id' => (int) $subject->id,
                            'teacher_id' => (int) $teacher->id,
                            'room_id' => (int) $room->id,
                        ]
                    );
                    $summary['timetable_entries_upserted']++;
                }
            }
        }
    });
}

$summary['teachers_created'] = (int) Teacher::query()->count();

echo "Desktop data import completed for session {$session}.\n\n";
echo "Summary:\n";
echo '  Teachers total: '.$summary['teachers_created']."\n";
echo '  Students created: '.$summary['students_created']."\n";
echo '  Students updated: '.$summary['students_updated']."\n";
echo '  Students skipped: '.$summary['students_skipped']."\n";
echo '  Classes created: '.$summary['classes_created']."\n";
echo '  Subjects created: '.$summary['subjects_created']."\n";
echo '  Teacher assignments created: '.$summary['teacher_assignments_created']."\n";
echo '  Teacher-subject assignments upserted: '.$summary['teacher_subject_assignments_upserted']."\n";
echo '  Class sections created: '.$summary['class_sections_created']."\n";
echo '  Time slots upserted: '.$summary['timeslots_upserted']."\n";
echo '  Timetable entries upserted: '.$summary['timetable_entries_upserted']."\n";
echo '  Rooms created: '.$summary['rooms_created']."\n";

if (! empty($studentSummary['errors']) && is_array($studentSummary['errors'])) {
    echo "\nStudent import warnings:\n";
    foreach (array_slice($studentSummary['errors'], 0, 20) as $error) {
        $row = (int) ($error['row'] ?? 0);
        $message = (string) ($error['message'] ?? 'Unknown');
        echo "  - Row {$row}: {$message}\n";
    }
    if (count($studentSummary['errors']) > 20) {
        echo '  ...and '.(count($studentSummary['errors']) - 20)." more.\n";
    }
}

if (! empty($summary['warnings'])) {
    echo "\nAdditional warnings:\n";
    foreach (array_slice($summary['warnings'], 0, 30) as $warning) {
        echo "  - {$warning}\n";
    }
    if (count($summary['warnings']) > 30) {
        echo '  ...and '.(count($summary['warnings']) - 30)." more.\n";
    }
}

function currentSession(): string
{
    $now = now();
    $startYear = $now->month >= 7 ? $now->year : ($now->year - 1);

    return $startYear.'-'.($startYear + 1);
}

function sanitizeText(string $value): string
{
    $value = str_replace(["\r", "\n", "\t"], ' ', $value);
    $value = preg_replace('/\s+/', ' ', $value) ?? $value;

    return trim($value);
}

function normalizeClassLabel(string $raw): array
{
    $text = Str::lower(sanitizeText($raw));
    $text = str_replace(['–', '—'], '-', $text);
    $section = null;

    if (str_contains($text, 'boys') || str_contains($text, 'boy')) {
        $section = 'Boys';
    } elseif (str_contains($text, 'girls') || str_contains($text, 'girl')) {
        $section = 'Girls';
    }

    if (preg_match('/play\s*group/i', $raw)) {
        return ['Play Group', $section];
    }
    if (preg_match('/nursery/i', $raw)) {
        return ['Nursery', $section];
    }
    if (preg_match('/prep/i', $raw)) {
        return ['Prep', $section];
    }
    if (preg_match('/\b(1|2)(?:st|nd)?\s*year\b/i', $raw, $m)) {
        $label = ((int) $m[1] === 1) ? '1st Year' : '2nd Year';
        return [$label, $section];
    }
    if (preg_match('/\b(9|10|11|12)(?:st|nd|rd|th)?\b/i', $raw, $m)) {
        return ['Class '.(int) $m[1], $section];
    }
    if (preg_match('/\b([1-8])\b/', $raw, $m)) {
        return ['Class '.(int) $m[1], $section];
    }

    return [Str::title($raw), $section];
}

function resolveOrCreateClass(string $name, ?string $section, array &$summary): SchoolClass
{
    $class = SchoolClass::query()->firstOrCreate(
        ['name' => $name, 'section' => $section],
        ['status' => 'active']
    );

    if ($class->wasRecentlyCreated) {
        $summary['classes_created']++;
    }

    return $class;
}

function resolveOrCreateClassSection(SchoolClass $classRoom, ?string $classSection, array &$summary): ClassSection
{
    $sectionName = $classSection ?: 'A';

    $section = ClassSection::query()->firstOrCreate([
        'class_id' => (int) $classRoom->id,
        'section_name' => mb_substr($sectionName, 0, 10),
    ]);

    if ($section->wasRecentlyCreated) {
        $summary['class_sections_created']++;
    }

    return $section;
}

function sectionGroupName(ClassSection $section): string
{
    return mb_substr((string) $section->section_name, 0, 20);
}

function resolveOrCreateSubject(string $rawName, array &$summary): Subject
{
    $name = normalizeSubjectName($rawName);
    $subject = Subject::query()->firstOrCreate(
        ['name' => $name],
        [
            'code' => null,
            'status' => 'active',
            'is_default' => false,
        ]
    );

    if ($subject->wasRecentlyCreated) {
        $summary['subjects_created']++;
    }

    return $subject;
}

function normalizeSubjectName(string $raw): string
{
    $name = sanitizeText($raw);
    $name = preg_replace('/\s*\/\s*/', '/', $name) ?? $name;
    $name = trim($name, '/');
    if (str_contains($name, '/')) {
        $name = trim(explode('/', $name)[0]);
    }

    $directMap = [
        'bio' => 'Biology',
        'biology' => 'Biology',
        'math' => 'Mathematics',
        'maths' => 'Mathematics',
        'comp' => 'Computer Science',
        'computer' => 'Computer Science',
        'quran' => 'Nazra Quran',
        'nazra' => 'Nazra Quran',
        'islamyat' => 'Islamiat',
        'islamiyat' => 'Islamiat',
        'civis' => 'Civics',
        'geo' => 'Geography',
        'trading' => 'Principles of Commerce',
        'banking' => 'Principles of Commerce',
    ];

    $containsMap = [
        'g. math' => 'General Mathematics',
        'g math' => 'General Mathematics',
        'general math' => 'General Mathematics',
        'b.math' => 'Business Mathematics',
        'b math' => 'Business Mathematics',
        'business math' => 'Business Mathematics',
        'b.state' => 'Statistics',
        'business statistics' => 'Statistics',
        'pak study' => 'Pakistan Studies',
        'pak. study' => 'Pakistan Studies',
        'islamyat study' => 'Islamiat',
        'islamic studies' => 'Islamiat',
    ];

    $lower = Str::lower($name);
    $compact = preg_replace('/[^a-z0-9]+/', '', $lower) ?? '';

    if (isset($directMap[$compact])) {
        return $directMap[$compact];
    }

    foreach ($containsMap as $from => $to) {
        if (str_contains($lower, $from)) {
            return $to;
        }
    }

    return Str::title($name);
}

function resolveOrCreateTeacher(string $teacherName, Role $teacherRole, array &$summary): Teacher
{
    $cleanName = sanitizeTeacherName($teacherName);
    $user = User::query()
        ->whereRaw('LOWER(name) = ?', [Str::lower($cleanName)])
        ->first();

    if (! $user) {
        $email = uniqueEmailForName($cleanName);
        $user = User::query()->create([
            'name' => $cleanName,
            'email' => $email,
            'password' => Hash::make('password'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
    } else {
        if (! $user->email_verified_at) {
            $user->email_verified_at = now();
        }
        if ($user->status !== 'active') {
            $user->status = 'active';
        }
        $user->save();
    }

    if (! $user->hasRole('Teacher')) {
        $user->assignRole($teacherRole);
    }

    $teacher = Teacher::query()->where('user_id', (int) $user->id)->first();
    if (! $teacher) {
        $teacher = Teacher::query()->create([
            'teacher_id' => nextTeacherCode(),
            'user_id' => (int) $user->id,
            'designation' => 'Teacher',
            'employee_code' => null,
        ]);
        $summary['teachers_created']++;
    }

    return $teacher;
}

function sanitizeTeacherName(string $raw): string
{
    $name = sanitizeText($raw);
    $name = preg_replace('/\bM\.\s*/i', 'Madam ', $name) ?? $name;
    $name = preg_replace('/\bM\s+([A-Za-z])/i', 'Madam $1', $name) ?? $name;

    return Str::title($name);
}

function uniqueEmailForName(string $name): string
{
    $slug = Str::slug($name);
    if ($slug === '') {
        $slug = 'teacher';
    }

    $counter = 1;
    do {
        $candidate = $slug.($counter > 1 ? $counter : '').'@school.local';
        $exists = User::query()->where('email', $candidate)->exists();
        $counter++;
    } while ($exists);

    return $candidate;
}

function nextTeacherCode(): string
{
    $maxId = (int) Teacher::query()->max('id');
    $next = $maxId + 1;
    $code = 'T-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);

    while (Teacher::query()->where('teacher_id', $code)->exists()) {
        $next++;
        $code = 'T-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    return $code;
}

function parseTeacherToken(string $token): array
{
    $token = sanitizeText($token);

    if ($token === '') {
        return ['subject_name' => null, 'class_label' => null];
    }

    if (preg_match('/^(play\s*group|nursery|prep)$/i', $token)) {
        return ['subject_name' => null, 'class_label' => $token];
    }

    if (preg_match('/^(.+?)\s+((?:1|2)(?:st|nd)?\s*year(?:\s+(?:boys?|girls?))?)$/i', $token, $m)) {
        return [
            'subject_name' => normalizeSubjectName($m[1]),
            'class_label' => $m[2],
        ];
    }

    if (preg_match('/^(.+?)\s+((?:[1-9]|10|11|12)(?:st|nd|rd|th)?(?:\s+(?:boys?|girls?))?)$/i', $token, $m)) {
        return [
            'subject_name' => normalizeSubjectName($m[1]),
            'class_label' => $m[2],
        ];
    }
    if (preg_match('/^(.+?)([1-9]|10|11|12)(?:st|nd|rd|th)?(?:\s+(boys?|girls?))?$/i', $token, $m)) {
        $suffix = isset($m[3]) && $m[3] !== '' ? ' '.$m[3] : '';
        return [
            'subject_name' => normalizeSubjectName($m[1]),
            'class_label' => $m[2].$suffix,
        ];
    }

    return [
        'subject_name' => normalizeSubjectName($token),
        'class_label' => null,
    ];
}

function assignmentCounterKey(string $session, int $classId, int $subjectId, int $teacherId, string $group): string
{
    return $session.'|'.$classId.'|'.$subjectId.'|'.$teacherId.'|'.Str::lower($group);
}

function readDocxTableRows(string $path): array
{
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        return [];
    }

    try {
        $documentXml = $zip->getFromName('word/document.xml');
        if (! is_string($documentXml) || trim($documentXml) === '') {
            return [];
        }

        $xml = simplexml_load_string($documentXml);
        if ($xml === false) {
            return [];
        }
        $xml->registerXPathNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        $tables = $xml->xpath('//w:tbl') ?: [];
        if (empty($tables)) {
            return [];
        }

        $rows = [];
        $rowNodes = $tables[0]->xpath('.//w:tr') ?: [];
        foreach ($rowNodes as $rowNode) {
            $cells = [];
            $cellNodes = $rowNode->xpath('./w:tc') ?: [];
            foreach ($cellNodes as $cellNode) {
                $texts = [];
                foreach (($cellNode->xpath('.//w:t') ?: []) as $textNode) {
                    $texts[] = (string) $textNode;
                }
                $cells[] = sanitizeText(implode(' ', $texts));
            }
            if (! empty($cells)) {
                $rows[] = $cells;
            }
        }

        return $rows;
    } finally {
        $zip->close();
    }
}

function isBreakLike(string $value): bool
{
    $lower = Str::lower(sanitizeText($value));

    return $lower === 'break'
        || str_contains($lower, 'assemb')
        || str_contains($lower, 'assembly');
}

function parseTimeRange(string $timeCell): array
{
    $value = str_replace(['—', '–'], '-', sanitizeText($timeCell));
    if (! str_contains($value, '-')) {
        return ['08:00:00', '08:40:00'];
    }

    [$rawStart, $rawEnd] = array_pad(explode('-', $value, 2), 2, '');

    return [normalizeClock($rawStart), normalizeClock($rawEnd)];
}

function normalizeClock(string $raw): string
{
    if (! preg_match('/(\d{1,2})\s*[:.]\s*(\d{2})/', $raw, $m)) {
        return '08:00:00';
    }

    $hour = (int) $m[1];
    $minute = (int) $m[2];
    if ($hour <= 2) {
        $hour += 12;
    }

    return sprintf('%02d:%02d:00', $hour, $minute);
}

function parseTimetableCell(string $cell): array
{
    $value = sanitizeText($cell);
    if ($value === '') {
        return ['subject' => null, 'teacher' => null];
    }

    $markerPattern = '/\b(?:sir|madam|hafiz|m\.)\s*[a-z]+|\bM\s+[a-z]+/i';
    $subjectPart = $value;
    $teacherPart = '';

    if (preg_match($markerPattern, $value, $match, PREG_OFFSET_CAPTURE)) {
        $offset = (int) $match[0][1];
        $subjectPart = trim(substr($value, 0, $offset));
        $teacherPart = trim(substr($value, $offset));
    }

    $subject = normalizeSubjectName($subjectPart);
    if ($subject === '') {
        return ['subject' => null, 'teacher' => null];
    }

    $teacher = null;
    if ($teacherPart !== '' && preg_match('/\b(?:Sir|Madam|Hafiz|M\.)\s*[A-Za-z]+|\bM\s+[A-Za-z]+/i', $teacherPart, $tm)) {
        $teacher = sanitizeTeacherName($tm[0]);
    }

    return [
        'subject' => $subject,
        'teacher' => $teacher,
    ];
}

function requiredRoomType(string $subjectName): string
{
    $lower = Str::lower($subjectName);
    if (Str::contains($lower, ['physics', 'chemistry', 'biology', 'computer', 'lab', 'practical'])) {
        return 'lab';
    }

    return 'classroom';
}

function mapTeacherAliases(string $teacherName, int $teacherId, array &$aliasMap): void
{
    $raw = Str::lower($teacherName);
    $aliases = [
        $raw,
        preg_replace('/\b(madam|sir|hafiz)\b/i', '', $raw) ?: '',
    ];

    foreach ($aliases as $alias) {
        $alias = sanitizeAlias($alias);
        if ($alias !== '') {
            $aliasMap[$alias] = $teacherId;
        }
    }
}

function sanitizeAlias(string $value): string
{
    $value = Str::lower(sanitizeText($value));
    $value = str_replace(['.', ',', '/', '\\'], ' ', $value);
    $value = preg_replace('/\b(madam|sir|hafiz)\b/', '', $value) ?? $value;
    $value = preg_replace('/\s+/', ' ', $value) ?? $value;

    return trim($value);
}

function resolveTeacherByAliases(string $cellText, array $aliasMap): ?Teacher
{
    $value = sanitizeAlias($cellText);
    if ($value === '') {
        return null;
    }

    foreach ($aliasMap as $alias => $teacherId) {
        if ($alias !== '' && str_contains($value, $alias)) {
            return Teacher::query()->find((int) $teacherId);
        }
    }

    return null;
}
