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
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (! Schema::hasTable('report_comments')) {
            return;
        }

        $columns = ['auto_comment', 'final_comment'];

        foreach ($columns as $columnName) {
            if (! Schema::hasColumn('report_comments', $columnName)) {
                continue;
            }

            $column = DB::selectOne(sprintf("SHOW COLUMNS FROM `report_comments` LIKE '%s'", $columnName));
            $type = strtolower((string) ($column->Type ?? ''));

            if (str_contains($type, 'text')) {
                continue;
            }

            DB::statement(sprintf('ALTER TABLE `report_comments` MODIFY `%s` TEXT NULL', $columnName));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (! Schema::hasTable('report_comments')) {
            return;
        }

        // Keep wider comment columns to avoid truncation regressions.
    }
};
