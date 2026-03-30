<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('category', 60)->default('stationery');
            $table->string('unit', 30)->default('pcs');
            $table->unsignedInteger('current_stock')->default(0);
            $table->unsignedInteger('minimum_stock')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['category', 'is_active']);
        });

        Schema::create('inventory_asset_units', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('item_id')->nullable()->constrained('inventory_items')->nullOnDelete();
            $table->string('serial_number')->unique();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->enum('status', ['available', 'issued', 'maintenance', 'retired'])->default('available');
            $table->foreignId('issued_to_teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
            $table->timestamp('issued_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_asset_units');
        Schema::dropIfExists('inventory_items');
    }
};
