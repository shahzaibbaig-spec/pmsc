<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_assignments', function (Blueprint $table): void {
            if (! Schema::hasColumn('teacher_assignments', 'copied_from_assignment_id')) {
                $table->foreignId('copied_from_assignment_id')
                    ->nullable()
                    ->after('session')
                    ->constrained('teacher_assignments')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('teacher_assignments', 'copied_by')) {
                $table->foreignId('copied_by')
                    ->nullable()
                    ->after('copied_from_assignment_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('teacher_assignments', 'copied_at')) {
                $table->timestamp('copied_at')->nullable()->after('copied_by');
            }

            if (! Schema::hasColumn('teacher_assignments', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('teacher_assignments', function (Blueprint $table): void {
            if (Schema::hasColumn('teacher_assignments', 'copied_from_assignment_id')) {
                $table->dropConstrainedForeignId('copied_from_assignment_id');
            }

            if (Schema::hasColumn('teacher_assignments', 'copied_by')) {
                $table->dropConstrainedForeignId('copied_by');
            }

            if (Schema::hasColumn('teacher_assignments', 'copied_at')) {
                $table->dropColumn('copied_at');
            }

            if (Schema::hasColumn('teacher_assignments', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
