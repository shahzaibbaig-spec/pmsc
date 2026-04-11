<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('certificates')) {
            return;
        }

        Schema::create('certificates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('certificate_type', 100);
            $table->string('title');
            $table->text('reason')->nullable();
            $table->string('class_name', 100)->nullable();
            $table->string('section_name', 100)->nullable();
            $table->date('issue_date');
            $table->string('certificate_no', 120)->unique();
            $table->string('chairman_name', 150)->nullable();
            $table->string('principal_name', 150)->nullable();
            $table->string('chairman_title', 150)->nullable();
            $table->string('principal_title', 150)->nullable();
            $table->string('status', 50)->default('issued');
            $table->timestamps();

            $table->index(['student_id', 'certificate_type'], 'certificates_student_type_index');
            $table->index(['issue_date', 'status'], 'certificates_issue_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
