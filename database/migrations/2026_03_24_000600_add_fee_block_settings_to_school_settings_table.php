<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_settings', function (Blueprint $table): void {
            if (! Schema::hasColumn('school_settings', 'block_results_for_defaulters')) {
                $table->boolean('block_results_for_defaulters')->default(false)->after('email');
            }

            if (! Schema::hasColumn('school_settings', 'block_admit_card_for_defaulters')) {
                $table->boolean('block_admit_card_for_defaulters')->default(false)->after('block_results_for_defaulters');
            }

            if (! Schema::hasColumn('school_settings', 'block_id_card_for_defaulters')) {
                $table->boolean('block_id_card_for_defaulters')->default(false)->after('block_admit_card_for_defaulters');
            }
        });
    }

    public function down(): void
    {
        Schema::table('school_settings', function (Blueprint $table): void {
            if (Schema::hasColumn('school_settings', 'block_id_card_for_defaulters')) {
                $table->dropColumn('block_id_card_for_defaulters');
            }

            if (Schema::hasColumn('school_settings', 'block_admit_card_for_defaulters')) {
                $table->dropColumn('block_admit_card_for_defaulters');
            }

            if (Schema::hasColumn('school_settings', 'block_results_for_defaulters')) {
                $table->dropColumn('block_results_for_defaulters');
            }
        });
    }
};
