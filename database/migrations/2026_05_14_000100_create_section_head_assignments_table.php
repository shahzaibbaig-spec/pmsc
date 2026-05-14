<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('section_head_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('section_head_type', 60);
            $table->string('scope', 40);
            $table->string('session', 20);
            $table->string('status', 20)->default('active');
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('teacher_id');
            $table->index('user_id');
            $table->index('scope');
            $table->index('session');
            $table->index('status');
            $table->index(['scope', 'session', 'status'], 'section_head_assignments_scope_session_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('section_head_assignments');
    }
};
