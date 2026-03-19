<?php

use App\Modules\Analytics\Services\FeatureBuilderService;
use App\Services\ReminderNotificationService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('analytics:build-features {--session=}', function (FeatureBuilderService $featureBuilder) {
    $session = (string) ($this->option('session') ?: '');
    if ($session === '') {
        $now = now();
        $startYear = $now->month >= 7 ? $now->year : ($now->year - 1);
        $session = $startYear.'-'.($startYear + 1);
    }

    $this->info('Building analytics features for session: '.$session);

    $result = $featureBuilder->buildForSession($session);

    $this->table(
        ['Metric', 'Value'],
        [
            ['Session', $result['session']],
            ['Date Range', implode(' to ', $result['date_range'] ?? [])],
            ['Students', (string) ($result['students_count'] ?? 0)],
            ['Features Upserted', (string) ($result['features_upserted'] ?? 0)],
            ['Predictions Upserted', (string) ($result['predictions_upserted'] ?? 0)],
        ]
    );
})->purpose('Build student performance features and risk predictions for a session');

Artisan::command('notifications:marks-submission-reminder {--session=} {--date=}', function (ReminderNotificationService $service) {
    $date = (string) ($this->option('date') ?: now()->toDateString());
    $session = (string) ($this->option('session') ?: $service->sessionFromDate($date));

    $result = $service->sendMarksSubmissionReminders($session, $date);

    $this->table(
        ['Metric', 'Value'],
        [
            ['Session', $result['session']],
            ['Date', $result['date']],
            ['Notified', (string) $result['notified']],
            ['Skipped', (string) $result['skipped']],
        ]
    );
})->purpose('Send marks submission reminders to teachers');

Artisan::command('notifications:attendance-cutoff-reminder {--session=} {--date=} {--cutoff=13:00}', function (ReminderNotificationService $service) {
    $date = (string) ($this->option('date') ?: now()->toDateString());
    $cutoff = (string) ($this->option('cutoff') ?: '13:00');
    $session = (string) ($this->option('session') ?: $service->sessionFromDate($date));

    $result = $service->sendAttendanceCutoffReminders($session, $date, $cutoff);

    $this->table(
        ['Metric', 'Value'],
        [
            ['Session', $result['session']],
            ['Date', $result['date']],
            ['Cutoff', $result['cutoff_time']],
            ['Notified', (string) $result['notified']],
            ['Skipped', (string) $result['skipped']],
        ]
    );
})->purpose('Send attendance cutoff reminders to class teachers');

