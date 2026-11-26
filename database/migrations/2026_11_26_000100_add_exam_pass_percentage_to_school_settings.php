<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_settings', function (Blueprint $table) {
            $table->decimal('exam_pass_percentage', 5, 2)->nullable();
        });
        \Illuminate\Support\Facades\Cache::forget(\App\Models\SchoolSetting::CACHE_KEY);
    }

    public function down(): void
    {
        Schema::table('school_settings', function (Blueprint $table) {
            $table->dropColumn('exam_pass_percentage');
        });
        \Illuminate\Support\Facades\Cache::forget(\App\Models\SchoolSetting::CACHE_KEY);
    }
};
