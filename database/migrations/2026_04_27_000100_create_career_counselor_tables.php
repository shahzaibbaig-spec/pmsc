<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('career_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('session', 20);
            $table->foreignId('current_class_id')->nullable()->constrained('school_classes')->nullOnDelete();
            $table->text('strengths')->nullable();
            $table->text('weaknesses')->nullable();
            $table->text('interests')->nullable();
            $table->text('preferred_subjects')->nullable();
            $table->text('career_goals')->nullable();
            $table->text('parent_expectations')->nullable();
            $table->text('recommended_career_paths')->nullable();
            $table->text('counselor_notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['student_id', 'session'], 'career_profiles_student_session_unique');
            $table->index(['session', 'current_class_id'], 'career_profiles_session_class_index');
        });

        Schema::create('career_counseling_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('career_profile_id')->nullable()->constrained('career_profiles')->nullOnDelete();
            $table->foreignId('counselor_id')->constrained('users')->cascadeOnDelete();
            $table->string('session', 20);
            $table->date('counseling_date');
            $table->string('discussion_topic')->nullable();
            $table->text('student_interests')->nullable();
            $table->text('academic_concerns')->nullable();
            $table->text('recommended_subjects')->nullable();
            $table->text('recommended_career_path')->nullable();
            $table->text('counselor_advice')->nullable();
            $table->text('private_notes')->nullable();
            $table->boolean('follow_up_required')->default(false);
            $table->date('follow_up_date')->nullable();
            $table->string('status', 30)->default('completed');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['session', 'counseling_date'], 'career_sessions_session_date_index');
            $table->index(['student_id', 'session'], 'career_sessions_student_session_index');
            $table->index(['counselor_id', 'follow_up_date'], 'career_sessions_counselor_followup_index');
            $table->index(['follow_up_required', 'status', 'follow_up_date'], 'career_sessions_followup_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('career_counseling_sessions');
        Schema::dropIfExists('career_profiles');
    }
};
