<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hostel_room_allocations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hostel_room_id')->constrained('hostel_rooms')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->date('allocated_from');
            $table->date('allocated_to')->nullable();
            $table->enum('status', ['active', 'shifted', 'completed'])->default('active');
            $table->text('remarks')->nullable();
            $table->foreignId('allocated_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['hostel_room_id', 'status']);
            $table->index(['student_id', 'status']);
            $table->index(['allocated_from', 'allocated_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hostel_room_allocations');
    }
};
