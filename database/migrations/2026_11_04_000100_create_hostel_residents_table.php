<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hostel_residents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hostel_id')->constrained('hostels')->restrictOnDelete();
            $table->string('name');
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 20)->default('male');
            $table->string('room_group', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->string('import_key', 64)->nullable()->unique();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['hostel_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hostel_residents');
    }
};
