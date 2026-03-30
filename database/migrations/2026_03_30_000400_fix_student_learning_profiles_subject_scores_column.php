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

        if (! Schema::hasTable('student_learning_profiles')) {
            return;
        }

        if (! Schema::hasColumn('student_learning_profiles', 'subject_scores')) {
            return;
        }

        $column = DB::selectOne("SHOW COLUMNS FROM `student_learning_profiles` LIKE 'subject_scores'");
        $type = strtolower((string) ($column->Type ?? ''));

        if (str_starts_with($type, 'json')) {
            return;
        }

        DB::statement('UPDATE `student_learning_profiles` SET `subject_scores` = NULL WHERE `subject_scores` IS NOT NULL AND JSON_VALID(`subject_scores`) = 0');
        DB::statement('ALTER TABLE `student_learning_profiles` MODIFY `subject_scores` JSON NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (! Schema::hasTable('student_learning_profiles')) {
            return;
        }

        if (! Schema::hasColumn('student_learning_profiles', 'subject_scores')) {
            return;
        }

        DB::statement('ALTER TABLE `student_learning_profiles` MODIFY `subject_scores` LONGTEXT NULL');
    }
};
