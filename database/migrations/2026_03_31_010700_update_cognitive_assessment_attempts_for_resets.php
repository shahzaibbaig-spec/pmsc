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

        try {
            Schema::table('cognitive_assessment_attempts', function (Blueprint $table): void {
                $table->dropUnique('cognitive_assessment_attempts_assessment_student_unique');
            });
        } catch (\Throwable) {
            // Some imported databases keep this unique key bound to an FK; keep it if it cannot be dropped safely.
        }

        try {
            Schema::table('cognitive_assessment_attempts', function (Blueprint $table): void {
                $table->index(
                    ['assessment_id', 'student_id'],
                    'cognitive_assessment_attempts_assessment_student_index'
                );
            });
        } catch (\Throwable) {
            // Index may already exist after partial migration runs.
        }
    }

    public function down(): void
    {
        try {
            Schema::table('cognitive_assessment_attempts', function (Blueprint $table): void {
                $table->dropIndex('cognitive_assessment_attempts_assessment_student_index');
            });
        } catch (\Throwable) {
            // Index may be absent in partially applied environments.
        }

        Schema::table('cognitive_assessment_attempts', function (Blueprint $table): void {
            $table->enum('status', ['not_started', 'in_progress', 'submitted', 'auto_submitted', 'graded'])
                ->default('not_started')
                ->change();
        });

        try {
            Schema::table('cognitive_assessment_attempts', function (Blueprint $table): void {
                $table->unique(
                    ['assessment_id', 'student_id'],
                    'cognitive_assessment_attempts_assessment_student_unique'
                );
            });
        } catch (\Throwable) {
            // Unique key may already exist.
        }
    }
};
