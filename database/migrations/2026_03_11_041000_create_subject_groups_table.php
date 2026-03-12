<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subject_groups', function (Blueprint $table): void {
            $table->id();
            $table->string('session', 20);
            $table->foreignId('class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('description', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['session', 'class_id', 'name'], 'subject_groups_session_class_name_unique');
            $table->index(['session', 'class_id', 'is_active'], 'subject_groups_session_class_active_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_groups');
    }
};

