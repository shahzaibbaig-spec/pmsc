<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table): void {
            $table->boolean('is_default')->default(false)->after('code');
            $table->index(['is_default']);
        });

        DB::table('subjects')
            ->whereIn('name', [
                'English',
                'Urdu',
                'Mathematics',
                'Islamiyat',
                'Pakistan Studies',
                'Physics',
                'Chemistry',
                'Biology',
                'Computer Science',
            ])
            ->update(['is_default' => true]);
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table): void {
            $table->dropIndex(['is_default']);
            $table->dropColumn('is_default');
        });
    }
};

