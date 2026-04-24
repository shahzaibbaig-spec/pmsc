<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->dropForeignKeyForColumn('medical_referrals', 'principal_id');
        $this->dropForeignKeyForColumn('medical_referrals', 'doctor_id');

        Schema::table('medical_referrals', function (Blueprint $table): void {
            $table->unsignedBigInteger('principal_id')->nullable()->change();
            $table->unsignedBigInteger('doctor_id')->nullable()->change();

            $table->string('source_type', 30)->nullable()->after('doctor_id');
            $table->foreignId('referred_by')->nullable()->after('source_type')->constrained('users')->nullOnDelete();
            $table->foreignId('added_by')->nullable()->after('referred_by')->constrained('users')->nullOnDelete();
            $table->text('problem')->nullable()->after('illness_other_text');
            $table->date('visit_date')->nullable()->after('status');
            $table->string('session', 20)->nullable()->after('visit_date');

            $table->foreign('principal_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('doctor_id')->references('id')->on('users')->nullOnDelete();

            $table->index('source_type', 'medical_referrals_source_type_index');
            $table->index('doctor_id', 'medical_referrals_doctor_id_single_index');
            $table->index('student_id', 'medical_referrals_student_id_single_index');
            $table->index('session', 'medical_referrals_session_index');
            $table->index('visit_date', 'medical_referrals_visit_date_index');
        });

        DB::statement("UPDATE medical_referrals SET source_type = 'principal_referral' WHERE source_type IS NULL");
        DB::statement("UPDATE medical_referrals SET referred_by = principal_id WHERE referred_by IS NULL AND principal_id IS NOT NULL");
        DB::statement("UPDATE medical_referrals SET added_by = principal_id WHERE added_by IS NULL AND principal_id IS NOT NULL");
        DB::statement("UPDATE medical_referrals SET problem = COALESCE(NULLIF(problem, ''), NULLIF(illness_other_text, ''), illness_type) WHERE problem IS NULL");
        DB::statement('UPDATE medical_referrals SET visit_date = DATE(COALESCE(referred_at, created_at)) WHERE visit_date IS NULL');
        DB::statement("UPDATE medical_referrals
            SET session = CONCAT(
                YEAR(DATE_SUB(COALESCE(visit_date, DATE(created_at)), INTERVAL IF(MONTH(COALESCE(visit_date, DATE(created_at))) < 7, 1, 0) YEAR)),
                '-',
                YEAR(DATE_SUB(COALESCE(visit_date, DATE(created_at)), INTERVAL IF(MONTH(COALESCE(visit_date, DATE(created_at))) < 7, 1, 0) YEAR)) + 1
            )
            WHERE session IS NULL OR session = ''");
    }

    public function down(): void
    {
        $this->dropForeignKeyForColumn('medical_referrals', 'referred_by');
        $this->dropForeignKeyForColumn('medical_referrals', 'added_by');
        $this->dropForeignKeyForColumn('medical_referrals', 'principal_id');
        $this->dropForeignKeyForColumn('medical_referrals', 'doctor_id');

        Schema::table('medical_referrals', function (Blueprint $table): void {
            $table->dropIndex('medical_referrals_source_type_index');
            $table->dropIndex('medical_referrals_doctor_id_single_index');
            $table->dropIndex('medical_referrals_student_id_single_index');
            $table->dropIndex('medical_referrals_session_index');
            $table->dropIndex('medical_referrals_visit_date_index');

            $table->dropColumn([
                'source_type',
                'referred_by',
                'added_by',
                'problem',
                'visit_date',
                'session',
            ]);

            $table->foreign('principal_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('doctor_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    private function dropForeignKeyForColumn(string $table, string $column): void
    {
        if (DB::getDriverName() !== 'mysql') {
            Schema::table($table, function (Blueprint $blueprint) use ($column): void {
                try {
                    $blueprint->dropForeign([$column]);
                } catch (\Throwable) {
                    // Best effort for non-MySQL test connections.
                }
            });

            return;
        }

        $database = DB::getDatabaseName();
        $records = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->select('CONSTRAINT_NAME')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->get();

        foreach ($records as $record) {
            $constraintName = (string) $record->CONSTRAINT_NAME;
            if ($constraintName === '') {
                continue;
            }

            DB::statement(sprintf(
                'ALTER TABLE `%s` DROP FOREIGN KEY `%s`',
                $table,
                $constraintName
            ));
        }
    }
};
