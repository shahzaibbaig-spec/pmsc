<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificate extends Model
{
    public const TYPE_MERIT = 'certificate_of_merit';

    protected $fillable = [
        'student_id',
        'certificate_type',
        'title',
        'reason',
        'class_name',
        'section_name',
        'issue_date',
        'certificate_no',
        'chairman_name',
        'principal_name',
        'chairman_title',
        'principal_title',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'student_id' => 'integer',
            'issue_date' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
