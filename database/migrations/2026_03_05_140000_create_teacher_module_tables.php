<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teachers', function (Blueprint $table): void {
            $table->id();
            $table->string('teacher_id')->unique();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('designation')->nullable();
            $table->string('employee_code')->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('teacher_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->boolean('is_class_teacher')->default(false);
            $table->string('session', 20);
            $table->timestamps();

            $table->index(['teacher_id', 'session']);
            $table->index(['class_id', 'session']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_assignments');
        Schema::dropIfExists('teachers');
    }
};

