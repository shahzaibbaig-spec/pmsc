<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('daily_diaries')) {
            return;
        }

        Schema::table('daily_diaries', function (Blueprint $table): void {
            if (! Schema::hasColumn('daily_diaries', 'attachment_path')) {
                $table->string('attachment_path')->nullable()->after('instructions');
            }

            if (! Schema::hasColumn('daily_diaries', 'attachment_name')) {
                $table->string('attachment_name')->nullable()->after('attachment_path');
            }

            if (! Schema::hasColumn('daily_diaries', 'attachment_mime')) {
                $table->string('attachment_mime')->nullable()->after('attachment_name');
            }

            if (! Schema::hasColumn('daily_diaries', 'attachment_size')) {
                $table->unsignedInteger('attachment_size')->nullable()->after('attachment_mime');
            }
        });

        if (! Schema::hasTable('daily_diary_attachments')) {
            return;
        }

        $legacyAttachments = DB::table('daily_diary_attachments')
            ->select('daily_diary_id', 'file_path', 'file_name')
            ->orderBy('id')
            ->get()
            ->groupBy('daily_diary_id')
            ->map(fn ($rows) => $rows->first());

        foreach ($legacyAttachments as $diaryId => $attachment) {
            DB::table('daily_diaries')
                ->where('id', (int) $diaryId)
                ->whereNull('attachment_path')
                ->update([
                    'attachment_path' => $attachment->file_path ?: null,
                    'attachment_name' => $attachment->file_name ?: null,
                ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('daily_diaries')) {
            return;
        }

        Schema::table('daily_diaries', function (Blueprint $table): void {
            if (Schema::hasColumn('daily_diaries', 'attachment_size')) {
                $table->dropColumn('attachment_size');
            }

            if (Schema::hasColumn('daily_diaries', 'attachment_mime')) {
                $table->dropColumn('attachment_mime');
            }

            if (Schema::hasColumn('daily_diaries', 'attachment_name')) {
                $table->dropColumn('attachment_name');
            }

            if (Schema::hasColumn('daily_diaries', 'attachment_path')) {
                $table->dropColumn('attachment_path');
            }
        });
    }
};

