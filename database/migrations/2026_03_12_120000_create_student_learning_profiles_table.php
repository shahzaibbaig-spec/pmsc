<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_learning_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('session', 20);
            $table->text('strengths')->nullable();
            $table->text('support_areas')->nullable();
            $table->string('best_aptitude', 100)->nullable();
            $table->text('learning_pattern')->nullable();
            $table->decimal('attendance_percentage', 5, 2)->nullable();
            $table->decimal('overall_average', 5, 2)->nullable();
            $table->json('subject_scores')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'session']);
            $table->index(['session', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_learning_profiles');
    }
};

