<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('result_locks', function (Blueprint $table): void {
            $table->id();
            $table->string('session');
            $table->foreignId('class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->foreignId('exam_id')->nullable()->constrained('exams')->nullOnDelete();
            $table->enum('lock_type', ['soft', 'final']);
            $table->foreignId('locked_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('locked_at');
            $table->timestamp('unlocked_at')->nullable();
            $table->foreignId('unlocked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->unique(['session', 'class_id', 'exam_id', 'lock_type'], 'result_locks_unique_scope_type');
            $table->index(['session', 'class_id'], 'result_locks_session_class_index');
        });

        Schema::create('result_lock_logs', function (Blueprint $table): void {
            $table->id();
            $table->enum('action', ['lock', 'unlock']);
            $table->enum('lock_type', ['soft', 'final']);
            $table->string('session');
            $table->foreignId('class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->foreignId('exam_id')->nullable()->constrained('exams')->nullOnDelete();
            $table->foreignId('performed_by')->constrained('users')->cascadeOnDelete();
            $table->text('reason')->nullable();
            $table->timestamp('created_at');
        });

        Schema::table('student_results', function (Blueprint $table): void {
            if (! Schema::hasColumn('student_results', 'is_locked')) {
                $table->boolean('is_locked')->default(false)->after('exam_id');
                $table->index('is_locked', 'student_results_is_locked_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('student_results', function (Blueprint $table): void {
            if (Schema::hasColumn('student_results', 'is_locked')) {
                $table->dropIndex('student_results_is_locked_index');
                $table->dropColumn('is_locked');
            }
        });

        Schema::dropIfExists('result_lock_logs');
        Schema::dropIfExists('result_locks');
    }
};