Artisan::command('classes:merge-legacy-aliases {--dry-run}', function () {
    $mapping = [
        35 => 3,   // 1 -> Class 1
        39 => 4,   // 2 -> Class 2
        40 => 54,  // 3-A -> Class 3 A
        41 => 55,  // 3-B -> Class 3 B
        42 => 56,  // 4-A -> Class 4 A
        43 => 57,  // 4-B -> Class 4 B
        44 => 7,   // 5 -> Class 5
        45 => 58,  // 6-A -> Class 6 A
        46 => 59,  // 6-B -> Class 6 B
        47 => 60,  // 7-A -> Class 7 A
        48 => 61,  // 7-B -> Class 7 B
        49 => 10,  // 8 -> Class 8
        50 => 62,  // 9-A -> Class 9 A
        51 => 63,  // 9-B -> Class 9 B
        36 => 12,  // 10 -> Class 10
        37 => 24,  // 11 -> Class 11
        38 => 26,  // 12 -> Class 12
    ];

    $dryRun = (bool) $this->option('dry-run');
    $now = now()->toDateTimeString();
    $oldClassIds = array_keys($mapping);

    $missingClasses = [];
    foreach ($mapping as $oldId => $newId) {
        $oldExists = DB::table('school_classes')->where('id', $oldId)->exists();
        $newExists = DB::table('school_classes')->where('id', $newId)->exists();
        if (! $oldExists || ! $newExists) {
            $missingClasses[] = [
                'old_id' => $oldId,
                'new_id' => $newId,
                'old_exists' => $oldExists ? 'yes' : 'no',
                'new_exists' => $newExists ? 'yes' : 'no',
            ];
        }
    }

    if ($missingClasses !== []) {
        $this->error('Merge aborted because some mapped classes are missing.');
        $this->table(['old_id', 'new_id', 'old_exists', 'new_exists'], $missingClasses);

        return;
    }

    $summary = [
        'students_moved' => 0,
        'attendance_moved' => 0,
        'class_subject_moved' => 0,
        'teacher_assignments_moved' => 0,
        'teacher_assignments_deduped' => 0,
        'teacher_subject_assignments_moved' => 0,
        'teacher_subject_assignments_deduped' => 0,
        'class_sections_moved' => 0,
        'class_sections_deduped' => 0,
        'subject_period_rules_moved' => 0,
        'subject_period_rules_deduped' => 0,
        'timetable_entries_moved' => 0,
        'timetable_entries_deduped' => 0,
        'teacher_subject_sections_relinked' => 0,
        'classes_deleted' => 0,
        'classes_kept_with_refs' => 0,
    ];

    $referenceCountForClass = function (int $classId): int {
        return
            DB::table('students')->where('class_id', $classId)->count() +
            DB::table('attendance')->where('class_id', $classId)->count() +
            DB::table('exams')->where('class_id', $classId)->count() +
            DB::table('teacher_assignments')->where('class_id', $classId)->count() +
            DB::table('teacher_subject_assignments')->where('class_id', $classId)->count() +
            DB::table('class_subject')->where('class_id', $classId)->count() +
            DB::table('class_sections')->where('class_id', $classId)->count();
    };

    if ($dryRun) {
        $previewRows = [];
        foreach ($mapping as $oldId => $newId) {
            $previewRows[] = [
                'old_id' => $oldId,
                'new_id' => $newId,
                'teacher_assignments' => DB::table('teacher_assignments')->where('class_id', $oldId)->count(),
                'teacher_subject_assignments' => DB::table('teacher_subject_assignments')->where('class_id', $oldId)->count(),
                'class_sections' => DB::table('class_sections')->where('class_id', $oldId)->count(),
                'class_subject' => DB::table('class_subject')->where('class_id', $oldId)->count(),
            ];
        }

        $this->info('Dry run: no changes written.');
        $this->table(
            ['old_id', 'new_id', 'teacher_assignments', 'teacher_subject_assignments', 'class_sections', 'class_subject'],
            $previewRows
        );

        return;
    }

    DB::transaction(function () use ($mapping, $oldClassIds, $now, &$summary, $referenceCountForClass): void {
        foreach ($mapping as $oldId => $newId) {
            $summary['students_moved'] += DB::table('students')->where('class_id', $oldId)->update(['class_id' => $newId, 'updated_at' => $now]);
            $summary['attendance_moved'] += DB::table('attendance')->where('class_id', $oldId)->update(['class_id' => $newId, 'updated_at' => $now]);

            $classSubjectRows = DB::table('class_subject')->where('class_id', $oldId)->get();
            foreach ($classSubjectRows as $row) {
                $inserted = DB::table('class_subject')->insertOrIgnore([
                    'class_id' => $newId,
                    'subject_id' => $row->subject_id,
                ]);
                if ($inserted > 0) {
                    $summary['class_subject_moved']++;
                }
            }
            DB::table('class_subject')->where('class_id', $oldId)->delete();

            $assignmentRows = DB::table('teacher_assignments')->where('class_id', $oldId)->orderBy('id')->get();
            foreach ($assignmentRows as $row) {
                $query = DB::table('teacher_assignments')
                    ->where('class_id', $newId)
                    ->where('session', $row->session)
                    ->where('is_class_teacher', $row->is_class_teacher);

                if ((int) $row->is_class_teacher === 1) {
                    $query->whereNull('subject_id');
                } else {
                    $query->where('subject_id', $row->subject_id);
                }

                $conflicts = $query->get();

                if ($conflicts->isEmpty()) {
                    DB::table('teacher_assignments')->where('id', $row->id)->update([
                        'class_id' => $newId,
                        'updated_at' => $now,
                    ]);
                    $summary['teacher_assignments_moved']++;
                    continue;
                }

                $winnerId = (int) $row->id;
                $winnerUpdatedAt = $row->updated_at ? strtotime((string) $row->updated_at) : 0;
                foreach ($conflicts as $conflict) {
                    $candidateTime = $conflict->updated_at ? strtotime((string) $conflict->updated_at) : 0;
                    if ($candidateTime > $winnerUpdatedAt || ($candidateTime === $winnerUpdatedAt && (int) $conflict->id > $winnerId)) {
                        $winnerId = (int) $conflict->id;
                        $winnerUpdatedAt = $candidateTime;
                    }
                }

                if ($winnerId === (int) $row->id) {
                    DB::table('teacher_assignments')->where('id', $row->id)->update([
                        'class_id' => $newId,
                        'updated_at' => $now,
                    ]);
                    $summary['teacher_assignments_moved']++;

                    $deleteIds = $conflicts->pluck('id')->map(fn ($id) => (int) $id)->all();
                    if ($deleteIds !== []) {
                        DB::table('teacher_assignments')->whereIn('id', $deleteIds)->delete();
                        $summary['teacher_assignments_deduped'] += count($deleteIds);
                    }
                } else {
                    DB::table('teacher_assignments')->where('id', $row->id)->delete();
                    $summary['teacher_assignments_deduped']++;
                }
            }

            $subjectAssignmentRows = DB::table('teacher_subject_assignments')->where('class_id', $oldId)->orderBy('id')->get();
            foreach ($subjectAssignmentRows as $row) {
                $key = [
                    'session' => $row->session,
                    'class_id' => $newId,
                    'subject_id' => $row->subject_id,
                    'teacher_id' => $row->teacher_id,
                    'group_name' => $row->group_name,
                ];

                $existing = DB::table('teacher_subject_assignments')->where($key)->first();
                if (! $existing) {
                    DB::table('teacher_subject_assignments')->insert([
                        'session' => $row->session,
                        'class_id' => $newId,
                        'class_section_id' => $row->class_section_id,
                        'subject_id' => $row->subject_id,
                        'teacher_id' => $row->teacher_id,
                        'group_name' => $row->group_name,
                        'lessons_per_week' => $row->lessons_per_week,
                        'created_at' => $row->created_at ?? $now,
                        'updated_at' => $now,
                    ]);
                    $summary['teacher_subject_assignments_moved']++;
                } else {
                    $mergedLessons = max((int) $existing->lessons_per_week, (int) $row->lessons_per_week);
                    $mergedSectionId = $existing->class_section_id ?: $row->class_section_id;

                    DB::table('teacher_subject_assignments')->where('id', $existing->id)->update([
                        'lessons_per_week' => $mergedLessons,
                        'class_section_id' => $mergedSectionId,
                        'updated_at' => $now,
                    ]);
                    $summary['teacher_subject_assignments_deduped']++;
                }

                DB::table('teacher_subject_assignments')->where('id', $row->id)->delete();
            }
        }

        $sectionRows = DB::table('class_sections')->whereIn('class_id', $oldClassIds)->orderBy('id')->get();
        $sectionMap = [];
        $duplicateSectionIds = [];

        foreach ($sectionRows as $row) {
            $newClassId = $mapping[(int) $row->class_id] ?? null;
            if (! $newClassId) {
                continue;
            }

            $existing = DB::table('class_sections')
                ->where('class_id', $newClassId)
                ->where('section_name', $row->section_name)
                ->first();

            if (! $existing) {
                DB::table('class_sections')->where('id', $row->id)->update([
                    'class_id' => $newClassId,
                    'updated_at' => $now,
                ]);
                $sectionMap[(int) $row->id] = (int) $row->id;
                $summary['class_sections_moved']++;
            } else {
                $sectionMap[(int) $row->id] = (int) $existing->id;
                $duplicateSectionIds[] = (int) $row->id;
                $summary['class_sections_deduped']++;
            }
        }

        foreach ($sectionMap as $oldSectionId => $newSectionId) {
            if ($oldSectionId === $newSectionId) {
                continue;
            }

            $ruleRows = DB::table('subject_period_rules')->where('class_section_id', $oldSectionId)->orderBy('id')->get();
            foreach ($ruleRows as $rule) {
                $existing = DB::table('subject_period_rules')
                    ->where('session', $rule->session)
                    ->where('class_section_id', $newSectionId)
                    ->where('subject_id', $rule->subject_id)
                    ->first();

                if (! $existing) {
                    DB::table('subject_period_rules')->where('id', $rule->id)->update([
                        'class_section_id' => $newSectionId,
                        'updated_at' => $now,
                    ]);
                    $summary['subject_period_rules_moved']++;
                } else {
                    DB::table('subject_period_rules')->where('id', $existing->id)->update([
                        'periods_per_week' => max((int) $existing->periods_per_week, (int) $rule->periods_per_week),
                        'updated_at' => $now,
                    ]);
                    DB::table('subject_period_rules')->where('id', $rule->id)->delete();
                    $summary['subject_period_rules_deduped']++;
                }
            }

            $entryRows = DB::table('timetable_entries')->where('class_section_id', $oldSectionId)->orderBy('id')->get();
            foreach ($entryRows as $entry) {
                $existing = DB::table('timetable_entries')
                    ->where('session', $entry->session)
                    ->where('class_section_id', $newSectionId)
                    ->where('day_of_week', $entry->day_of_week)
                    ->where('slot_index', $entry->slot_index)
                    ->first();

                if (! $existing) {
                    DB::table('timetable_entries')->where('id', $entry->id)->update([
                        'class_section_id' => $newSectionId,
                        'updated_at' => $now,
                    ]);
                    $summary['timetable_entries_moved']++;
                } else {
                    DB::table('timetable_entries')->where('id', $existing->id)->update([
                        'subject_id' => $existing->subject_id ?: $entry->subject_id,
                        'teacher_id' => $existing->teacher_id ?: $entry->teacher_id,
                        'room_id' => $existing->room_id ?: $entry->room_id,
                        'updated_at' => $now,
                    ]);
                    DB::table('timetable_entries')->where('id', $entry->id)->delete();
                    $summary['timetable_entries_deduped']++;
                }
            }

            $summary['teacher_subject_sections_relinked'] += DB::table('teacher_subject_assignments')
                ->where('class_section_id', $oldSectionId)
                ->update(['class_section_id' => $newSectionId, 'updated_at' => $now]);
        }

        if ($duplicateSectionIds !== []) {
            DB::table('class_sections')->whereIn('id', $duplicateSectionIds)->delete();
        }

        foreach ($oldClassIds as $oldClassId) {
            $refs = $referenceCountForClass((int) $oldClassId);
            if ($refs === 0) {
                DB::table('school_classes')->where('id', $oldClassId)->delete();
                $summary['classes_deleted']++;
            } else {
                $summary['classes_kept_with_refs']++;
            }
        }
    });

    $this->info('Legacy class aliases merged successfully.');
    $this->table(
        ['Metric', 'Value'],
        collect($summary)->map(fn ($value, $metric) => [$metric, (string) $value])->values()->all()
    );
})->purpose('Merge legacy duplicate class names into canonical Class N records and relink related data');

Schedule::command('analytics:build-features')
    ->weeklyOn(1, '01:00')
    ->withoutOverlapping();

Schedule::command('notifications:marks-submission-reminder')
    ->weekdays()
    ->dailyAt('15:00')
    ->withoutOverlapping();

Schedule::command('notifications:attendance-cutoff-reminder --cutoff=13:00')
    ->weekdays()
    ->dailyAt('13:10')
    ->withoutOverlapping();

Schedule::command('academic-events:send-notifications')
    ->dailyAt('07:00')
    ->withoutOverlapping();

Schedule::command('fees:process-late-fees')
    ->dailyAt('00:30')
    ->withoutOverlapping();
