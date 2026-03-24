<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_installment_plans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('session', 20);
            $table->string('plan_name', 150)->nullable();
            $table->string('plan_type', 20);
            $table->decimal('total_amount', 10, 2);
            $table->unsignedInteger('number_of_installments');
            $table->date('first_due_date');
            $table->unsignedInteger('custom_interval_days')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['student_id', 'session', 'is_active'], 'installment_plans_student_session_active_index');
            $table->index(['plan_type', 'is_active'], 'installment_plans_type_active_index');
        });

        Schema::create('fee_installments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('fee_installment_plan_id')->constrained('fee_installment_plans')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->unsignedInteger('installment_no');
            $table->string('title', 150)->nullable();
            $table->date('due_date');
            $table->decimal('amount', 10, 2);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->string('status', 20)->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['fee_installment_plan_id', 'installment_no'], 'installments_plan_number_unique');
            $table->index(['student_id', 'status', 'due_date'], 'installments_student_status_due_index');
            $table->index(['fee_installment_plan_id', 'due_date'], 'installments_plan_due_index');
        });

        Schema::create('student_arrears', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('session', 20)->nullable();
            $table->string('title', 150);
            $table->decimal('amount', 10, 2);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->string('status', 20)->default('pending');
            $table->date('due_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'status'], 'student_arrears_student_status_index');
            $table->index(['session', 'status'], 'student_arrears_session_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_arrears');
        Schema::dropIfExists('fee_installments');
        Schema::dropIfExists('fee_installment_plans');
    }
};

