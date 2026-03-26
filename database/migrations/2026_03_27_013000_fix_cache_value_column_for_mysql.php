<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('cache')) {
            return;
        }

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE `cache` MODIFY `value` LONGTEXT NOT NULL');
        DB::statement('ALTER TABLE `cache` MODIFY `expiration` INT UNSIGNED NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('cache')) {
            return;
        }

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE `cache` MODIFY `value` MEDIUMTEXT NOT NULL');
        DB::statement('ALTER TABLE `cache` MODIFY `expiration` INT NOT NULL');
    }
};

