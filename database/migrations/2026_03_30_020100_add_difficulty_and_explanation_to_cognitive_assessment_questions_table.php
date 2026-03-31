<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cognitive_assessment_questions', function (Blueprint $table): void {
            if (! Schema::hasColumn('cognitive_assessment_questions', 'difficulty_level')) {
                $table->string('difficulty_level', 50)->nullable()->after('question_type');
            }

            if (! Schema::hasColumn('cognitive_assessment_questions', 'explanation')) {
                $table->text('explanation')->nullable()->after('correct_answer');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cognitive_assessment_questions', function (Blueprint $table): void {
            if (Schema::hasColumn('cognitive_assessment_questions', 'difficulty_level')) {
                $table->dropColumn('difficulty_level');
            }

            if (Schema::hasColumn('cognitive_assessment_questions', 'explanation')) {
                $table->dropColumn('explanation');
            }
        });
    }
};
