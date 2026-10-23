<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('senior_progress_report_drafts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->string('session', 20);
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->json('content');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['student_id', 'class_id', 'session', 'exam_id'], 'senior_progress_draft_context_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('senior_progress_report_drafts');
    }
};
