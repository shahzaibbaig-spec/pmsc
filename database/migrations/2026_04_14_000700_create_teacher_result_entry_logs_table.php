<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('teacher_result_entry_logs')) {
            return;
        }

        Schema::create('teacher_result_entry_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->string('session', 20);
            $table->string('exam_type', 30);
            $table->decimal('old_marks', 8, 2)->nullable();
            $table->decimal('new_marks', 8, 2)->nullable();
            $table->string('old_grade', 10)->nullable();
            $table->string('new_grade', 10)->nullable();
            $table->enum('action_type', ['created', 'updated', 'deleted']);
            $table->timestamp('action_at');
            $table->foreignId('acted_by')->constrained('users')->cascadeOnDelete();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['teacher_id', 'session', 'exam_type'], 'trel_teacher_session_exam_idx');
            $table->index(['class_id', 'subject_id', 'session', 'exam_type'], 'trel_class_subject_scope_idx');
            $table->index(['action_type', 'action_at'], 'trel_action_type_time_idx');
            $table->index(['acted_by', 'action_at'], 'trel_actor_time_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_result_entry_logs');
    }
};

