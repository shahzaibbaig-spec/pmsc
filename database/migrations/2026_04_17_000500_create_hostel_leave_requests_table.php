<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hostel_leave_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('hostel_room_id')->nullable()->constrained('hostel_rooms')->nullOnDelete();
            $table->dateTime('leave_from');
            $table->dateTime('leave_to');
            $table->text('reason');
            $table->enum('status', ['pending', 'approved', 'rejected', 'returned'])->default('pending');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'status']);
            $table->index(['hostel_room_id', 'status']);
            $table->index(['leave_from', 'leave_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hostel_leave_requests');
    }
};
