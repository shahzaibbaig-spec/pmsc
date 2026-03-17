<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_fee_structures', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('session', 20);
            $table->decimal('tuition_fee', 10, 2)->default(0);
            $table->decimal('computer_fee', 10, 2)->default(0);
            $table->decimal('exam_fee', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['student_id', 'session'], 'student_fee_structures_student_session_unique');
            $table->index(['session', 'is_active'], 'student_fee_structures_session_active_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_fee_structures');
    }
};
