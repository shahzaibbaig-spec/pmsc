<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cognitive_assessment_attempt_resets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('attempt_id')->constrained('cognitive_assessment_attempts')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('reset_by')->constrained('users');
            $table->text('reason')->nullable();
            $table->timestamp('reset_at');
            $table->timestamps();

            $table->index(['student_id', 'reset_at'], 'cognitive_assessment_attempt_resets_student_reset_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cognitive_assessment_attempt_resets');
    }
};
