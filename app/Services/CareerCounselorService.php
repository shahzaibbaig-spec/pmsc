<?php

namespace App\Services;

use App\Models\CareerCounselingSession;
use App\Models\CareerProfile;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Notifications\CareerCounselorPrincipalNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CareerCounselorService
{
    public function dashboardStats(User $counselor): array
    {
        return [
            'session' => $this->currentSession(),
            'total_students' => $this->eligibleStudentsQuery()->count(),
            'total_profiles' => CareerProfile::query()->where('session', $this->currentSession())->count(),
            'total_sessions' => CareerCounselingSession::query()
                ->where('counselor_id', $counselor->id)
                ->where('session', $this->currentSession())
                ->count(),
            'recent_sessions' => CareerCounselingSession::query()
                ->with(['student.classRoom'])
                ->where('counselor_id', $counselor->id)
                ->orderByDesc('counseling_date')
                ->orderByDesc('id')
                ->limit(5)
                ->get(),
            'urgent_cases' => $this->getUrgentCases()->take(5),
            'missed_followups' => $this->getMissedFollowUps($counselor)->take(5),
        ];
    }

    public function searchStudents(string $term): Collection
    {
        $needle = trim($term);
        if (mb_strlen($needle) < 2) {
            return collect();
        }

        $like = '%'.$needle.'%';

        return $this->eligibleStudentsQuery()
            ->where(function (Builder $query) use ($like): void {
                $query->where('name', 'like', $like)
                    ->orWhere('student_id', 'like', $like)
                    ->orWhere('father_name', 'like', $like)
                    ->orWhereHas('classRoom', function (Builder $classQuery) use ($like): void {
                        $classQuery->where('name', 'like', $like)
                            ->orWhere('section', 'like', $like);
                    });
            })
            ->with('classRoom:id,name,section')
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'student_id', 'name', 'father_name', 'class_id'])
            ->map(fn (Student $student): array => $this->studentSearchRow($student));
    }

    public function createOrUpdateCareerProfile(Student $student, array $data, User $counselor): CareerProfile
    {
        $this->ensureEligibleStudent($student);
        $session = $this->currentSession();

        return DB::transaction(function () use ($student, $data, $counselor, $session): CareerProfile {
            $profile = CareerProfile::query()->firstOrNew([
                'student_id' => $student->id,
                'session' => $session,
            ]);

            $profile->fill($this->onlyProfileFields($data));
            $profile->current_class_id = $student->class_id;
            $profile->updated_by = $counselor->id;

            if (! $profile->exists) {
                $profile->created_by = $counselor->id;
            }

            $profile->save();

            return $profile->fresh(['student.classRoom', 'currentClass', 'createdBy', 'updatedBy']);
        });
    }

    public function createCounselingSession(Student $student, array $data, User $counselor): CareerCounselingSession
    {
        $this->ensureEligibleStudent($student);
        $session = $this->currentSession();

        return DB::transaction(function () use ($student, $data, $counselor, $session): CareerCounselingSession {
            $profile = CareerProfile::query()
                ->where('student_id', $student->id)
                ->where('session', $session)
                ->first();

            return CareerCounselingSession::query()->create([
                ...$this->onlySessionFields($data),
                'student_id' => $student->id,
                'career_profile_id' => $profile?->id,
                'counselor_id' => $counselor->id,
                'session' => $session,
                'status' => 'completed',
                'visibility' => $data['visibility'] ?? 'private',
                'public_summary' => $data['public_summary'] ?? null,
                'created_by' => $counselor->id,
                'updated_by' => $counselor->id,
            ])->fresh(['student.classRoom', 'careerProfile', 'counselor']);
        });
    }

    public function markUrgentGuidance(CareerCounselingSession $session, User $counselor, ?string $reason = null): CareerCounselingSession
    {
        return DB::transaction(function () use ($session, $counselor, $reason): CareerCounselingSession {
            $session->forceFill([
                'urgent_guidance_required' => true,
                'urgent_reason' => $reason,
                'urgent_marked_at' => now(),
                'urgent_marked_by' => $counselor->id,
                'updated_by' => $counselor->id,
            ])->save();

            $fresh = $session->fresh(['student.classRoom', 'counselor']);
            $this->notifyPrincipals($fresh->student, $counselor, $reason ?: 'Urgent guidance required.', $fresh);

            return $fresh;
        });
    }

    public function unmarkUrgentGuidance(CareerCounselingSession $session, User $counselor): CareerCounselingSession
    {
        return DB::transaction(function () use ($session, $counselor): CareerCounselingSession {
            $session->forceFill([
                'urgent_guidance_required' => false,
                'updated_by' => $counselor->id,
            ])->save();

            return $session->fresh(['student.classRoom', 'counselor']);
        });
    }

    public function updateVisibility(CareerCounselingSession $session, array $data, User $counselor): CareerCounselingSession
    {
        return DB::transaction(function () use ($session, $data, $counselor): CareerCounselingSession {
            $session->forceFill([
                'visibility' => $data['visibility'] ?? 'private',
                'public_summary' => $data['public_summary'] ?? null,
                'updated_by' => $counselor->id,
            ])->save();

            if (($data['visibility'] ?? 'private') !== 'private' && trim((string) ($data['public_summary'] ?? '')) !== '') {
                $this->notifyPrincipals($session->student, $counselor, 'New important career recommendation added.', $session);
            }

            return $session->fresh(['student.classRoom', 'counselor']);
        });
    }

    public function notifyPrincipals(Student $student, User $counselor, string $reason, ?CareerCounselingSession $session = null): void
    {
        $student->loadMissing('classRoom');

        User::role(['Principal', 'Admin'])
            ->get()
            ->each(fn (User $user) => $user->notify(new CareerCounselorPrincipalNotification($student, $counselor, $reason, $session)));
    }

    public function getUrgentCases(): Collection
    {
        return CareerCounselingSession::query()
            ->with(['student.classRoom', 'counselor', 'urgentMarkedBy'])
            ->where('urgent_guidance_required', true)
            ->orderByDesc('urgent_marked_at')
            ->get();
    }

    public function getMissedFollowUps(?User $counselor = null): Collection
    {
        return CareerCounselingSession::query()
            ->with(['student.classRoom', 'counselor'])
            ->where('follow_up_required', true)
            ->where('status', 'completed')
            ->whereDate('follow_up_date', '<', now()->toDateString())
            ->when($counselor, fn (Builder $query) => $query->where('counselor_id', $counselor->id))
            ->orderBy('follow_up_date')
            ->get();
    }

    public function getPrincipalRecords(array $filters = []): array
    {
        return [
            'profiles' => $this->profileRecords($filters),
            'sessions' => $this->sessionRecords($filters),
            'classes' => SchoolClass::query()->orderBy('name')->orderBy('section')->get(['id', 'name', 'section']),
            'counselors' => User::role('Career Counselor')->orderBy('name')->get(['id', 'name', 'email']),
            'filters' => $filters,
        ];
    }

    public function currentSession(): string
    {
        $now = now();
        $startYear = $now->month >= 7 ? $now->year : ($now->year - 1);

        return $startYear.'-'.($startYear + 1);
    }

    public function eligibleStudentsQuery(): Builder
    {
        return Student::query()
            ->with('classRoom:id,name,section')
            ->where('status', 'active')
            ->whereHas('classRoom', function (Builder $query): void {
                $query->where(function (Builder $gradeQuery): void {
                    foreach (range(7, 12) as $grade) {
                        $gradeQuery->orWhere('name', (string) $grade)
                            ->orWhere('name', 'like', 'Class '.$grade.'%')
                            ->orWhere('name', 'like', 'Grade '.$grade.'%')
                            ->orWhere('name', 'like', $grade.'%');
                    }
                });
            });
    }

    private function profileRecords(array $filters): LengthAwarePaginator
    {
        return CareerProfile::query()
            ->with(['student.classRoom', 'currentClass', 'createdBy', 'updatedBy'])
            ->when($filters['student'] ?? null, function (Builder $query, string $student): void {
                $like = '%'.trim($student).'%';
                $query->whereHas('student', fn (Builder $studentQuery) => $studentQuery
                    ->where('name', 'like', $like)
                    ->orWhere('student_id', 'like', $like)
                    ->orWhere('father_name', 'like', $like));
            })
            ->when($filters['class_id'] ?? null, fn (Builder $query, string $classId) => $query->where('current_class_id', $classId))
            ->when($filters['session'] ?? null, fn (Builder $query, string $session) => $query->where('session', $session))
            ->orderByDesc('session')
            ->orderByDesc('id')
            ->paginate(20, ['*'], 'profiles_page')
            ->withQueryString();
    }

    private function sessionRecords(array $filters): LengthAwarePaginator
    {
        return CareerCounselingSession::query()
            ->with(['student.classRoom', 'careerProfile', 'counselor'])
            ->when($filters['student'] ?? null, function (Builder $query, string $student): void {
                $like = '%'.trim($student).'%';
                $query->whereHas('student', fn (Builder $studentQuery) => $studentQuery
                    ->where('name', 'like', $like)
                    ->orWhere('student_id', 'like', $like)
                    ->orWhere('father_name', 'like', $like));
            })
            ->when($filters['class_id'] ?? null, fn (Builder $query, string $classId) => $query->whereHas('student', fn (Builder $studentQuery) => $studentQuery->where('class_id', $classId)))
            ->when($filters['counselor_id'] ?? null, fn (Builder $query, string $counselorId) => $query->where('counselor_id', $counselorId))
            ->when($filters['session'] ?? null, fn (Builder $query, string $session) => $query->where('session', $session))
            ->when($filters['urgent'] ?? null, fn (Builder $query) => $query->where('urgent_guidance_required', true))
            ->orderByDesc('counseling_date')
            ->orderByDesc('id')
            ->paginate(20, ['*'], 'sessions_page')
            ->withQueryString();
    }

    private function ensureEligibleStudent(Student $student): void
    {
        $className = (string) ($student->classRoom?->name ?? '');
        if (! preg_match('/(^|[^0-9])0?(7|8|9|10|11|12)([^0-9]|$)/i', $className)) {
            throw ValidationException::withMessages([
                'student_id' => 'Career Counselor records are only available for Grade 7 to Grade 12 students.',
            ]);
        }
    }

    private function onlyProfileFields(array $data): array
    {
        return collect($data)->only([
            'strengths',
            'weaknesses',
            'interests',
            'preferred_subjects',
            'career_goals',
            'parent_expectations',
            'recommended_career_paths',
            'counselor_notes',
        ])->all();
    }

    private function onlySessionFields(array $data): array
    {
        return collect($data)->only([
            'counseling_date',
            'discussion_topic',
            'student_interests',
            'academic_concerns',
            'recommended_subjects',
            'recommended_career_path',
            'counselor_advice',
            'private_notes',
            'visibility',
            'public_summary',
        ])->all();
    }

    private function studentSearchRow(Student $student): array
    {
        $className = trim((string) ($student->classRoom?->name ?? '').' '.(string) ($student->classRoom?->section ?? ''));

        return [
            'id' => (int) $student->id,
            'student_id' => (string) $student->student_id,
            'name' => (string) $student->name,
            'admission_number' => (string) $student->student_id,
            'roll_number' => (string) $student->student_id,
            'class_section' => $className,
            'father_name' => (string) ($student->father_name ?? ''),
        ];
    }
}
