<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_sports_observations', function (Blueprint $table): void {
            $table->text('combined_auto_message')->nullable()->after('auto_message');
            $table->text('custom_note')->nullable()->after('combined_auto_message');
        });

        Schema::create('student_sports_observation_issues', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_sports_observation_id')
                ->constrained('student_sports_observations')
                ->cascadeOnDelete();
            $table->string('issue_type', 100);
            $table->string('issue_label', 190);
            $table->text('auto_message');
            $table->timestamps();

            $table->index('student_sports_observation_id', 'sports_obs_issues_parent_idx');
            $table->index('issue_type', 'sports_obs_issues_type_idx');
            $table->unique(['student_sports_observation_id', 'issue_type'], 'sports_obs_issues_unique_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_sports_observation_issues');

        Schema::table('student_sports_observations', function (Blueprint $table): void {
            $table->dropColumn(['combined_auto_message', 'custom_note']);
        });
    }
};

