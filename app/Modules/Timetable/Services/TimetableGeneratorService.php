<?php

namespace App\Modules\Timetable\Services;

use App\Models\ClassSection;
use App\Models\Room;
use App\Models\SubjectPeriodRule;
use App\Models\TeacherAssignment;
use App\Models\TeacherAvailability;
use App\Models\TimeSlot;
use App\Models\TimetableConstraint;
use App\Models\TimetableEntry;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

class TimetableGeneratorService
{
    private const MAX_ITERATIONS = 250000;
    private const MAX_CONFLICT_ITEMS = 500;

    public function generate(string $session, array $classSectionIds): array
    {
        $session = trim($session);
        $classSectionIds = collect($classSectionIds)
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        $logger = $this->logger();
        $this->log($logger, 'generation_started', [
            'session' => $session,
            'class_section_ids' => $classSectionIds,
        ]);

        if ($session === '' || empty($classSectionIds)) {
            return [
                'scheduled_count' => 0,
                'conflicts' => [[
                    'code' => 'invalid_input',
                    'message' => 'Session and class sections are required to generate a timetable.',
                    'context' => [],
                ]],
                'unresolved_subjects' => [],
            ];
        }

        $result = DB::transaction(function () use ($session, $classSectionIds, $logger): array {
            return $this->generateWithinTransaction($session, $classSectionIds, $logger);
        }, 3);

        $this->log($logger, 'generation_completed', [
            'session' => $session,
            'scheduled_count' => $result['scheduled_count'],
            'conflict_count' => count($result['conflicts']),
            'unresolved_subject_count' => count($result['unresolved_subjects']),
        ]);

        return $result;
    }

