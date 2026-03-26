<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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

        if (! Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table): void {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });

            return;
        }

        $idColumn = DB::selectOne("SHOW COLUMNS FROM `sessions` LIKE 'id'");
        $idType = strtolower((string) ($idColumn->Type ?? ''));

        // If sessions.id is already VARCHAR, keep it.
        if (str_starts_with($idType, 'varchar')) {
            return;
        }

        // Rebuild broken sessions table (import scripts may incorrectly make id numeric).
        DB::statement('DROP TABLE `sessions`');

        Schema::create('sessions', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (! Schema::hasTable('sessions')) {
            return;
        }

        // Keep latest schema; no destructive rollback needed for auth/session table.
    }
};

