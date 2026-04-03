<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GradeScale extends Model
{
    protected $fillable = [
        'grade_code',
        'label',
        'percentage_equivalent',
        'grade_point',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'percentage_equivalent' => 'decimal:2',
            'grade_point' => 'decimal:2',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
