<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('student_discipline_reports')) {
            Schema::table('student_discipline_reports', function (Blueprint $table): void {
                $table->text('psychiatrist_feedback')->nullable()->after('warden_remarks');
                $table->foreignId('psychiatrist_reviewed_by')
                    ->nullable()
                    ->after('psychiatrist_feedback')
                    ->constrained('users')
                    ->nullOnDelete();
                $table->timestamp('psychiatrist_reviewed_at')
                    ->nullable()
                    ->after('psychiatrist_reviewed_by');
                $table->index('psychiatrist_reviewed_by');
                $table->index('psychiatrist_reviewed_at');
            });
        }

        if (Schema::hasTable('student_sports_observations')) {
            Schema::table('student_sports_observations', function (Blueprint $table): void {
                $table->text('psychiatrist_feedback')->nullable()->after('resolution_notes');
                $table->foreignId('psychiatrist_reviewed_by')
                    ->nullable()
                    ->after('psychiatrist_feedback')
                    ->constrained('users')
                    ->nullOnDelete();
                $table->timestamp('psychiatrist_reviewed_at')
                    ->nullable()
                    ->after('psychiatrist_reviewed_by');
                $table->index('psychiatrist_reviewed_by');
                $table->index('psychiatrist_reviewed_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('student_discipline_reports')) {
            Schema::table('student_discipline_reports', function (Blueprint $table): void {
                $table->dropForeign(['psychiatrist_reviewed_by']);
                $table->dropIndex(['psychiatrist_reviewed_by']);
                $table->dropIndex(['psychiatrist_reviewed_at']);
                $table->dropColumn([
                    'psychiatrist_feedback',
                    'psychiatrist_reviewed_by',
                    'psychiatrist_reviewed_at',
                ]);
            });
        }

        if (Schema::hasTable('student_sports_observations')) {
            Schema::table('student_sports_observations', function (Blueprint $table): void {
                $table->dropForeign(['psychiatrist_reviewed_by']);
                $table->dropIndex(['psychiatrist_reviewed_by']);
                $table->dropIndex(['psychiatrist_reviewed_at']);
                $table->dropColumn([
                    'psychiatrist_feedback',
                    'psychiatrist_reviewed_by',
                    'psychiatrist_reviewed_at',
                ]);
            });
        }
    }
};

