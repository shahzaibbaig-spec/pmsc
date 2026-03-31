<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cognitive_assessment_section_questions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('section_id')->constrained('cognitive_assessment_sections')->cascadeOnDelete();
            $table->foreignId('bank_question_id')->constrained('cognitive_bank_questions')->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['section_id', 'bank_question_id'], 'cognitive_assessment_section_questions_section_bank_unique');
            $table->index(['section_id', 'sort_order'], 'cognitive_assessment_section_questions_section_sort_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cognitive_assessment_section_questions');
    }
};
