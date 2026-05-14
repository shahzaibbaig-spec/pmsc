<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('principal_teacher_threads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('principal_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->string('subject', 255)->nullable();
            $table->string('related_type', 80)->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->string('status', 20)->default('open');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('principal_id');
            $table->index('teacher_id');
            $table->index('status');
            $table->index(['related_type', 'related_id'], 'principal_teacher_threads_related_index');
        });

        Schema::create('principal_teacher_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('thread_id')->constrained('principal_teacher_threads')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->text('message');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index('thread_id');
            $table->index('sender_id');
            $table->index('read_at');
        });

        Schema::create('principal_teacher_message_attachments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('message_id')->constrained('principal_teacher_messages')->cascadeOnDelete();
            $table->string('file_path', 500);
            $table->string('original_name', 255)->nullable();
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->timestamps();

            $table->index('message_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('principal_teacher_message_attachments');
        Schema::dropIfExists('principal_teacher_messages');
        Schema::dropIfExists('principal_teacher_threads');
    }
};
