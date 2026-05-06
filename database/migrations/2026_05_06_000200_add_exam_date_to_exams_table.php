<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table): void {
            if (! Schema::hasColumn('exams', 'exam_date')) {
                $table->date('exam_date')->nullable()->after('sequence_number');
            }
        });

        if (! $this->hasIndex('exams', 'exams_exam_date_index')) {
            Schema::table('exams', function (Blueprint $table): void {
                $table->index('exam_date', 'exams_exam_date_index');
            });
        }
    }

    public function down(): void
    {
        if ($this->hasIndex('exams', 'exams_exam_date_index')) {
            Schema::table('exams', function (Blueprint $table): void {
                $table->dropIndex('exams_exam_date_index');
            });
        }

        Schema::table('exams', function (Blueprint $table): void {
            if (Schema::hasColumn('exams', 'exam_date')) {
                $table->dropColumn('exam_date');
            }
        });
    }

    private function hasIndex(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        return match ($driver) {
            'sqlite' => collect($connection->select("PRAGMA index_list('{$table}')"))
                ->contains(fn (object $row): bool => (string) ($row->name ?? '') === $index),
            'mysql', 'mariadb' => $connection->select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index]) !== [],
            'pgsql' => $connection->select(
                'SELECT indexname FROM pg_indexes WHERE schemaname = current_schema() AND tablename = ? AND indexname = ?',
                [$table, $index]
            ) !== [],
            default => false,
        };
    }
};
