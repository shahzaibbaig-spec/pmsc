<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_scales', function (Blueprint $table): void {
            $table->id();
            $table->string('grade_code', 10)->unique();
            $table->string('label', 100);
            $table->decimal('percentage_equivalent', 5, 2);
            $table->decimal('grade_point', 4, 2);
            $table->integer('sort_order');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order'], 'grade_scales_active_sort_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_scales');
    }
};
