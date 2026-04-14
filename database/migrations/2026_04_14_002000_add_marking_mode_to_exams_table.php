<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('exams') || Schema::hasColumn('exams', 'marking_mode')) {
            return;
        }

        Schema::table('exams', function (Blueprint $table): void {
            $table->enum('marking_mode', ['numeric', 'grade'])
                ->default('numeric')
                ->after('session');
        });

        if (! Schema::hasTable('school_classes')) {
            return;
        }

        $earlyYears = ['pg', 'prep', 'nursery', '1', 'class 1'];
        $earlyYearsClassIds = DB::table('school_classes')
            ->select('id', 'name')
            ->get()
            ->filter(function ($class) use ($earlyYears): bool {
                $normalized = strtolower(trim((string) ($class->name ?? '')));
                $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

                return in_array($normalized, $earlyYears, true);
            })
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        if ($earlyYearsClassIds !== []) {
            DB::table('exams')
                ->whereIn('class_id', $earlyYearsClassIds)
                ->update(['marking_mode' => 'grade']);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('exams') || ! Schema::hasColumn('exams', 'marking_mode')) {
            return;
        }

        Schema::table('exams', function (Blueprint $table): void {
            $table->dropColumn('marking_mode');
        });
    }
};

