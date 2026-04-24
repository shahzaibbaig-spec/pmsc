<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warden_daily_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hostel_id')->constrained('hostels')->cascadeOnDelete();
            $table->date('report_date');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['hostel_id', 'report_date']);
            $table->index(['hostel_id', 'report_date']);
        });

        Schema::create('warden_attendance', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('report_id')->constrained('warden_daily_reports')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->enum('status', ['present', 'absent', 'on_leave']);
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['report_id', 'student_id']);
            $table->index(['report_id', 'status']);
            $table->index(['student_id']);
        });

        Schema::create('warden_discipline_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('report_id')->constrained('warden_daily_reports')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('issue_type');
            $table->enum('severity', ['low', 'medium', 'high']);
            $table->text('description');
            $table->text('action_taken')->nullable();
            $table->timestamps();

            $table->index(['report_id', 'severity']);
            $table->index(['student_id']);
        });

        Schema::create('warden_health_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('report_id')->constrained('warden_daily_reports')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->text('condition');
            $table->decimal('temperature', 4, 1)->nullable();
            $table->text('medication')->nullable();
            $table->boolean('doctor_visit')->default(false);
            $table->timestamps();

            $table->index(['report_id', 'doctor_visit']);
            $table->index(['student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warden_health_logs');
        Schema::dropIfExists('warden_discipline_logs');
        Schema::dropIfExists('warden_attendance');
        Schema::dropIfExists('warden_daily_reports');
    }
};

