<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_device_declarations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->string('device_type', 40)->default('chromebook');
            $table->string('serial_number');
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->enum('status', ['submitted', 'verified', 'rejected', 'linked'])->default('submitted');
            $table->foreignId('asset_unit_id')->nullable()->constrained('inventory_asset_units')->nullOnDelete();
            $table->text('teacher_note')->nullable();
            $table->text('admin_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['teacher_id', 'status']);
            $table->index(['serial_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_device_declarations');
    }
};
