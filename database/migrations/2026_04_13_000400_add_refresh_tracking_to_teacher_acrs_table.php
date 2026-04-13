<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('teacher_acrs')) {
            return;
        }

        $addNeedsRefresh = ! Schema::hasColumn('teacher_acrs', 'needs_refresh');
        $addLastMetricsRefreshAt = ! Schema::hasColumn('teacher_acrs', 'last_metrics_refresh_at');

        if (! $addNeedsRefresh && ! $addLastMetricsRefreshAt) {
            return;
        }

        Schema::table('teacher_acrs', function (Blueprint $table) use ($addNeedsRefresh, $addLastMetricsRefreshAt): void {
            if ($addNeedsRefresh) {
                $table->boolean('needs_refresh')->default(false)->after('finalized_at');
            }

            if ($addLastMetricsRefreshAt) {
                $table->timestamp('last_metrics_refresh_at')->nullable()->after('needs_refresh');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('teacher_acrs')) {
            return;
        }

        $dropLastMetricsRefreshAt = Schema::hasColumn('teacher_acrs', 'last_metrics_refresh_at');
        $dropNeedsRefresh = Schema::hasColumn('teacher_acrs', 'needs_refresh');

        if (! $dropLastMetricsRefreshAt && ! $dropNeedsRefresh) {
            return;
        }

        Schema::table('teacher_acrs', function (Blueprint $table) use ($dropLastMetricsRefreshAt, $dropNeedsRefresh): void {
            if ($dropLastMetricsRefreshAt) {
                $table->dropColumn('last_metrics_refresh_at');
            }

            if ($dropNeedsRefresh) {
                $table->dropColumn('needs_refresh');
            }
        });
    }
};
