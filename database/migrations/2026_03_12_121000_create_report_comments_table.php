<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('session', 20);
            $table->string('exam_type', 40);
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('auto_comment')->nullable();
            $table->text('final_comment')->nullable();
            $table->boolean('is_edited')->default(false);
            $table->timestamps();

            $table->unique(['student_id', 'session', 'exam_type']);
            $table->index(['session', 'exam_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_comments');
    }
};

