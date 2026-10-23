<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeniorProgressReportDraft extends Model
{
    protected $fillable = ['student_id', 'class_id', 'session', 'exam_id', 'content', 'updated_by'];

    protected function casts(): array
    {
        return ['content' => 'array'];
    }
}
