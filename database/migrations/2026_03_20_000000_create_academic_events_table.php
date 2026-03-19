<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_events', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('type', 40);
            $table->boolean('notify_before')->default(false);
            $table->unsignedSmallInteger('notify_days_before')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['type', 'start_date']);
            $table->index(['notify_before', 'start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_events');
    }
};

