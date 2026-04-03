<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cognitive_assessment_student_assignments')) {
            return;
        }

        Schema::create('cognitive_assessment_student_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('assessment_id')->constrained('cognitive_assessments')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->boolean('is_enabled')->default(false);
            $table->foreignId('enabled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('enabled_at')->nullable();
            $table->foreignId('disabled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('disabled_at')->nullable();
            $table->text('principal_note')->nullable();
            $table->timestamps();

            $table->unique(
                ['assessment_id', 'student_id'],
                'cognitive_assessment_student_assignments_assessment_student_unique'
            );
            $table->index(['student_id', 'is_enabled'], 'cognitive_assessment_student_assignments_student_enabled_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cognitive_assessment_student_assignments');
    }
};
