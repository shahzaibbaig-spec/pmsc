<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('school_classes', 'class_teacher_id')) {
            Schema::table('school_classes', function (Blueprint $table): void {
                $table->foreignId('class_teacher_id')
                    ->nullable()
                    ->after('status')
                    ->constrained('teachers')
                    ->nullOnDelete();
                $table->index(['class_teacher_id']);
            });
        }

        Schema::create('teacher_subject_assignments', function (Blueprint $table): void {
            $table->id();
            $table->string('session', 20);
            $table->foreignId('class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->foreignId('class_section_id')->nullable()->constrained('class_sections')->nullOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->string('group_name', 20)->default('');
            $table->unsignedTinyInteger('lessons_per_week')->default(1);
            $table->timestamps();

            $table->unique(
                ['session', 'class_id', 'subject_id', 'teacher_id', 'group_name'],
                'teacher_subject_assignments_unique'
            );
            $table->index(['session', 'class_id', 'group_name']);
            $table->index(['session', 'teacher_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_subject_assignments');

        if (Schema::hasColumn('school_classes', 'class_teacher_id')) {
            Schema::table('school_classes', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('class_teacher_id');
            });
        }
    }
};

