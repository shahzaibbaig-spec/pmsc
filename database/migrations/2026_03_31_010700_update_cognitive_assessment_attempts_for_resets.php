<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cognitive_assessment_attempts', function (Blueprint $table): void {
            $table->enum('status', ['not_started', 'in_progress', 'submitted', 'auto_submitted', 'graded', 'reset'])
                ->default('not_started')
                ->change();
        });

        Schema::table('cognitive_assessment_attempts', function (Blueprint $table): void {
            $table->dropUnique('cognitive_assessment_attempts_assessment_student_unique');
            $table->index(
                ['assessment_id', 'student_id'],
                'cognitive_assessment_attempts_assessment_student_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('cognitive_assessment_attempts', function (Blueprint $table): void {
            $table->dropIndex('cognitive_assessment_attempts_assessment_student_index');
            $table->enum('status', ['not_started', 'in_progress', 'submitted', 'auto_submitted', 'graded'])
                ->default('not_started')
                ->change();
            $table->unique(
                ['assessment_id', 'student_id'],
                'cognitive_assessment_attempts_assessment_student_unique'
            );
        });
    }
};
