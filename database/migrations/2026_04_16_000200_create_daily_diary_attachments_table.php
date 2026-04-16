<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_diary_attachments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('daily_diary_id')->constrained('daily_diaries')->cascadeOnDelete();
            $table->string('file_path');
            $table->string('file_name')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_diary_attachments');
    }
};

