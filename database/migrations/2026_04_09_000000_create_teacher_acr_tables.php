<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('teacher_acrs')) {
            Schema::create('teacher_acrs', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
                $table->string('session', 20);
                $table->decimal('attendance_score', 5, 2)->default(0);
                $table->decimal('academic_score', 5, 2)->default(0);
                $table->decimal('improvement_score', 5, 2)->default(0);
                $table->decimal('conduct_score', 5, 2)->default(0);
                $table->decimal('pd_score', 5, 2)->default(0);
                $table->decimal('principal_score', 5, 2)->default(0);
                $table->decimal('total_score', 5, 2)->default(0);
                $table->string('final_grade')->nullable();
                $table->text('strengths')->nullable();
                $table->text('areas_for_improvement')->nullable();
                $table->text('recommendations')->nullable();
                $table->text('confidential_remarks')->nullable();
                $table->enum('status', ['draft', 'reviewed', 'finalized'])->default('draft');
                $table->foreignId('prepared_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamp('finalized_at')->nullable();
                $table->timestamps();

                $table->unique(['teacher_id', 'session']);
                $table->index(['session', 'status']);
                $table->index(['reviewed_by', 'status']);
            });
        }

        if (! Schema::hasTable('teacher_acr_metrics')) {
            Schema::create('teacher_acr_metrics', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('acr_id')->constrained('teacher_acrs')->cascadeOnDelete();
                $table->decimal('attendance_percentage', 5, 2)->nullable();
                $table->decimal('teacher_cgpa', 4, 2)->nullable();
                $table->decimal('pass_percentage', 5, 2)->nullable();
                $table->decimal('student_improvement_percentage', 5, 2)->nullable();
                $table->integer('trainings_attended')->default(0);
                $table->integer('late_count')->default(0);
                $table->integer('discipline_flags')->default(0);
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->unique('acr_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_acr_metrics');
        Schema::dropIfExists('teacher_acrs');
    }
};
