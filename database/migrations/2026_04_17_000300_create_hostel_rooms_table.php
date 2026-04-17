<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hostel_rooms', function (Blueprint $table): void {
            $table->id();
            $table->string('room_name');
            $table->unsignedInteger('floor_number');
            $table->unsignedInteger('capacity')->default(1);
            $table->string('gender')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['room_name', 'floor_number'], 'hostel_rooms_room_floor_unique');
            $table->index(['floor_number', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hostel_rooms');
    }
};
