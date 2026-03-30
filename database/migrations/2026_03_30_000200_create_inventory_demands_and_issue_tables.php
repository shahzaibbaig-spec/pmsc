<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_demands', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->date('request_date');
            $table->string('session', 20)->nullable();
            $table->enum('status', ['pending', 'approved', 'partially_approved', 'rejected', 'fulfilled'])->default('pending');
            $table->text('teacher_note')->nullable();
            $table->text('review_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['teacher_id', 'status']);
            $table->index(['session']);
        });

        Schema::create('inventory_demand_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('demand_id')->constrained('inventory_demands')->cascadeOnDelete();
            $table->foreignId('item_id')->nullable()->constrained('inventory_items')->nullOnDelete();
            $table->string('requested_item_name')->nullable();
            $table->unsignedInteger('requested_quantity')->default(1);
            $table->unsignedInteger('approved_quantity')->nullable();
            $table->enum('line_status', ['pending', 'approved', 'partially_approved', 'rejected', 'fulfilled'])->default('pending');
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['demand_id', 'line_status']);
        });

        Schema::create('inventory_issues', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->foreignId('demand_id')->nullable()->constrained('inventory_demands')->nullOnDelete();
            $table->date('issue_date');
            $table->string('session', 20)->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['teacher_id', 'issue_date']);
            $table->index(['session']);
        });

        Schema::create('inventory_issue_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('issue_id')->constrained('inventory_issues')->cascadeOnDelete();
            $table->foreignId('demand_line_id')->nullable()->constrained('inventory_demand_lines')->nullOnDelete();
            $table->foreignId('item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_stock_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->foreignId('issue_line_id')->nullable()->constrained('inventory_issue_lines')->nullOnDelete();
            $table->foreignId('demand_line_id')->nullable()->constrained('inventory_demand_lines')->nullOnDelete();
            $table->enum('movement_type', ['in', 'out', 'adjustment'])->default('out');
            $table->unsignedInteger('quantity');
            $table->string('reference_type', 60)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('moved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('moved_at');
            $table->timestamps();

            $table->index(['item_id', 'movement_type', 'moved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_stock_movements');
        Schema::dropIfExists('inventory_issue_lines');
        Schema::dropIfExists('inventory_issues');
        Schema::dropIfExists('inventory_demand_lines');
        Schema::dropIfExists('inventory_demands');
    }
};
