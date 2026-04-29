<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('career_profiles', function (Blueprint $table): void {
            if (! Schema::hasColumn('career_profiles', 'visibility')) {
                $table->string('visibility', 30)->default('private')->after('counselor_notes');
                $table->text('public_summary')->nullable()->after('visibility');
            }
        });

        Schema::table('career_counseling_sessions', function (Blueprint $table): void {
            if (! Schema::hasColumn('career_counseling_sessions', 'urgent_guidance_required')) {
                $table->boolean('urgent_guidance_required')->default(false)->after('status');
                $table->text('urgent_reason')->nullable()->after('urgent_guidance_required');
                $table->timestamp('urgent_marked_at')->nullable()->after('urgent_reason');
                $table->foreignId('urgent_marked_by')->nullable()->after('urgent_marked_at')->constrained('users')->nullOnDelete();
                $table->string('visibility', 30)->default('private')->after('urgent_marked_by');
                $table->text('public_summary')->nullable()->after('visibility');
            }
        });

        Schema::create('career_parent_meetings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('career_profile_id')->nullable()->constrained('career_profiles')->nullOnDelete();
            $table->foreignId('counseling_session_id')->nullable()->constrained('career_counseling_sessions')->nullOnDelete();
            $table->foreignId('counselor_id')->constrained('users')->cascadeOnDelete();
            $table->string('session', 20);
            $table->date('meeting_date')->nullable();
            $table->text('parent_concerns')->nullable();
            $table->text('parent_expectations')->nullable();
            $table->text('counselor_recommendation')->nullable();
            $table->text('agreed_action_plan')->nullable();
            $table->date('next_meeting_date')->nullable();
            $table->string('status', 30)->default('pending');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['student_id', 'session']);
            $table->index(['counselor_id', 'status']);
        });

        Schema::create('career_assessments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('counselor_id')->constrained('users')->cascadeOnDelete();
            $table->string('session', 20);
            $table->date('assessment_date');
            $table->string('title')->nullable();
            $table->text('overall_summary')->nullable();
            $table->string('recommended_stream')->nullable();
            $table->string('alternative_stream')->nullable();
            $table->text('suggested_subjects')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['student_id', 'session']);
            $table->index(['counselor_id', 'assessment_date']);
        });

        Schema::create('career_assessment_scores', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('career_assessment_id')->constrained('career_assessments')->cascadeOnDelete();
            $table->string('category', 80);
            $table->integer('score')->default(0);
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['career_assessment_id', 'category'], 'career_assessment_scores_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('career_assessment_scores');
        Schema::dropIfExists('career_assessments');
        Schema::dropIfExists('career_parent_meetings');
    }
};
