<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_diaries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->string('session', 20);
            $table->date('diary_date');
            $table->string('title')->nullable();
            $table->text('homework_text');
            $table->text('instructions')->nullable();
            $table->boolean('is_published')->default(true);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->unique(['class_id', 'subject_id', 'session', 'diary_date'], 'daily_diaries_unique_scope');
            $table->index(['teacher_id', 'session', 'diary_date'], 'daily_diaries_teacher_session_date_index');
            $table->index(['class_id', 'session', 'diary_date'], 'daily_diaries_class_session_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_diaries');
    }
};

