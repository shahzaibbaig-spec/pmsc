<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_subject_assignments', function (Blueprint $table): void {
            $table->foreignId('subject_group_id')
                ->nullable()
                ->after('subject_id')
                ->constrained('subject_groups')
                ->nullOnDelete();

            $table->index(['session', 'student_id', 'subject_group_id'], 'student_subject_assignments_group_lookup');
        });
    }

    public function down(): void
    {
        Schema::table('student_subject_assignments', function (Blueprint $table): void {
            $table->dropIndex('student_subject_assignments_group_lookup');
            $table->dropConstrainedForeignId('subject_group_id');
        });
    }
};

