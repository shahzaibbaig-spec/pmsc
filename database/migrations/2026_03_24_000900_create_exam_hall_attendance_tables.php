<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_room_invigilators', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('exam_session_id')->constrained('exam_sessions')->cascadeOnDelete();
            $table->foreignId('room_id')->constrained('exam_rooms')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['exam_session_id', 'room_id', 'teacher_id'], 'exam_room_invigilators_unique');
            $table->index(['exam_session_id', 'room_id'], 'exam_room_invigilators_session_room_index');
        });

        Schema::create('exam_attendances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('exam_session_id')->constrained('exam_sessions')->cascadeOnDelete();
            $table->foreignId('room_id')->constrained('exam_rooms')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('seat_assignment_id')->constrained('exam_seat_assignments')->cascadeOnDelete();
            $table->string('status', 20)->default('present');
            $table->text('remarks')->nullable();
            $table->foreignId('marked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('marked_at')->nullable();
            $table->timestamps();

            $table->unique(['exam_session_id', 'room_id', 'student_id'], 'exam_attendances_unique');
            $table->index(['exam_session_id', 'room_id', 'status'], 'exam_attendances_session_room_status_index');
            $table->index(['seat_assignment_id'], 'exam_attendances_seat_assignment_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_attendances');
        Schema::dropIfExists('exam_room_invigilators');
    }
};
