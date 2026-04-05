<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherCgpaRanking extends Model
{
    public const SCOPE_CLASSWISE = 'classwise';

    public const SCOPE_OVERALL = 'overall';

    public const GROUP_EARLY_YEARS = 'early_years';

    public const GROUP_MIDDLE_SCHOOL = 'middle_school';

    public const GROUP_SENIOR_SCHOOL = 'senior_school';

    protected $fillable = [
        'teacher_id',
        'session',
        'exam_type',
        'class_id',
        'average_percentage',
        'pass_percentage',
        'cgpa',
        'student_count',
        'rank_position',
        'ranking_scope',
        'ranking_group',
    ];

    protected function casts(): array
    {
        return [
            'teacher_id' => 'integer',
            'class_id' => 'integer',
            'average_percentage' => 'decimal:2',
            'pass_percentage' => 'decimal:2',
            'cgpa' => 'decimal:2',
            'student_count' => 'integer',
            'rank_position' => 'integer',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }
}
