<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exams', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->enum('exam_type', ['class_test', 'bimonthly_test', 'first_term', 'final_term']);
            $table->string('session', 20);
            $table->unsignedInteger('total_marks');
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();

            $table->unique(['class_id', 'subject_id', 'exam_type', 'session']);
            $table->index(['teacher_id', 'session']);
            $table->index(['class_id', 'session']);
        });

        Schema::create('marks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->unsignedInteger('obtained_marks');
            $table->unsignedInteger('total_marks');
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->string('session', 20);
            $table->timestamps();

            $table->unique(['exam_id', 'student_id']);
            $table->index(['student_id', 'session']);
            $table->index(['teacher_id', 'session']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marks');
        Schema::dropIfExists('exams');
    }
};

