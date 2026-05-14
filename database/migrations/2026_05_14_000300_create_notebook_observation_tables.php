<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notebook_observations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('observed_teacher_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('observer_id')->constrained('users')->restrictOnDelete();
            $table->string('observer_role', 80)->nullable();
            $table->string('session', 20);
            $table->date('observation_date');
            $table->foreignId('class_id')->nullable()->constrained('school_classes')->nullOnDelete();
            $table->string('class_section', 80)->nullable();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->unsignedInteger('total_students')->nullable();
            $table->unsignedInteger('notebooks_provided')->nullable();
            $table->unsignedInteger('covered_notebooks')->nullable();
            $table->unsignedInteger('uncovered_notebooks')->nullable();
            $table->unsignedInteger('well_maintained')->nullable();
            $table->text('general_comments')->nullable();
            $table->unsignedInteger('total_yes')->default(0);
            $table->unsignedInteger('total_no')->default(0);
            $table->decimal('performance_score', 6, 2)->nullable();
            $table->string('status', 30)->default('submitted');
            $table->text('teacher_comments')->nullable();
            $table->timestamp('teacher_commented_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('observed_teacher_id');
            $table->index('observer_id');
            $table->index('session');
            $table->index('observation_date');
            $table->index('status');
        });

        Schema::create('notebook_observation_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('notebook_observation_id')->constrained('notebook_observations')->cascadeOnDelete();
            $table->text('checklist_text');
            $table->string('response', 10)->nullable();
            $table->text('comments')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('notebook_observation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notebook_observation_items');
        Schema::dropIfExists('notebook_observations');
    }
};
