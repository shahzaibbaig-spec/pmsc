<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('teacher_cgpa_rankings')) {
            return;
        }

        Schema::create('teacher_cgpa_rankings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->string('session', 20);
            $table->string('exam_type', 30)->nullable();
            $table->foreignId('class_id')->nullable()->constrained('school_classes')->nullOnDelete();
            $table->decimal('average_percentage', 5, 2)->default(0);
            $table->decimal('pass_percentage', 5, 2)->default(0);
            $table->decimal('cgpa', 4, 2)->default(0);
            $table->integer('student_count')->default(0);
            $table->integer('rank_position')->nullable();
            $table->enum('ranking_scope', ['classwise', 'overall'])->default('classwise');
            $table->timestamps();

            $table->index(['session', 'exam_type', 'ranking_scope'], 'teacher_cgpa_rankings_session_exam_scope_index');
            $table->index(['session', 'exam_type', 'class_id'], 'teacher_cgpa_rankings_session_exam_class_index');
            $table->index(['teacher_id', 'session'], 'teacher_cgpa_rankings_teacher_session_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_cgpa_rankings');
    }
};
