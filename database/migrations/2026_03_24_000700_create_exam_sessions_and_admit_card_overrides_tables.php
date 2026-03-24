<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_sessions', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 150);
            $table->string('session', 20);
            $table->date('start_date');
            $table->date('end_date');
            $table->timestamps();

            $table->index(['session', 'start_date'], 'exam_sessions_session_start_index');
        });

        Schema::create('admit_card_overrides', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('exam_session_id')->constrained('exam_sessions')->cascadeOnDelete();
            $table->boolean('is_allowed')->default(true);
            $table->text('reason')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['student_id', 'exam_session_id'], 'admit_card_overrides_student_exam_session_unique');
            $table->index(['exam_session_id', 'is_allowed'], 'admit_card_overrides_session_allowed_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admit_card_overrides');
        Schema::dropIfExists('exam_sessions');
    }
};
