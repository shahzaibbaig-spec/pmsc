<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('hostel_room_allocations')) {
            return;
        }

        Schema::table('hostel_room_allocations', function (Blueprint $table): void {
            $table->dropForeign(['hostel_room_id']);
        });

        Schema::table('hostel_room_allocations', function (Blueprint $table): void {
            $table->foreignId('hostel_room_id')->nullable()->change();
        });

        Schema::table('hostel_room_allocations', function (Blueprint $table): void {
            $table->foreign('hostel_room_id')
                ->references('id')
                ->on('hostel_rooms')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('hostel_room_allocations')) {
            return;
        }

        Schema::table('hostel_room_allocations', function (Blueprint $table): void {
            $table->dropForeign(['hostel_room_id']);
        });

        Schema::table('hostel_room_allocations', function (Blueprint $table): void {
            $table->foreignId('hostel_room_id')->nullable(false)->change();
        });

        Schema::table('hostel_room_allocations', function (Blueprint $table): void {
            $table->foreign('hostel_room_id')
                ->references('id')
                ->on('hostel_rooms')
                ->cascadeOnDelete();
        });
    }
};

