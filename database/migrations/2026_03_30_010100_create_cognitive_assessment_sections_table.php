<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cognitive_assessment_sections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('assessment_id')->constrained('cognitive_assessments')->cascadeOnDelete();
            $table->enum('skill', ['verbal', 'non_verbal', 'quantitative', 'spatial']);
            $table->string('title');
            $table->unsignedInteger('duration_seconds')->default(600);
            $table->unsignedInteger('total_marks')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['assessment_id', 'skill'], 'cognitive_assessment_sections_assessment_skill_unique');
            $table->index(['assessment_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cognitive_assessment_sections');
    }
};
