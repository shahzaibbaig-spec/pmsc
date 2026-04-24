<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_cbc_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_medical_record_id')
                ->nullable()
                ->constrained('medical_referrals')
                ->nullOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained('users')->cascadeOnDelete();
            $table->string('session', 20);
            $table->date('report_date');
            $table->string('machine_report_no', 100)->nullable();

            $table->decimal('hemoglobin', 10, 2)->nullable();
            $table->decimal('rbc_count', 10, 2)->nullable();
            $table->decimal('wbc_count', 10, 2)->nullable();
            $table->decimal('platelet_count', 10, 2)->nullable();
            $table->decimal('hematocrit_pcv', 10, 2)->nullable();
            $table->decimal('mcv', 10, 2)->nullable();
            $table->decimal('mch', 10, 2)->nullable();
            $table->decimal('mchc', 10, 2)->nullable();
            $table->decimal('neutrophils', 10, 2)->nullable();
            $table->decimal('lymphocytes', 10, 2)->nullable();
            $table->decimal('monocytes', 10, 2)->nullable();
            $table->decimal('eosinophils', 10, 2)->nullable();
            $table->decimal('basophils', 10, 2)->nullable();
            $table->decimal('esr', 10, 2)->nullable();

            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('student_id');
            $table->index('doctor_id');
            $table->index('session');
            $table->index('report_date');
            $table->index('student_medical_record_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_cbc_reports');
    }
};
