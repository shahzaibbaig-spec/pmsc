<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_observations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('observed_teacher_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('observer_id')->constrained('users')->restrictOnDelete();
            $table->string('observer_role', 80)->nullable();
            $table->string('session', 20);
            $table->date('observation_date');
            $table->string('school', 190)->nullable();
            $table->string('subject_topic', 255)->nullable();
            $table->foreignId('class_id')->nullable()->constrained('school_classes')->nullOnDelete();
            $table->string('class_section', 80)->nullable();
            $table->unsignedInteger('no_of_students')->nullable();
            $table->text('learning_objectives')->nullable();
            $table->text('previous_targets')->nullable();
            $table->text('what_went_well')->nullable();
            $table->text('even_better_if')->nullable();
            $table->decimal('progress_percentage', 6, 2)->nullable();
            $table->string('overall_judgment', 40)->nullable();
            $table->decimal('total_marks', 8, 2)->default(0);
            $table->decimal('max_marks', 8, 2)->default(0);
            $table->decimal('performance_score', 6, 2)->nullable();
            $table->string('status', 30)->default('submitted');
            $table->text('teacher_comments')->nullable();
            $table->timestamp('teacher_commented_at')->nullable();
            $table->boolean('teacher_signature_acknowledged')->default(false);
            $table->boolean('observer_signature_acknowledged')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('observed_teacher_id');
            $table->index('observer_id');
            $table->index('session');
            $table->index('observation_date');
            $table->index('status');
        });

        Schema::create('lesson_observation_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lesson_observation_id')->constrained('lesson_observations')->cascadeOnDelete();
            $table->string('area', 120);
            $table->text('standard_text');
            $table->integer('mark')->default(0);
            $table->integer('max_mark')->default(1);
            $table->text('comments')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('lesson_observation_id');
            $table->index('area');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_observation_items');
        Schema::dropIfExists('lesson_observations');
    }
};
