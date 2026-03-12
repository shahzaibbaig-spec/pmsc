<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_structures', function (Blueprint $table): void {
            $table->id();
            $table->string('session', 20);
            $table->foreignId('class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->string('title', 150);
            $table->decimal('amount', 10, 2);
            $table->string('fee_type', 50);
            $table->boolean('is_monthly')->default(false);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['session', 'class_id']);
            $table->index(['class_id', 'is_active']);
        });

        Schema::create('student_fee_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('fee_structure_id')->constrained('fee_structures')->cascadeOnDelete();
            $table->string('session', 20);
            $table->decimal('custom_amount', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['student_id', 'fee_structure_id', 'session'], 'student_fee_assignments_unique');
            $table->index(['session', 'is_active']);
        });

        Schema::create('fee_challans', function (Blueprint $table): void {
            $table->id();
            $table->string('challan_number', 40)->unique();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->string('session', 20);
            $table->string('month', 7);
            $table->date('issue_date');
            $table->date('due_date');
            $table->decimal('total_amount', 10, 2);
            $table->string('status', 20)->default('unpaid');
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'session', 'month'], 'fee_challans_student_session_month_unique');
            $table->index(['class_id', 'session', 'month'], 'fee_challans_class_session_month_index');
            $table->index(['status', 'due_date'], 'fee_challans_status_due_date_index');
        });

        Schema::create('fee_challan_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('fee_challan_id')->constrained('fee_challans')->cascadeOnDelete();
            $table->foreignId('fee_structure_id')->nullable()->constrained('fee_structures')->nullOnDelete();
            $table->string('title', 150);
            $table->string('fee_type', 50);
            $table->decimal('amount', 10, 2);
            $table->timestamps();

            $table->index('fee_challan_id');
        });

        Schema::create('fee_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('fee_challan_id')->constrained('fee_challans')->cascadeOnDelete();
            $table->decimal('amount_paid', 10, 2);
            $table->date('payment_date');
            $table->string('payment_method', 50)->nullable();
            $table->string('reference_no', 100)->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['fee_challan_id', 'payment_date'], 'fee_payments_challan_payment_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_payments');
        Schema::dropIfExists('fee_challan_items');
        Schema::dropIfExists('fee_challans');
        Schema::dropIfExists('student_fee_assignments');
        Schema::dropIfExists('fee_structures');
    }
};
