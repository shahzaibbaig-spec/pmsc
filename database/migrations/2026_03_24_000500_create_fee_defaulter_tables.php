<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_defaulters', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('session', 20);
            $table->decimal('total_due', 10, 2)->default(0);
            $table->date('oldest_due_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('marked_at')->nullable();
            $table->timestamp('cleared_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'session'], 'fee_defaulters_student_session_unique');
            $table->index(['session', 'is_active'], 'fee_defaulters_session_active_index');
            $table->index(['oldest_due_date'], 'fee_defaulters_oldest_due_date_index');
        });

        Schema::create('fee_reminders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('challan_id')->nullable()->constrained('fee_challans')->nullOnDelete();
            $table->string('session', 20);
            $table->string('channel', 30)->default('in_app');
            $table->string('title', 150);
            $table->text('message');
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'session'], 'fee_reminders_student_session_index');
            $table->index(['sent_at'], 'fee_reminders_sent_at_index');
        });

        Schema::create('fee_block_overrides', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('session', 20);
            $table->string('block_type', 40);
            $table->boolean('is_allowed')->default(true);
            $table->text('reason')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['student_id', 'session', 'block_type'], 'fee_block_overrides_student_session_type_unique');
            $table->index(['block_type', 'is_allowed'], 'fee_block_overrides_type_allowed_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_block_overrides');
        Schema::dropIfExists('fee_reminders');
        Schema::dropIfExists('fee_defaulters');
    }
};
