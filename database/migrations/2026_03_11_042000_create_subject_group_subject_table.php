<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subject_group_subject', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('subject_group_id')->constrained('subject_groups')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['subject_group_id', 'subject_id'], 'subject_group_subject_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_group_subject');
    }
};

