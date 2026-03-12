<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedInteger('capacity')->nullable();
            $table->string('type', 20)->default('classroom');
            $table->timestamps();

            $table->index(['type', 'name']);
        });

        Schema::create('time_slots', function (Blueprint $table): void {
            $table->id();
            $table->string('day_of_week', 3);
            $table->unsignedTinyInteger('slot_index');
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();

            $table->unique(['day_of_week', 'slot_index']);
            $table->index(['slot_index']);
        });

        Schema::create('class_sections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->string('section_name', 10);
            $table->timestamps();

            $table->unique(['class_id', 'section_name']);
            $table->index(['section_name']);
        });

        Schema::create('timetable_constraints', function (Blueprint $table): void {
            $table->id();
            $table->string('session', 20)->unique();
            $table->unsignedTinyInteger('max_periods_per_day_teacher')->default(6);
            $table->unsignedTinyInteger('max_periods_per_week_teacher')->default(28);
            $table->unsignedTinyInteger('max_periods_per_day_class')->default(7);
            $table->timestamps();
        });

        Schema::create('teacher_availability', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->string('day_of_week', 3);
            $table->unsignedTinyInteger('slot_index');
            $table->boolean('is_available')->default(true);
            $table->timestamps();

            $table->unique(['teacher_id', 'day_of_week', 'slot_index'], 'teacher_availability_unique');
            $table->index(['day_of_week', 'slot_index']);
            $table->index(['teacher_id', 'is_available']);
        });

        Schema::create('subject_period_rules', function (Blueprint $table): void {
            $table->id();
            $table->string('session', 20);
            $table->foreignId('class_section_id')->constrained('class_sections')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->unsignedTinyInteger('periods_per_week');
            $table->timestamps();

            $table->unique(['session', 'class_section_id', 'subject_id'], 'subject_period_rules_unique');
            $table->index(['session', 'class_section_id']);
        });

        Schema::create('timetable_entries', function (Blueprint $table): void {
            $table->id();
            $table->string('session', 20);
            $table->foreignId('class_section_id')->constrained('class_sections')->cascadeOnDelete();
            $table->string('day_of_week', 3);
            $table->unsignedTinyInteger('slot_index');
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(
                ['session', 'class_section_id', 'day_of_week', 'slot_index'],
                'timetable_entries_unique'
            );
            $table->index(['session', 'teacher_id']);
            $table->index(['session', 'class_section_id']);
            $table->index(['day_of_week', 'slot_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timetable_entries');
        Schema::dropIfExists('subject_period_rules');
        Schema::dropIfExists('teacher_availability');
        Schema::dropIfExists('timetable_constraints');
        Schema::dropIfExists('class_sections');
        Schema::dropIfExists('time_slots');
        Schema::dropIfExists('rooms');
    }
};

