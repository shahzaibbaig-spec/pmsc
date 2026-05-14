<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_performance_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->restrictOnDelete();
            $table->string('source_type', 60);
            $table->unsignedBigInteger('source_id');
            $table->string('session', 20);
            $table->decimal('score', 8, 2);
            $table->decimal('max_score', 8, 2)->nullable();
            $table->decimal('percentage', 6, 2);
            $table->string('judgment', 40)->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('recorded_at')->nullable();
            $table->timestamps();

            $table->index('teacher_id');
            $table->index(['source_type', 'source_id'], 'teacher_performance_events_source_index');
            $table->index('session');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_performance_events');
    }
};
