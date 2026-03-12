<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_subject_assignments', function (Blueprint $table): void {
            $table->id();
            $table->string('session', 20);
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['session', 'student_id', 'subject_id'], 'student_subject_assignments_unique');
            $table->index(['session', 'class_id']);
            $table->index(['session', 'assigned_by']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_subject_assignments');
    }
};
