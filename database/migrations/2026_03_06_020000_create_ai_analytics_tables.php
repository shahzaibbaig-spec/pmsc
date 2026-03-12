<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_performance_features', function (Blueprint $table): void {
            $table->id();
            $table->string('session', 20);
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->float('attendance_rate')->default(0);
            $table->float('avg_class_test')->nullable();
            $table->float('avg_bimonthly')->nullable();
            $table->float('avg_first_term')->nullable();
            $table->float('trend_slope')->default(0);
            $table->float('last_assessment_score')->nullable();
            $table->timestamps();

            $table->unique(['session', 'student_id'], 'spf_session_student_unique');
            $table->index(['session']);
            $table->index(['student_id']);
        });

        Schema::create('student_risk_predictions', function (Blueprint $table): void {
            $table->id();
            $table->string('session', 20);
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->enum('target_exam', ['first_term', 'final_term']);
            $table->float('predicted_percentage');
            $table->enum('risk_level', ['low', 'medium', 'high']);
            $table->float('confidence');
            $table->json('explanation')->nullable();
            $table->timestamps();

            $table->unique(['session', 'student_id', 'target_exam'], 'srp_session_student_exam_unique');
            $table->index(['session', 'target_exam']);
            $table->index(['risk_level']);
            $table->index(['student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_risk_predictions');
        Schema::dropIfExists('student_performance_features');
    }
};
