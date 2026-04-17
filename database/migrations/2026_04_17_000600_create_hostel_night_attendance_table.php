<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hostel_night_attendance', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('hostel_room_id')->nullable()->constrained('hostel_rooms')->nullOnDelete();
            $table->date('attendance_date');
            $table->enum('status', ['present', 'absent', 'on_leave', 'late_return'])->default('present');
            $table->text('remarks')->nullable();
            $table->foreignId('marked_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['student_id', 'attendance_date'], 'hostel_night_attendance_student_date_unique');
            $table->index(['attendance_date', 'status']);
            $table->index(['hostel_room_id', 'attendance_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hostel_night_attendance');
    }
};
