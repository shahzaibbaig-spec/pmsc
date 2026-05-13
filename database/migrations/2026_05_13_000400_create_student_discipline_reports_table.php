<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_discipline_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('class_id')->nullable()->constrained('school_classes')->nullOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->restrictOnDelete();
            $table->string('session', 20);
            $table->date('report_date');
            $table->string('issue_type', 100);
            $table->string('issue_label', 190);
            $table->string('severity', 20)->default('normal');
            $table->text('description')->nullable();
            $table->text('auto_message');
            $table->string('status', 20)->default('open');
            $table->text('principal_remarks')->nullable();
            $table->text('warden_remarks')->nullable();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('student_id');
            $table->index('class_id');
            $table->index('subject_id');
            $table->index('teacher_id');
            $table->index('session');
            $table->index('report_date');
            $table->index('issue_type');
            $table->index('severity');
            $table->index('status');
            $table->index(
                ['student_id', 'teacher_id', 'issue_type', 'report_date', 'session'],
                'student_discipline_duplicate_lookup_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_discipline_reports');
    }
};

