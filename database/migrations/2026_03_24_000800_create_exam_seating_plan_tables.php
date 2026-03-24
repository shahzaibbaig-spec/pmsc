<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_rooms', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120)->unique();
            $table->unsignedInteger('capacity');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'name'], 'exam_rooms_active_name_index');
        });

        Schema::create('exam_seating_plans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('exam_session_id')->constrained('exam_sessions')->cascadeOnDelete();
            $table->json('class_ids');
            $table->boolean('is_randomized')->default(false);
            $table->unsignedInteger('total_students')->default(0);
            $table->unsignedInteger('total_rooms')->default(0);
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->index(['exam_session_id', 'generated_at'], 'seating_plans_session_generated_at_index');
        });

        Schema::create('exam_seat_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('exam_seating_plan_id')->constrained('exam_seating_plans')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->foreignId('exam_room_id')->constrained('exam_rooms')->cascadeOnDelete();
            $table->unsignedInteger('seat_number');
            $table->timestamps();

            $table->unique(['exam_seating_plan_id', 'student_id'], 'seat_assignments_plan_student_unique');
            $table->unique(['exam_seating_plan_id', 'exam_room_id', 'seat_number'], 'seat_assignments_plan_room_seat_unique');
            $table->index(['exam_seating_plan_id', 'class_id'], 'seat_assignments_plan_class_index');
            $table->index(['exam_room_id', 'seat_number'], 'seat_assignments_room_seat_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_seat_assignments');
        Schema::dropIfExists('exam_seating_plans');
        Schema::dropIfExists('exam_rooms');
    }
};
