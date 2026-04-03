<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('teacher_cgpa_rankings') || Schema::hasColumn('teacher_cgpa_rankings', 'pass_percentage')) {
            return;
        }

        Schema::table('teacher_cgpa_rankings', function (Blueprint $table): void {
            $table->decimal('pass_percentage', 5, 2)
                ->default(0)
                ->after('average_percentage');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('teacher_cgpa_rankings') || ! Schema::hasColumn('teacher_cgpa_rankings', 'pass_percentage')) {
            return;
        }

        Schema::table('teacher_cgpa_rankings', function (Blueprint $table): void {
            $table->dropColumn('pass_percentage');
        });
    }
};
