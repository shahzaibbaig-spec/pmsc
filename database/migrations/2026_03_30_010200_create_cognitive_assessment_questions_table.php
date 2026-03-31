<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cognitive_assessment_questions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('section_id')->constrained('cognitive_assessment_sections')->cascadeOnDelete();
            $table->string('question_type');
            $table->text('question_text')->nullable();
            $table->string('question_image')->nullable();
            $table->json('options')->nullable();
            $table->text('correct_answer')->nullable();
            $table->unsignedInteger('marks')->default(1);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['section_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cognitive_assessment_questions');
    }
};
