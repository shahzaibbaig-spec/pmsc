<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_challans', function (Blueprint $table): void {
            if (! Schema::hasColumn('fee_challans', 'arrears')) {
                $table->decimal('arrears', 10, 2)->default(0)->after('due_date');
            }

            if (! Schema::hasColumn('fee_challans', 'late_fee')) {
                $table->decimal('late_fee', 10, 2)->default(0)->after('arrears');
            }

            if (! Schema::hasColumn('fee_challans', 'late_fee_waived_at')) {
                $table->timestamp('late_fee_waived_at')->nullable()->after('late_fee');
            }
        });

        DB::table('fee_challans')
            ->where('status', 'partially_paid')
            ->update(['status' => 'partial']);

        DB::table('fee_challans')
            ->whereNull('status')
            ->update(['status' => 'unpaid']);
    }

    public function down(): void
    {
        DB::table('fee_challans')
            ->where('status', 'partial')
            ->update(['status' => 'partially_paid']);

        Schema::table('fee_challans', function (Blueprint $table): void {
            if (Schema::hasColumn('fee_challans', 'late_fee_waived_at')) {
                $table->dropColumn('late_fee_waived_at');
            }

            if (Schema::hasColumn('fee_challans', 'late_fee')) {
                $table->dropColumn('late_fee');
            }

            if (Schema::hasColumn('fee_challans', 'arrears')) {
                $table->dropColumn('arrears');
            }
        });
    }
};

