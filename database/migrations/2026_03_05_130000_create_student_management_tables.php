<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_classes', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('section')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });

        Schema::create('students', function (Blueprint $table): void {
            $table->id();
            $table->string('student_id')->unique();
            $table->string('name');
            $table->string('father_name')->nullable();
            $table->foreignId('class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->date('date_of_birth')->nullable();
            $table->unsignedTinyInteger('age')->nullable();
            $table->string('contact', 30)->nullable();
            $table->text('address')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['name', 'status']);
            $table->index(['father_name']);
            $table->index(['class_id']);
        });

        Schema::create('subjects', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });

        Schema::create('student_subject', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['student_id', 'subject_id']);
        });

        Schema::create('student_attendance', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->date('date');
            $table->string('status', 20);
            $table->string('remarks')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'date']);
        });

        Schema::create('student_results', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->string('exam_name');
            $table->unsignedInteger('total_marks');
            $table->unsignedInteger('obtained_marks');
            $table->date('result_date')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'result_date']);
        });

        Schema::create('medical_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->date('visit_date')->nullable();
            $table->text('details');
            $table->text('treatment')->nullable();
            $table->string('status', 30)->default('pending');
            $table->timestamps();

            $table->index(['student_id', 'visit_date']);
        });

        Schema::create('discipline_complaints', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->date('complaint_date')->nullable();
            $table->text('description');
            $table->text('action_taken')->nullable();
            $table->string('status', 20)->default('open');
            $table->timestamps();

            $table->index(['student_id', 'complaint_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discipline_complaints');
        Schema::dropIfExists('medical_histories');
        Schema::dropIfExists('student_results');
        Schema::dropIfExists('student_attendance');
        Schema::dropIfExists('student_subject');
        Schema::dropIfExists('subjects');
        Schema::dropIfExists('students');
        Schema::dropIfExists('school_classes');
    }
};

