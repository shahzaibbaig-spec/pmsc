<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('teacher_cgpa_rankings') || Schema::hasColumn('teacher_cgpa_rankings', 'ranking_group')) {
            return;
        }

        Schema::table('teacher_cgpa_rankings', function (Blueprint $table): void {
            $table->string('ranking_group', 30)
                ->default('middle_school')
                ->after('ranking_scope');

            $table->index(
                ['session', 'exam_type', 'ranking_group', 'ranking_scope'],
                'teacher_cgpa_rankings_session_exam_group_scope_index'
            );
            $table->index(
                ['session', 'exam_type', 'ranking_group', 'class_id'],
                'teacher_cgpa_rankings_session_exam_group_class_index'
            );
            $table->index(['teacher_id', 'session', 'ranking_group'], 'teacher_cgpa_rankings_teacher_session_group_index');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('teacher_cgpa_rankings') || ! Schema::hasColumn('teacher_cgpa_rankings', 'ranking_group')) {
            return;
        }

        Schema::table('teacher_cgpa_rankings', function (Blueprint $table): void {
            $table->dropIndex('teacher_cgpa_rankings_session_exam_group_scope_index');
            $table->dropIndex('teacher_cgpa_rankings_session_exam_group_class_index');
            $table->dropIndex('teacher_cgpa_rankings_teacher_session_group_index');
            $table->dropColumn('ranking_group');
        });
    }
};
