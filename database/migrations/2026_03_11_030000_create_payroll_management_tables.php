<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('basic_salary', 12, 2);
            $table->decimal('allowances', 12, 2)->default(0);
            $table->decimal('deductions', 12, 2)->default(0);
            $table->string('bank_name', 120)->nullable();
            $table->string('account_no', 100)->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->unique('user_id');
            $table->index(['status', 'user_id']);
        });

        Schema::create('payroll_allowances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payroll_profile_id')->constrained('payroll_profiles')->cascadeOnDelete();
            $table->string('title', 120);
            $table->decimal('amount', 12, 2);
            $table->timestamps();

            $table->index('payroll_profile_id');
        });

        Schema::create('payroll_deductions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payroll_profile_id')->constrained('payroll_profiles')->cascadeOnDelete();
            $table->string('title', 120);
            $table->decimal('amount', 12, 2);
            $table->timestamps();

            $table->index('payroll_profile_id');
        });

        Schema::create('payroll_runs', function (Blueprint $table): void {
            $table->id();
            $table->string('month', 7);
            $table->date('run_date');
            $table->string('status', 20)->default('generated');
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('month');
            $table->index(['month', 'status']);
        });

        Schema::create('payroll_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained('payroll_runs')->cascadeOnDelete();
            $table->foreignId('payroll_profile_id')->constrained('payroll_profiles')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('basic_salary', 12, 2);
            $table->decimal('allowances_total', 12, 2)->default(0);
            $table->decimal('deductions_total', 12, 2)->default(0);
            $table->decimal('net_salary', 12, 2);
            $table->string('status', 20)->default('generated');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['payroll_run_id', 'payroll_profile_id']);
            $table->index(['payroll_run_id', 'status']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_items');
        Schema::dropIfExists('payroll_runs');
        Schema::dropIfExists('payroll_deductions');
        Schema::dropIfExists('payroll_allowances');
        Schema::dropIfExists('payroll_profiles');
    }
};
