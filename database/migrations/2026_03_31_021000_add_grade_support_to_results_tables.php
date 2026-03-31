<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table): void {
            $table->unsignedInteger('total_marks')->nullable()->change();
        });

        $marksHasGrade = Schema::hasColumn('marks', 'grade');
        Schema::table('marks', function (Blueprint $table): void {
            $table->unsignedInteger('obtained_marks')->nullable()->change();
            $table->unsignedInteger('total_marks')->nullable()->change();

            if (! $marksHasGrade) {
                $table->string('grade', 10)->nullable()->after('obtained_marks');
            }
        });

        $studentResultsHasGrade = Schema::hasColumn('student_results', 'grade');
        Schema::table('student_results', function (Blueprint $table): void {
            $table->unsignedInteger('total_marks')->nullable()->change();
            $table->unsignedInteger('obtained_marks')->nullable()->change();

            if (! $studentResultsHasGrade) {
                $table->string('grade', 10)->nullable()->after('obtained_marks');
            }
        });

        $markEditLogsHasOldGrade = Schema::hasColumn('mark_edit_logs', 'old_grade');
        $markEditLogsHasNewGrade = Schema::hasColumn('mark_edit_logs', 'new_grade');
        Schema::table('mark_edit_logs', function (Blueprint $table): void {
            if (! $markEditLogsHasOldGrade) {
                $table->string('old_grade', 10)->nullable()->after('new_marks');
            }

            if (! $markEditLogsHasNewGrade) {
                $table->string('new_grade', 10)->nullable()->after($markEditLogsHasOldGrade ? 'old_grade' : 'new_marks');
            }
        });
    }

    public function down(): void
    {
        Schema::table('mark_edit_logs', function (Blueprint $table): void {
            $columns = array_values(array_filter([
                Schema::hasColumn('mark_edit_logs', 'old_grade') ? 'old_grade' : null,
                Schema::hasColumn('mark_edit_logs', 'new_grade') ? 'new_grade' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });

        Schema::table('student_results', function (Blueprint $table): void {
            if (Schema::hasColumn('student_results', 'grade')) {
                $table->dropColumn('grade');
            }
            $table->unsignedInteger('obtained_marks')->nullable(false)->change();
            $table->unsignedInteger('total_marks')->nullable(false)->change();
        });

        Schema::table('marks', function (Blueprint $table): void {
            if (Schema::hasColumn('marks', 'grade')) {
                $table->dropColumn('grade');
            }
            $table->unsignedInteger('obtained_marks')->nullable(false)->change();
            $table->unsignedInteger('total_marks')->nullable(false)->change();
        });

        Schema::table('exams', function (Blueprint $table): void {
            $table->unsignedInteger('total_marks')->nullable(false)->change();
        });
    }
};
