<?php

use App\Models\CognitiveAssessmentSection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cognitive_bank_questions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('question_bank_id')->constrained('cognitive_question_banks')->cascadeOnDelete();
            $table->enum('skill', [
                CognitiveAssessmentSection::SKILL_VERBAL,
                CognitiveAssessmentSection::SKILL_NON_VERBAL,
                CognitiveAssessmentSection::SKILL_QUANTITATIVE,
                CognitiveAssessmentSection::SKILL_SPATIAL,
            ]);
            $table->string('question_type');
            $table->string('difficulty_level', 50)->nullable();
            $table->text('question_text')->nullable();
            $table->string('question_image')->nullable();
            $table->text('explanation')->nullable();
            $table->json('options')->nullable();
            $table->string('correct_answer')->nullable();
            $table->unsignedInteger('marks')->default(1);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['question_bank_id', 'skill']);
            $table->index(['skill', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cognitive_bank_questions');
    }
};
