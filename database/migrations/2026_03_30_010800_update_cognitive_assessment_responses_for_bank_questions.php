<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cognitive_assessment_responses', function (Blueprint $table): void {
            $table->dropUnique('cognitive_assessment_responses_attempt_question_unique');
            $table->dropForeign(['question_id']);
        });

        Schema::table('cognitive_assessment_responses', function (Blueprint $table): void {
            $table->foreignId('question_id')->nullable()->change();
            $table->foreignId('bank_question_id')
                ->nullable()
                ->after('question_id')
                ->constrained('cognitive_bank_questions')
                ->restrictOnDelete();
            $table->foreign('question_id')
                ->references('id')
                ->on('cognitive_assessment_questions')
                ->cascadeOnDelete();
            $table->unique(['attempt_id', 'question_id'], 'cognitive_assessment_responses_attempt_question_unique');
            $table->unique(['attempt_id', 'bank_question_id'], 'cognitive_assessment_responses_attempt_bank_question_unique');
        });
    }

    public function down(): void
    {
        Schema::table('cognitive_assessment_responses', function (Blueprint $table): void {
            $table->dropUnique('cognitive_assessment_responses_attempt_bank_question_unique');
            $table->dropUnique('cognitive_assessment_responses_attempt_question_unique');
            $table->dropForeign(['bank_question_id']);
            $table->dropForeign(['question_id']);
        });

        Schema::table('cognitive_assessment_responses', function (Blueprint $table): void {
            $table->dropColumn('bank_question_id');
            $table->foreignId('question_id')->nullable(false)->change();
            $table->foreign('question_id')
                ->references('id')
                ->on('cognitive_assessment_questions')
                ->cascadeOnDelete();
            $table->unique(['attempt_id', 'question_id'], 'cognitive_assessment_responses_attempt_question_unique');
        });
    }
};
