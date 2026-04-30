<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kcat_tests', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('grade_from')->nullable();
            $table->integer('grade_to')->nullable();
            $table->integer('total_questions')->default(0);
            $table->integer('total_marks')->default(0);
            $table->integer('duration_minutes')->nullable();
            $table->string('status', 30)->default('draft');
            $table->string('session', 20)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('kcat_sections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('kcat_test_id')->constrained('kcat_tests')->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 80);
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->integer('total_questions')->default(0);
            $table->integer('total_marks')->default(0);
            $table->timestamps();
            $table->unique(['kcat_test_id', 'code']);
        });

        Schema::create('kcat_questions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('kcat_test_id')->constrained('kcat_tests')->cascadeOnDelete();
            $table->foreignId('kcat_section_id')->constrained('kcat_sections')->cascadeOnDelete();
            $table->string('question_type', 40)->default('mcq');
            $table->string('difficulty', 20)->default('medium');
            $table->text('question_text');
            $table->string('question_image')->nullable();
            $table->text('explanation')->nullable();
            $table->integer('marks')->default(1);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['kcat_test_id', 'kcat_section_id', 'is_active']);
        });

        Schema::create('kcat_question_options', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('kcat_question_id')->constrained('kcat_questions')->cascadeOnDelete();
            $table->text('option_text')->nullable();
            $table->string('option_image')->nullable();
            $table->boolean('is_correct')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('kcat_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('kcat_test_id')->constrained('kcat_tests')->cascadeOnDelete();
            $table->string('assigned_to_type', 20);
            $table->foreignId('student_id')->nullable()->constrained('students')->cascadeOnDelete();
            $table->foreignId('class_id')->nullable()->constrained('school_classes')->cascadeOnDelete();
            $table->string('section')->nullable();
            $table->string('session', 20);
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->date('due_date')->nullable();
            $table->string('status', 30)->default('assigned');
            $table->timestamps();
            $table->index(['student_id', 'session', 'status']);
            $table->index(['class_id', 'section', 'session']);
        });

        Schema::create('kcat_attempts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('kcat_assignment_id')->nullable()->constrained('kcat_assignments')->nullOnDelete();
            $table->foreignId('kcat_test_id')->constrained('kcat_tests')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('counselor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('session', 20);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->string('status', 30)->default('in_progress');
            $table->decimal('total_score', 8, 2)->nullable();
            $table->decimal('percentage', 5, 2)->nullable();
            $table->string('band', 40)->nullable();
            $table->string('recommended_stream')->nullable();
            $table->text('recommendation_summary')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['student_id', 'session', 'status']);
        });

        Schema::create('kcat_answers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('kcat_attempt_id')->constrained('kcat_attempts')->cascadeOnDelete();
            $table->foreignId('kcat_question_id')->constrained('kcat_questions')->cascadeOnDelete();
            $table->foreignId('selected_option_id')->nullable()->constrained('kcat_question_options')->nullOnDelete();
            $table->text('answer_text')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->decimal('marks_awarded', 8, 2)->default(0);
            $table->timestamps();
            $table->unique(['kcat_attempt_id', 'kcat_question_id']);
        });

        Schema::create('kcat_scores', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('kcat_attempt_id')->constrained('kcat_attempts')->cascadeOnDelete();
            $table->foreignId('kcat_section_id')->constrained('kcat_sections')->cascadeOnDelete();
            $table->string('section_code', 80);
            $table->decimal('raw_score', 8, 2)->default(0);
            $table->decimal('total_marks', 8, 2)->default(0);
            $table->decimal('percentage', 5, 2)->default(0);
            $table->string('band', 40)->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->unique(['kcat_attempt_id', 'kcat_section_id']);
        });

        Schema::create('kcat_report_notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('kcat_attempt_id')->constrained('kcat_attempts')->cascadeOnDelete();
            $table->foreignId('counselor_id')->constrained('users')->cascadeOnDelete();
            $table->text('strengths')->nullable();
            $table->text('development_areas')->nullable();
            $table->text('counselor_recommendation')->nullable();
            $table->text('parent_summary')->nullable();
            $table->text('private_notes')->nullable();
            $table->string('visibility', 30)->default('private');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kcat_report_notes');
        Schema::dropIfExists('kcat_scores');
        Schema::dropIfExists('kcat_answers');
        Schema::dropIfExists('kcat_attempts');
        Schema::dropIfExists('kcat_assignments');
        Schema::dropIfExists('kcat_question_options');
        Schema::dropIfExists('kcat_questions');
        Schema::dropIfExists('kcat_sections');
        Schema::dropIfExists('kcat_tests');
    }
};
