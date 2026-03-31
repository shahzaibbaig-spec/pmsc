<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cognitive_assessment_responses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('attempt_id')->constrained('cognitive_assessment_attempts')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('cognitive_assessment_questions')->cascadeOnDelete();
            $table->text('selected_answer')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->unsignedInteger('awarded_marks')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();

            $table->unique(['attempt_id', 'question_id'], 'cognitive_assessment_responses_attempt_question_unique');
            $table->index(['attempt_id', 'locked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cognitive_assessment_responses');
    }
};