    private function generateWithinTransaction(string $session, array $classSectionIds, LoggerInterface $logger): array
    {
        $conflicts = [];
        $conflictKeys = [];

        $slots = TimeSlot::query()
            ->orderByRaw($this->dayOrderSql('day_of_week'))
            ->orderBy('slot_index')
            ->get(['day_of_week', 'slot_index'])
            ->map(fn (TimeSlot $slot): array => [
                'day_of_week' => $slot->day_of_week,
                'slot_index' => (int) $slot->slot_index,
            ])
            ->values()
            ->all();

        if (empty($slots)) {
            $this->addConflict(
                $conflicts,
                $conflictKeys,
                'no_slots',
                'No time slots found. Configure timetable slots first.',
                []
            );

            return [
                'scheduled_count' => 0,
                'conflicts' => $conflicts,
                'unresolved_subjects' => [],
            ];
        }

        $classSections = ClassSection::query()
            ->with('classRoom:id,name,section')
            ->whereIn('id', $classSectionIds)
            ->get(['id', 'class_id', 'section_name'])
            ->keyBy('id');

        if ($classSections->isEmpty()) {
            $this->addConflict(
                $conflicts,
                $conflictKeys,
                'no_sections',
                'No valid class sections found for generation.',
                []
            );

            return [
                'scheduled_count' => 0,
                'conflicts' => $conflicts,
                'unresolved_subjects' => [],
            ];
        }

        TimetableEntry::query()
            ->where('session', $session)
            ->whereIn('class_section_id', $classSections->keys())
            ->delete();

        $existingEntries = TimetableEntry::query()
            ->where('session', $session)
            ->get(['class_section_id', 'day_of_week', 'slot_index', 'subject_id', 'teacher_id', 'room_id']);

        $constraints = $this->resolveConstraints($session, $slots);

        $rules = SubjectPeriodRule::query()
            ->with([
                'subject:id,name,code',
                'classSection:id,class_id,section_name',
                'classSection.classRoom:id,name,section',
            ])
            ->where('session', $session)
            ->whereIn('class_section_id', $classSections->keys())
            ->get();

        if ($rules->isEmpty()) {
            $this->addConflict(
                $conflicts,
                $conflictKeys,
                'no_rules',
                'No subject period rules found for the selected session and class sections.',
                ['session' => $session]
            );

            return [
                'scheduled_count' => 0,
                'conflicts' => $conflicts,
                'unresolved_subjects' => [],
            ];
        }

        $classIds = $rules
            ->pluck('class_section_id')
            ->unique()
            ->map(fn ($id) => $classSections->get($id)?->class_id)
            ->filter()
            ->unique()
            ->values();

        $teacherAssignments = TeacherAssignment::query()
            ->where('session', $session)
            ->whereIn('class_id', $classIds)
            ->whereNotNull('subject_id')
            ->get(['teacher_id', 'class_id', 'subject_id']);

        $teacherMap = [];
        foreach ($teacherAssignments as $assignment) {
            $key = $assignment->class_id.'|'.$assignment->subject_id;
            $teacherMap[$key] ??= [];
            $teacherMap[$key][$assignment->teacher_id] = (int) $assignment->teacher_id;
        }
        $teacherMap = collect($teacherMap)->map(fn (array $ids): array => array_values($ids))->all();

        $teacherIds = $teacherAssignments->pluck('teacher_id')->unique()->values();
        $availabilityMap = TeacherAvailability::query()
            ->whereIn('teacher_id', $teacherIds)
            ->get(['teacher_id', 'day_of_week', 'slot_index', 'is_available'])
            ->mapWithKeys(fn (TeacherAvailability $availability): array => [
                $this->teacherSlotKey(
                    (int) $availability->teacher_id,
                    $availability->day_of_week,
                    (int) $availability->slot_index
                ) => (bool) $availability->is_available,
            ])
            ->all();

        $rooms = Room::query()
            ->orderBy('type')
            ->orderBy('id')
            ->get(['id', 'name', 'type'])
            ->map(fn (Room $room): array => [
                'id' => (int) $room->id,
                'name' => $room->name,
                'type' => Str::lower((string) $room->type),
            ])
            ->values()
            ->all();

        if (empty($rooms)) {
            $this->addConflict(
                $conflicts,
                $conflictKeys,
                'no_rooms',
                'No rooms found. Create rooms before timetable generation.',
                []
            );

            $unresolved = $rules->map(function (SubjectPeriodRule $rule) use ($classSections): array {
                $classSection = $classSections->get($rule->class_section_id);
                $label = $this->classSectionLabel($classSection);

                return [
                    'rule_id' => (int) $rule->id,
                    'class_section_id' => (int) $rule->class_section_id,
                    'class_section' => $label,
                    'subject_id' => (int) $rule->subject_id,
                    'subject' => $rule->subject?->name ?? 'Subject',
                    'required_periods' => (int) $rule->periods_per_week,
                    'scheduled_periods' => 0,
                    'unresolved_periods' => (int) $rule->periods_per_week,
                ];
            })->values()->all();

            return [
                'scheduled_count' => 0,
                'conflicts' => $conflicts,
                'unresolved_subjects' => $unresolved,
            ];
        }

        $roomsByType = [];
        foreach ($rooms as $room) {
            $roomsByType[$room['type']][] = $room;
        }

        $state = $this->buildInitialState($existingEntries);

        $ruleMeta = [];
        $ruleRequired = [];
        $ruleScheduled = [];
        $tasksById = [];
        $pendingTaskIds = [];

        $taskId = 1;
        foreach ($rules as $rule) {
            $classSection = $classSections->get($rule->class_section_id);
            if (! $classSection || ! $rule->subject) {
                $this->addConflict(
                    $conflicts,
                    $conflictKeys,
                    'invalid_rule',
                    'Invalid rule detected due to missing class section or subject.',
                    ['rule_id' => (int) $rule->id]
                );
                continue;
            }

            $ruleId = (int) $rule->id;
            $subjectName = (string) $rule->subject->name;
            $classLabel = $this->classSectionLabel($classSection);
            $teacherOptions = $teacherMap[$classSection->class_id.'|'.$rule->subject_id] ?? [];

            $ruleMeta[$ruleId] = [
                'rule_id' => $ruleId,
                'class_section_id' => (int) $rule->class_section_id,
                'class_section' => $classLabel,
                'subject_id' => (int) $rule->subject_id,
                'subject' => $subjectName,
                'teacher_options' => $teacherOptions,
                'is_core' => $this->isCoreSubject($subjectName),
                'required_room_type' => $this->requiredRoomType($subjectName),
            ];

            $required = (int) $rule->periods_per_week;
            $ruleRequired[$ruleId] = $required;
            $ruleScheduled[$ruleId] = 0;

            if (empty($teacherOptions)) {
                $this->addConflict(
                    $conflicts,
                    $conflictKeys,
                    'no_teacher_assignment',
                    'No teacher assignment found for '.$classLabel.' - '.$subjectName.'.',
                    [
                        'rule_id' => $ruleId,
                        'class_section_id' => (int) $rule->class_section_id,
                        'subject_id' => (int) $rule->subject_id,
                    ]
                );
                continue;
            }

            for ($period = 1; $period <= $required; $period++) {
                $tasksById[$taskId] = [
                    'task_id' => $taskId,
                    'rule_id' => $ruleId,
                    'class_section_id' => (int) $rule->class_section_id,
                    'class_section' => $classLabel,
                    'subject_id' => (int) $rule->subject_id,
                    'subject' => $subjectName,
                    'teacher_options' => $teacherOptions,
                    'is_core' => $ruleMeta[$ruleId]['is_core'],
                    'required_room_type' => $ruleMeta[$ruleId]['required_room_type'],
                ];

                $pendingTaskIds[] = $taskId;
                $taskId++;
            }
        }

        $context = [
            'slots' => $slots,
            'availability' => $availabilityMap,
            'max_teacher_day' => $constraints['max_teacher_day'],
            'max_teacher_week' => $constraints['max_teacher_week'],
            'max_class_day' => $constraints['max_class_day'],
            'rooms' => $rooms,
            'rooms_by_type' => $roomsByType,
        ];

        $assignments = [];
        $decisionStack = [];
        $unresolvedTaskIds = [];
        $iterations = 0;

        while (! empty($pendingTaskIds) && $iterations < self::MAX_ITERATIONS) {
            $iterations++;

            [$selectedTaskId, $candidateList] = $this->pickMostConstrainedTask(
                $pendingTaskIds,
                $tasksById,
                $state,
                $context
            );

            if ($selectedTaskId === null) {
                break;
            }

            if (empty($candidateList)) {
                $task = $tasksById[$selectedTaskId];
                $this->addConflict(
                    $conflicts,
                    $conflictKeys,
                    'slot_unavailable',
                    'No feasible slot available for '.$task['class_section'].' - '.$task['subject'].'.',
                    [
                        'task_id' => $selectedTaskId,
                        'rule_id' => $task['rule_id'],
                        'class_section_id' => $task['class_section_id'],
                        'subject_id' => $task['subject_id'],
                    ]
                );

                $didBacktrack = $this->attemptBacktrack(
                    $decisionStack,
                    $pendingTaskIds,
                    $tasksById,
                    $assignments,
                    $ruleScheduled,
                    $state,
                    $context,
                    $logger
                );

                if ($didBacktrack) {
                    continue;
                }

                $pendingTaskIds = array_values(array_filter(
                    $pendingTaskIds,
                    fn ($id): bool => $id !== $selectedTaskId
                ));
                $unresolvedTaskIds[$selectedTaskId] = true;
                continue;
            }

            $chosen = $candidateList[0];
            $this->applyCandidate($tasksById[$selectedTaskId], $chosen, $assignments, $ruleScheduled, $state);

            $decisionStack[] = [
                'task_id' => $selectedTaskId,
                'candidates' => $candidateList,
                'candidate_index' => 0,
            ];

            $pendingTaskIds = array_values(array_filter(
                $pendingTaskIds,
                fn ($id): bool => $id !== $selectedTaskId
            ));

            $this->log($logger, 'assignment_created', [
                'task_id' => $selectedTaskId,
                'rule_id' => $tasksById[$selectedTaskId]['rule_id'],
                'class_section_id' => $tasksById[$selectedTaskId]['class_section_id'],
                'subject_id' => $tasksById[$selectedTaskId]['subject_id'],
                'teacher_id' => $chosen['teacher_id'],
                'room_id' => $chosen['room_id'],
                'day_of_week' => $chosen['day_of_week'],
                'slot_index' => $chosen['slot_index'],
                'score' => $chosen['score'],
            ]);
        }

        if (! empty($pendingTaskIds)) {
            foreach ($pendingTaskIds as $taskIdLeft) {
                $unresolvedTaskIds[$taskIdLeft] = true;
            }

            $this->addConflict(
                $conflicts,
                $conflictKeys,
                'iteration_limit',
                'Generation stopped due to search iteration limit. Some subjects remain unresolved.',
                ['iteration_limit' => self::MAX_ITERATIONS]
            );
        }

        $now = now();
        $rows = [];
        foreach ($assignments as $assignedTaskId => $candidate) {
            $task = $tasksById[$assignedTaskId];
            $rows[] = [
                'session' => $session,
                'class_section_id' => $task['class_section_id'],
                'day_of_week' => $candidate['day_of_week'],
                'slot_index' => $candidate['slot_index'],
                'subject_id' => $task['subject_id'],
                'teacher_id' => $candidate['teacher_id'],
                'room_id' => $candidate['room_id'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (! empty($rows)) {
            TimetableEntry::query()->insert($rows);
        }

        $unresolvedSubjects = [];
        foreach ($ruleMeta as $ruleId => $meta) {
            $required = (int) ($ruleRequired[$ruleId] ?? 0);
            $scheduled = (int) ($ruleScheduled[$ruleId] ?? 0);
            $remaining = max(0, $required - $scheduled);

            if ($remaining > 0) {
                $unresolvedSubjects[] = [
                    'rule_id' => $ruleId,
                    'class_section_id' => $meta['class_section_id'],
                    'class_section' => $meta['class_section'],
                    'subject_id' => $meta['subject_id'],
                    'subject' => $meta['subject'],
                    'required_periods' => $required,
                    'scheduled_periods' => $scheduled,
                    'unresolved_periods' => $remaining,
                ];
            }
        }

        usort($unresolvedSubjects, function (array $a, array $b): int {
            return [$b['unresolved_periods'], $a['class_section'], $a['subject']]
                <=> [$a['unresolved_periods'], $b['class_section'], $b['subject']];
        });

        return [
            'scheduled_count' => count($rows),
            'conflicts' => $conflicts,
            'unresolved_subjects' => $unresolvedSubjects,
        ];
    }

    private function resolveConstraints(string $session, array $slots): array
    {
        $constraint = TimetableConstraint::query()->where('session', $session)->first();

        $maxSlotsPerDay = collect($slots)
            ->groupBy('day_of_week')
            ->map(fn (Collection $rows): int => $rows->count())
            ->max() ?? 0;

        $maxTeacherDay = (int) ($constraint?->max_periods_per_day_teacher ?? 6);
        $maxTeacherWeek = (int) ($constraint?->max_periods_per_week_teacher ?? 28);
        $maxClassDay = (int) ($constraint?->max_periods_per_day_class ?? $maxSlotsPerDay);

        return [
            'max_teacher_day' => max(1, $maxTeacherDay),
            'max_teacher_week' => max(1, $maxTeacherWeek),
            'max_class_day' => max(1, min(max(1, $maxSlotsPerDay), $maxClassDay)),
        ];
    }

    private function buildInitialState(Collection $existingEntries): array
    {
        $state = [
            'class_slot' => [],
            'teacher_slot' => [],
            'room_slot' => [],
            'teacher_day_load' => [],
            'teacher_week_load' => [],
            'class_day_load' => [],
            'class_subject_day' => [],
            'class_slot_subject' => [],
        ];

        foreach ($existingEntries as $entry) {
            $day = (string) $entry->day_of_week;
            $slot = (int) $entry->slot_index;
            $classSectionId = (int) $entry->class_section_id;
            $teacherId = (int) $entry->teacher_id;
            $roomId = (int) $entry->room_id;
            $subjectId = (int) $entry->subject_id;

            $classSlotKey = $this->classSlotKey($classSectionId, $day, $slot);
            $teacherSlotKey = $this->teacherSlotKey($teacherId, $day, $slot);
            $roomSlotKey = $this->roomSlotKey($roomId, $day, $slot);
            $teacherDayKey = $this->teacherDayKey($teacherId, $day);
            $classDayKey = $this->classDayKey($classSectionId, $day);
            $classSubjectDayKey = $this->classSubjectDayKey($classSectionId, $subjectId, $day);
            $classSlotSubjectKey = $this->classSlotSubjectKey($classSectionId, $day, $slot);

            $state['class_slot'][$classSlotKey] = true;
            $state['teacher_slot'][$teacherSlotKey] = true;
            $state['room_slot'][$roomSlotKey] = true;
            $state['teacher_day_load'][$teacherDayKey] = ($state['teacher_day_load'][$teacherDayKey] ?? 0) + 1;
            $state['teacher_week_load'][$teacherId] = ($state['teacher_week_load'][$teacherId] ?? 0) + 1;
            $state['class_day_load'][$classDayKey] = ($state['class_day_load'][$classDayKey] ?? 0) + 1;
            $state['class_subject_day'][$classSubjectDayKey] = ($state['class_subject_day'][$classSubjectDayKey] ?? 0) + 1;
            $state['class_slot_subject'][$classSlotSubjectKey] = $subjectId;
        }

        return $state;
    }

    private function pickMostConstrainedTask(array $pendingTaskIds, array $tasksById, array $state, array $context): array
    {
        $bestTaskId = null;
        $bestCandidates = [];
        $bestCount = PHP_INT_MAX;

        foreach ($pendingTaskIds as $taskId) {
            $task = $tasksById[$taskId] ?? null;
            if (! $task) {
                continue;
            }

            $candidates = $this->buildCandidates($task, $state, $context);
            $candidateCount = count($candidates);

            if ($candidateCount < $bestCount) {
                $bestCount = $candidateCount;
                $bestTaskId = $taskId;
                $bestCandidates = $candidates;

                if ($candidateCount === 0) {
                    break;
                }
                continue;
            }

            if ($candidateCount === $bestCount && $bestTaskId !== null) {
                $currentTeacherCount = count($task['teacher_options']);
                $bestTeacherCount = count($tasksById[$bestTaskId]['teacher_options']);
                if ($currentTeacherCount < $bestTeacherCount) {
                    $bestTaskId = $taskId;
                    $bestCandidates = $candidates;
                }
            }
        }

        return [$bestTaskId, $bestCandidates];
    }

    private function buildCandidates(array $task, array $state, array $context): array
    {
        $candidates = [];
        $slots = $context['slots'];

        foreach ($slots as $slot) {
            $day = $slot['day_of_week'];
            $slotIndex = (int) $slot['slot_index'];

            if (! $this->canPlaceClass($task['class_section_id'], $day, $slotIndex, $state, $context)) {
                continue;
            }

            foreach ($task['teacher_options'] as $teacherId) {
                $teacherId = (int) $teacherId;
                if (! $this->canPlaceTeacher($teacherId, $day, $slotIndex, $state, $context)) {
                    continue;
                }

                $room = $this->findAvailableRoom($task, $day, $slotIndex, $state, $context);
                if (! $room) {
                    continue;
                }

                $score = $this->scoreCandidate(
                    $task,
                    $day,
                    $slotIndex,
                    $teacherId,
                    $room,
                    $state
                );

                $candidates[] = [
                    'day_of_week' => $day,
                    'slot_index' => $slotIndex,
                    'teacher_id' => $teacherId,
                    'room_id' => $room['id'],
                    'room_type' => $room['type'],
                    'score' => $score,
                ];
            }
        }

        usort($candidates, function (array $a, array $b): int {
            return [$b['score'], $a['slot_index']] <=> [$a['score'], $b['slot_index']];
        });

        return $candidates;
    }

    private function canPlaceClass(int $classSectionId, string $day, int $slotIndex, array $state, array $context): bool
    {
        $classSlotKey = $this->classSlotKey($classSectionId, $day, $slotIndex);
        if (($state['class_slot'][$classSlotKey] ?? false) === true) {
            return false;
        }

        $classDayKey = $this->classDayKey($classSectionId, $day);
        $classDayLoad = (int) ($state['class_day_load'][$classDayKey] ?? 0);

        return $classDayLoad < (int) $context['max_class_day'];
    }

    private function canPlaceTeacher(int $teacherId, string $day, int $slotIndex, array $state, array $context): bool
    {
        $teacherSlotKey = $this->teacherSlotKey($teacherId, $day, $slotIndex);
        if (($state['teacher_slot'][$teacherSlotKey] ?? false) === true) {
            return false;
        }

        $availabilityKey = $this->teacherSlotKey($teacherId, $day, $slotIndex);
        $isAvailable = array_key_exists($availabilityKey, $context['availability'])
            ? (bool) $context['availability'][$availabilityKey]
            : true;

        if (! $isAvailable) {
            return false;
        }

        $teacherDayKey = $this->teacherDayKey($teacherId, $day);
        $teacherDayLoad = (int) ($state['teacher_day_load'][$teacherDayKey] ?? 0);
        $teacherWeekLoad = (int) ($state['teacher_week_load'][$teacherId] ?? 0);

        if ($teacherDayLoad >= (int) $context['max_teacher_day']) {
            return false;
        }

        return $teacherWeekLoad < (int) $context['max_teacher_week'];
    }

    private function findAvailableRoom(array $task, string $day, int $slotIndex, array $state, array $context): ?array
    {
        $requiredType = $task['required_room_type'];

        if ($requiredType !== null) {
            $pool = $context['rooms_by_type'][$requiredType] ?? [];
            foreach ($pool as $room) {
                if (($state['room_slot'][$this->roomSlotKey($room['id'], $day, $slotIndex)] ?? false) !== true) {
                    return $room;
                }
            }

            return null;
        }

        $classroomPool = $context['rooms_by_type']['classroom'] ?? [];
        foreach ($classroomPool as $room) {
            if (($state['room_slot'][$this->roomSlotKey($room['id'], $day, $slotIndex)] ?? false) !== true) {
                return $room;
            }
        }

        foreach ($context['rooms'] as $room) {
            if (($state['room_slot'][$this->roomSlotKey($room['id'], $day, $slotIndex)] ?? false) !== true) {
                return $room;
            }
        }

        return null;
    }

    private function scoreCandidate(array $task, string $day, int $slotIndex, int $teacherId, array $room, array $state): int
    {
        $score = 100;
        $classSectionId = (int) $task['class_section_id'];
        $subjectId = (int) $task['subject_id'];

        $classSubjectDayKey = $this->classSubjectDayKey($classSectionId, $subjectId, $day);
        $sameSubjectToday = (int) ($state['class_subject_day'][$classSubjectDayKey] ?? 0);
        $score -= $sameSubjectToday * 12;

        $prevKey = $this->classSlotSubjectKey($classSectionId, $day, $slotIndex - 1);
        $nextKey = $this->classSlotSubjectKey($classSectionId, $day, $slotIndex + 1);
        if (($state['class_slot_subject'][$prevKey] ?? null) === $subjectId) {
            $score -= 25;
        }
        if (($state['class_slot_subject'][$nextKey] ?? null) === $subjectId) {
            $score -= 25;
        }

        if ((bool) $task['is_core']) {
            if ($slotIndex <= 2) {
                $score += 18;
            } elseif ($slotIndex <= 4) {
                $score += 8;
            } else {
                $score -= 6;
            }
        }

        $teacherWeekLoad = (int) ($state['teacher_week_load'][$teacherId] ?? 0);
        $teacherDayLoad = (int) ($state['teacher_day_load'][$this->teacherDayKey($teacherId, $day)] ?? 0);
        $score += max(0, 12 - $teacherWeekLoad);
        $score += max(0, 4 - $teacherDayLoad);

        if (($room['type'] ?? '') === 'classroom' && $task['required_room_type'] === null) {
            $score += 2;
        }

        return $score;
    }

    private function attemptBacktrack(
        array &$decisionStack,
        array &$pendingTaskIds,
        array $tasksById,
        array &$assignments,
        array &$ruleScheduled,
        array &$state,
        array $context,
        LoggerInterface $logger
    ): bool {
        while (! empty($decisionStack)) {
            $decision = array_pop($decisionStack);
            $taskId = (int) $decision['task_id'];
            $task = $tasksById[$taskId] ?? null;
            if (! $task) {
                continue;
            }

            $currentCandidate = $decision['candidates'][$decision['candidate_index']] ?? null;
            if ($currentCandidate) {
                $this->undoCandidate($task, $currentCandidate, $assignments, $ruleScheduled, $state);
                if (! in_array($taskId, $pendingTaskIds, true)) {
                    $pendingTaskIds[] = $taskId;
                }
            }

            for ($nextIndex = $decision['candidate_index'] + 1; $nextIndex < count($decision['candidates']); $nextIndex++) {
                $candidate = $decision['candidates'][$nextIndex];
                if (! $this->isCandidateStillValid($task, $candidate, $state, $context)) {
                    continue;
                }

                $this->applyCandidate($task, $candidate, $assignments, $ruleScheduled, $state);
                $pendingTaskIds = array_values(array_filter(
                    $pendingTaskIds,
                    fn ($id): bool => $id !== $taskId
                ));

                $decisionStack[] = [
                    'task_id' => $taskId,
                    'candidates' => $decision['candidates'],
                    'candidate_index' => $nextIndex,
                ];

                $this->log($logger, 'backtrack_reassignment', [
                    'task_id' => $taskId,
                    'rule_id' => $task['rule_id'],
                    'class_section_id' => $task['class_section_id'],
                    'subject_id' => $task['subject_id'],
                    'teacher_id' => $candidate['teacher_id'],
                    'room_id' => $candidate['room_id'],
                    'day_of_week' => $candidate['day_of_week'],
                    'slot_index' => $candidate['slot_index'],
                    'score' => $candidate['score'],
                ]);

                return true;
            }
        }

        return false;
    }

    private function isCandidateStillValid(array $task, array $candidate, array $state, array $context): bool
    {
        $day = $candidate['day_of_week'];
        $slotIndex = (int) $candidate['slot_index'];
        $teacherId = (int) $candidate['teacher_id'];
        $roomId = (int) $candidate['room_id'];

        if (! $this->canPlaceClass((int) $task['class_section_id'], $day, $slotIndex, $state, $context)) {
            return false;
        }

        if (! $this->canPlaceTeacher($teacherId, $day, $slotIndex, $state, $context)) {
            return false;
        }

        $roomKey = $this->roomSlotKey($roomId, $day, $slotIndex);

        return ($state['room_slot'][$roomKey] ?? false) !== true;
    }

    private function applyCandidate(array $task, array $candidate, array &$assignments, array &$ruleScheduled, array &$state): void
    {
        $taskId = (int) $task['task_id'];
        $classSectionId = (int) $task['class_section_id'];
        $subjectId = (int) $task['subject_id'];
        $teacherId = (int) $candidate['teacher_id'];
        $roomId = (int) $candidate['room_id'];
        $day = $candidate['day_of_week'];
        $slot = (int) $candidate['slot_index'];

        $classSlotKey = $this->classSlotKey($classSectionId, $day, $slot);
        $teacherSlotKey = $this->teacherSlotKey($teacherId, $day, $slot);
        $roomSlotKey = $this->roomSlotKey($roomId, $day, $slot);
        $teacherDayKey = $this->teacherDayKey($teacherId, $day);
        $classDayKey = $this->classDayKey($classSectionId, $day);
        $classSubjectDayKey = $this->classSubjectDayKey($classSectionId, $subjectId, $day);
        $classSlotSubjectKey = $this->classSlotSubjectKey($classSectionId, $day, $slot);

        $state['class_slot'][$classSlotKey] = true;
        $state['teacher_slot'][$teacherSlotKey] = true;
        $state['room_slot'][$roomSlotKey] = true;
        $state['teacher_day_load'][$teacherDayKey] = ($state['teacher_day_load'][$teacherDayKey] ?? 0) + 1;
        $state['teacher_week_load'][$teacherId] = ($state['teacher_week_load'][$teacherId] ?? 0) + 1;
        $state['class_day_load'][$classDayKey] = ($state['class_day_load'][$classDayKey] ?? 0) + 1;
        $state['class_subject_day'][$classSubjectDayKey] = ($state['class_subject_day'][$classSubjectDayKey] ?? 0) + 1;
        $state['class_slot_subject'][$classSlotSubjectKey] = $subjectId;

        $ruleId = (int) $task['rule_id'];
        $ruleScheduled[$ruleId] = ($ruleScheduled[$ruleId] ?? 0) + 1;
        $assignments[$taskId] = $candidate;
    }

    private function undoCandidate(array $task, array $candidate, array &$assignments, array &$ruleScheduled, array &$state): void
    {
        $taskId = (int) $task['task_id'];
        $classSectionId = (int) $task['class_section_id'];
        $subjectId = (int) $task['subject_id'];
        $teacherId = (int) $candidate['teacher_id'];
        $roomId = (int) $candidate['room_id'];
        $day = $candidate['day_of_week'];
        $slot = (int) $candidate['slot_index'];

        $classSlotKey = $this->classSlotKey($classSectionId, $day, $slot);
        $teacherSlotKey = $this->teacherSlotKey($teacherId, $day, $slot);
        $roomSlotKey = $this->roomSlotKey($roomId, $day, $slot);
        $teacherDayKey = $this->teacherDayKey($teacherId, $day);
        $classDayKey = $this->classDayKey($classSectionId, $day);
        $classSubjectDayKey = $this->classSubjectDayKey($classSectionId, $subjectId, $day);
        $classSlotSubjectKey = $this->classSlotSubjectKey($classSectionId, $day, $slot);

        unset($state['class_slot'][$classSlotKey], $state['teacher_slot'][$teacherSlotKey], $state['room_slot'][$roomSlotKey]);
        unset($state['class_slot_subject'][$classSlotSubjectKey], $assignments[$taskId]);

        $state['teacher_day_load'][$teacherDayKey] = max(0, (int) ($state['teacher_day_load'][$teacherDayKey] ?? 0) - 1);
        $state['teacher_week_load'][$teacherId] = max(0, (int) ($state['teacher_week_load'][$teacherId] ?? 0) - 1);
        $state['class_day_load'][$classDayKey] = max(0, (int) ($state['class_day_load'][$classDayKey] ?? 0) - 1);
        $state['class_subject_day'][$classSubjectDayKey] = max(0, (int) ($state['class_subject_day'][$classSubjectDayKey] ?? 0) - 1);

        if (($state['teacher_day_load'][$teacherDayKey] ?? 0) === 0) {
            unset($state['teacher_day_load'][$teacherDayKey]);
        }
        if (($state['teacher_week_load'][$teacherId] ?? 0) === 0) {
            unset($state['teacher_week_load'][$teacherId]);
        }
        if (($state['class_day_load'][$classDayKey] ?? 0) === 0) {
            unset($state['class_day_load'][$classDayKey]);
        }
        if (($state['class_subject_day'][$classSubjectDayKey] ?? 0) === 0) {
            unset($state['class_subject_day'][$classSubjectDayKey]);
        }

        $ruleId = (int) $task['rule_id'];
        $ruleScheduled[$ruleId] = max(0, (int) ($ruleScheduled[$ruleId] ?? 0) - 1);
    }

    private function isCoreSubject(string $subjectName): bool
    {
        $name = Str::lower($subjectName);

        return Str::contains($name, ['mathematics', 'math', 'english']);
    }

    private function requiredRoomType(string $subjectName): ?string
    {
        $name = Str::lower($subjectName);

        if (Str::contains($name, ['physics', 'chemistry', 'biology', 'computer', 'lab', 'practical'])) {
            return 'lab';
        }

        return null;
    }

    private function classSectionLabel(?ClassSection $classSection): string
    {
        if (! $classSection) {
            return 'Class Section';
        }

        $className = trim(($classSection->classRoom?->name ?? 'Class').' '.($classSection->classRoom?->section ?? ''));

        return trim($className.' - '.($classSection->section_name ?? 'Section'));
    }

    private function addConflict(array &$conflicts, array &$seen, string $code, string $message, array $context): void
    {
        if (count($conflicts) >= self::MAX_CONFLICT_ITEMS) {
            return;
        }

        $key = $code.'|'.md5($message.json_encode($context));
        if (isset($seen[$key])) {
            return;
        }

        $seen[$key] = true;
        $conflicts[] = [
            'code' => $code,
            'message' => $message,
            'context' => $context,
        ];
    }

    private function log(LoggerInterface $logger, string $event, array $context = []): void
    {
        $logger->info($event, $context);
    }

    private function logger(): LoggerInterface
    {
        return Log::build([
            'driver' => 'single',
            'path' => storage_path('logs/timetable.log'),
        ]);
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

    private function classSlotKey(int $classSectionId, string $day, int $slot): string
    {
        return $classSectionId.'|'.$day.'|'.$slot;
    }

    private function teacherSlotKey(int $teacherId, string $day, int $slot): string
    {
        return $teacherId.'|'.$day.'|'.$slot;
    }

    private function roomSlotKey(int $roomId, string $day, int $slot): string
    {
        return $roomId.'|'.$day.'|'.$slot;
    }

    private function teacherDayKey(int $teacherId, string $day): string
    {
        return $teacherId.'|'.$day;
    }

    private function classDayKey(int $classSectionId, string $day): string
    {
        return $classSectionId.'|'.$day;
    }

    private function classSubjectDayKey(int $classSectionId, int $subjectId, string $day): string
    {
        return $classSectionId.'|'.$subjectId.'|'.$day;
    }

    private function classSlotSubjectKey(int $classSectionId, string $day, int $slot): string
    {
        return $classSectionId.'|'.$day.'|'.$slot;
    }
}
