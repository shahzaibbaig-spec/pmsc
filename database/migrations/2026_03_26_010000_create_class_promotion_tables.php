<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_campaigns', function (Blueprint $table): void {
            $table->id();
            $table->string('from_session', 20);
            $table->string('to_session', 20);
            $table->foreignId('class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected', 'executed'])->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->text('principal_note')->nullable();
            $table->timestamps();

            $table->unique(['from_session', 'to_session', 'class_id'], 'promotion_campaigns_session_class_unique');
            $table->index(['status', 'submitted_at'], 'promotion_campaigns_status_submitted_index');
        });

        Schema::create('student_promotions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('promotion_campaign_id')->constrained('promotion_campaigns')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('from_class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->foreignId('to_class_id')->nullable()->constrained('school_classes')->nullOnDelete();
            $table->decimal('final_percentage', 5, 2)->nullable();
            $table->string('final_grade', 20)->nullable();
            $table->boolean('is_passed')->default(false);
            $table->enum('teacher_decision', ['promote', 'conditional_promote', 'retain'])->nullable();
            $table->text('teacher_note')->nullable();
            $table->enum('principal_decision', ['promote', 'conditional_promote', 'retain'])->nullable();
            $table->text('principal_note')->nullable();
            $table->enum('final_status', ['pending', 'approved', 'rejected', 'executed'])->default('pending');
            $table->timestamps();

            $table->unique(['promotion_campaign_id', 'student_id'], 'student_promotions_campaign_student_unique');
            $table->index(['promotion_campaign_id', 'final_status'], 'student_promotions_campaign_status_index');
        });

        Schema::create('student_class_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->string('session', 20);
            $table->enum('status', ['active', 'promoted', 'retained', 'conditional_promoted', 'completed'])->default('active');
            $table->date('joined_on')->nullable();
            $table->date('left_on')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'class_id', 'session'], 'student_class_histories_student_class_session_unique');
            $table->index(['class_id', 'session', 'status'], 'student_class_histories_class_session_status_index');
        });

        Schema::create('class_promotion_mappings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('from_class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->foreignId('to_class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['from_class_id', 'to_class_id'], 'class_promotion_mappings_unique');
            $table->index(['from_class_id'], 'class_promotion_mappings_from_class_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_promotion_mappings');
        Schema::dropIfExists('student_class_histories');
        Schema::dropIfExists('student_promotions');
        Schema::dropIfExists('promotion_campaigns');
    }
};

