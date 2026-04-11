<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('marks') && ! Schema::hasColumn('marks', 'grade')) {
            Schema::table('marks', function (Blueprint $table): void {
                $table->string('grade', 10)->nullable()->after('obtained_marks');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('marks') && Schema::hasColumn('marks', 'grade')) {
            Schema::table('marks', function (Blueprint $table): void {
                $table->dropColumn('grade');
            });
        }
    }
};
