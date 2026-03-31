<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cognitive_assessment_attempts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('assessment_id')->constrained('cognitive_assessments')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->enum('status', ['not_started', 'in_progress', 'submitted', 'auto_submitted', 'graded'])->default('not_started');
            $table->unsignedInteger('verbal_score')->nullable();
            $table->unsignedInteger('non_verbal_score')->nullable();
            $table->unsignedInteger('quantitative_score')->nullable();
            $table->unsignedInteger('spatial_score')->nullable();
            $table->unsignedInteger('overall_score')->nullable();
            $table->decimal('overall_percentage', 5, 2)->nullable();
            $table->string('performance_band', 50)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['assessment_id', 'student_id'], 'cognitive_assessment_attempts_assessment_student_unique');
            $table->index(['status', 'expires_at']);
            $table->index(['student_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cognitive_assessment_attempts');
    }
};
