<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_sports_observations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('class_id')->nullable()->constrained('school_classes')->nullOnDelete();
            $table->foreignId('sports_teacher_id')->constrained('users')->restrictOnDelete();
            $table->string('session', 20);
            $table->date('observation_date');
            $table->string('issue_type', 100);
            $table->string('issue_label', 190);
            $table->text('auto_message');
            $table->string('severity', 20)->default('normal');
            $table->string('status', 20)->default('open');
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('resolution_notes')->nullable();
            $table->timestamp('notified_principal_at')->nullable();
            $table->timestamp('notified_wardens_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('student_id');
            $table->index('class_id');
            $table->index('sports_teacher_id');
            $table->index('session');
            $table->index('observation_date');
            $table->index('issue_type');
            $table->index('status');
            $table->index(['student_id', 'issue_type', 'observation_date', 'session'], 'sports_obs_duplicate_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_sports_observations');
    }
};
