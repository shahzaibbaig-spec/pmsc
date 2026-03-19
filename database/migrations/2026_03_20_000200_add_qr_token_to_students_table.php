<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('students') || Schema::hasColumn('students', 'qr_token')) {
            return;
        }

        Schema::table('students', function (Blueprint $table): void {
            $table->string('qr_token', 64)->nullable()->unique();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('students') || ! Schema::hasColumn('students', 'qr_token')) {
            return;
        }

        Schema::table('students', function (Blueprint $table): void {
            $table->dropUnique('students_qr_token_unique');
            $table->dropColumn('qr_token');
        });
    }
};
