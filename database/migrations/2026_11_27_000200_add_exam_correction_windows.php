<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->timestamp('corrections_opened_at')->nullable();
            $table->timestamp('corrections_close_at')->nullable();
            $table->unsignedBigInteger('corrections_opened_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn(['corrections_opened_at', 'corrections_close_at', 'corrections_opened_by']);
        });
    }
};
