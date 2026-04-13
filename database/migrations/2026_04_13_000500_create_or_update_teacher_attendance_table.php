<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('teacher_attendance')) {
            Schema::create('teacher_attendance', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
                $table->date('attendance_date');
                $table->enum('status', ['present', 'absent', 'leave', 'late']);
                $table->text('remarks')->nullable();
                $table->foreignId('marked_by')->nullable()->constrained('users')->nullOnDelete();
                $table->enum('source', ['manual', 'system'])->default('manual');
                $table->timestamps();

                $table->unique(['teacher_id', 'attendance_date'], 'teacher_attendance_teacher_date_unique');
                $table->index(['attendance_date', 'status'], 'teacher_attendance_date_status_index');
            });

            return;
        }

        $addRemarks = ! Schema::hasColumn('teacher_attendance', 'remarks');
        $addMarkedBy = ! Schema::hasColumn('teacher_attendance', 'marked_by');
        $addSource = ! Schema::hasColumn('teacher_attendance', 'source');

        if (! $addRemarks && ! $addMarkedBy && ! $addSource) {
            return;
        }

        Schema::table('teacher_attendance', function (Blueprint $table) use ($addRemarks, $addMarkedBy, $addSource): void {
            if ($addRemarks) {
                $table->text('remarks')->nullable()->after('status');
            }

            if ($addMarkedBy) {
                $table->foreignId('marked_by')->nullable()->after('remarks')->constrained('users')->nullOnDelete();
            }

            if ($addSource) {
                $table->enum('source', ['manual', 'system'])->default('manual')->after('marked_by');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('teacher_attendance')) {
            return;
        }

        $dropSource = Schema::hasColumn('teacher_attendance', 'source');
        $dropMarkedBy = Schema::hasColumn('teacher_attendance', 'marked_by');
        $dropRemarks = Schema::hasColumn('teacher_attendance', 'remarks');

        if (! $dropSource && ! $dropMarkedBy && ! $dropRemarks) {
            return;
        }

        Schema::table('teacher_attendance', function (Blueprint $table) use ($dropSource, $dropMarkedBy, $dropRemarks): void {
            if ($dropMarkedBy) {
                $table->dropConstrainedForeignId('marked_by');
            }

            if ($dropSource) {
                $table->dropColumn('source');
            }

            if ($dropRemarks) {
                $table->dropColumn('remarks');
            }
        });
    }
};

