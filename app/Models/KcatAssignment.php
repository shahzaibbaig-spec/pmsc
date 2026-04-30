<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KcatAssignment extends Model
{
    protected $fillable = [
        'kcat_test_id', 'assigned_to_type', 'student_id', 'class_id', 'section', 'session',
        'assigned_by', 'assigned_at', 'due_date', 'status',
    ];

    protected function casts(): array
    {
        return ['assigned_at' => 'datetime', 'due_date' => 'date'];
    }

    public function test(): BelongsTo { return $this->belongsTo(KcatTest::class, 'kcat_test_id'); }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function classRoom(): BelongsTo { return $this->belongsTo(SchoolClass::class, 'class_id'); }
    public function assignedBy(): BelongsTo { return $this->belongsTo(User::class, 'assigned_by'); }
    public function attempts(): HasMany { return $this->hasMany(KcatAttempt::class); }
}
