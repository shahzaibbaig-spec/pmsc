<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mark_edit_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('mark_id');
            $table->unsignedInteger('old_marks')->nullable();
            $table->unsignedInteger('new_marks')->nullable();
            $table->foreignId('edited_by')->constrained('users')->cascadeOnDelete();
            $table->text('edit_reason');
            $table->string('action_type', 20);
            $table->timestamp('edited_at');

            $table->index(['mark_id', 'action_type']);
            $table->index('edited_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mark_edit_logs');
    }
};
