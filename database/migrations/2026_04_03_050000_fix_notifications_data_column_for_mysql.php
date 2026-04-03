<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notifications') || ! Schema::hasColumn('notifications', 'data')) {
            return;
        }

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $dataColumn = DB::selectOne("SHOW COLUMNS FROM `notifications` LIKE 'data'");
        $dataType = strtolower((string) ($dataColumn->Type ?? ''));

        if ($dataType === 'longtext') {
            return;
        }

        DB::statement('ALTER TABLE `notifications` MODIFY `data` LONGTEXT NOT NULL');
    }

    public function down(): void
    {
        if (! Schema::hasTable('notifications') || ! Schema::hasColumn('notifications', 'data')) {
            return;
        }

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE `notifications` MODIFY `data` TEXT NOT NULL');
    }
};
