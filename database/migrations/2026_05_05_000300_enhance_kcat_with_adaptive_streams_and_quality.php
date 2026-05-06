<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('kcat_tests')) {
            Schema::table('kcat_tests', function (Blueprint $table): void {
                if (! Schema::hasColumn('kcat_tests', 'is_adaptive_enabled')) {
                    $table->boolean('is_adaptive_enabled')->default(false)->after('status');
                }
                if (! Schema::hasColumn('kcat_tests', 'questions_per_section')) {
                    $table->unsignedInteger('questions_per_section')->default(10)->after('is_adaptive_enabled');
                }
            });
        }

        if (Schema::hasTable('kcat_attempts')) {
            Schema::table('kcat_attempts', function (Blueprint $table): void {
                if (! Schema::hasColumn('kcat_attempts', 'is_adaptive')) {
                    $table->boolean('is_adaptive')->default(false)->after('status');
                }
                if (! Schema::hasColumn('kcat_attempts', 'current_section_id')) {
                    $table->foreignId('current_section_id')->nullable()->after('is_adaptive')->constrained('kcat_sections')->nullOnDelete();
                }
                if (! Schema::hasColumn('kcat_attempts', 'current_difficulty')) {
                    $table->string('current_difficulty', 20)->nullable()->after('current_section_id');
                }
                if (! Schema::hasColumn('kcat_attempts', 'adaptive_state')) {
                    $table->json('adaptive_state')->nullable()->after('current_difficulty');
                }
                if (! Schema::hasColumn('kcat_attempts', 'counselor_override_stream')) {
                    $table->string('counselor_override_stream')->nullable()->after('recommendation_summary');
                }
                if (! Schema::hasColumn('kcat_attempts', 'counselor_override_reason')) {
                    $table->text('counselor_override_reason')->nullable()->after('counselor_override_stream');
                }
                if (! Schema::hasColumn('kcat_attempts', 'override_by')) {
                    $table->foreignId('override_by')->nullable()->after('counselor_override_reason')->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn('kcat_attempts', 'override_at')) {
                    $table->timestamp('override_at')->nullable()->after('override_by');
                }
            });
        }

        if (Schema::hasTable('kcat_answers')) {
            Schema::table('kcat_answers', function (Blueprint $table): void {
                if (! Schema::hasColumn('kcat_answers', 'difficulty_at_time')) {
                    $table->string('difficulty_at_time', 20)->nullable()->after('marks_awarded');
                }
                if (! Schema::hasColumn('kcat_answers', 'answered_at')) {
                    $table->timestamp('answered_at')->nullable()->after('difficulty_at_time');
                }
                if (! Schema::hasColumn('kcat_answers', 'response_time_seconds')) {
                    $table->integer('response_time_seconds')->nullable()->after('answered_at');
                }
            });
        }

        if (Schema::hasTable('kcat_questions')) {
            Schema::table('kcat_questions', function (Blueprint $table): void {
                if (! Schema::hasColumn('kcat_questions', 'review_status')) {
                    $table->string('review_status', 30)->default('pending')->after('is_active');
                }
                if (! Schema::hasColumn('kcat_questions', 'times_attempted')) {
                    $table->unsignedInteger('times_attempted')->default(0)->after('review_status');
                }
                if (! Schema::hasColumn('kcat_questions', 'times_correct')) {
                    $table->unsignedInteger('times_correct')->default(0)->after('times_attempted');
                }
                if (! Schema::hasColumn('kcat_questions', 'average_response_time')) {
                    $table->decimal('average_response_time', 8, 2)->nullable()->after('times_correct');
                }
                if (! Schema::hasColumn('kcat_questions', 'discrimination_flag')) {
                    $table->string('discrimination_flag', 40)->nullable()->after('average_response_time');
                }
                if (! Schema::hasColumn('kcat_questions', 'retired_at')) {
                    $table->timestamp('retired_at')->nullable()->after('discrimination_flag');
                }
                if (! Schema::hasColumn('kcat_questions', 'retired_by')) {
                    $table->foreignId('retired_by')->nullable()->after('retired_at')->constrained('users')->nullOnDelete();
                }
            });
        }

        if (Schema::hasTable('kcat_attempts') && ! Schema::hasTable('kcat_stream_recommendations')) {
            Schema::create('kcat_stream_recommendations', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('kcat_attempt_id')->constrained('kcat_attempts')->cascadeOnDelete();
                $table->string('stream_name');
                $table->decimal('match_score', 6, 2);
                $table->string('confidence_band', 30)->nullable();
                $table->text('reasoning_summary')->nullable();
                $table->unsignedInteger('rank')->default(1);
                $table->timestamps();
                $table->index(['kcat_attempt_id', 'rank']);
            });
        }

        if (Schema::hasTable('kcat_questions') && ! Schema::hasTable('kcat_question_reviews')) {
            Schema::create('kcat_question_reviews', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('kcat_question_id')->constrained('kcat_questions')->cascadeOnDelete();
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('status', 30)->default('pending');
                $table->string('difficulty_review', 20)->nullable();
                $table->unsignedTinyInteger('clarity_score')->nullable();
                $table->unsignedTinyInteger('quality_score')->nullable();
                $table->text('issue_notes')->nullable();
                $table->string('action_taken')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();
                $table->index(['kcat_question_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('kcat_stream_recommendations')) {
            Schema::dropIfExists('kcat_stream_recommendations');
        }

        if (Schema::hasTable('kcat_question_reviews')) {
            Schema::dropIfExists('kcat_question_reviews');
        }

        if (Schema::hasTable('kcat_questions')) {
            Schema::table('kcat_questions', function (Blueprint $table): void {
                if (Schema::hasColumn('kcat_questions', 'retired_by')) {
                    $table->dropConstrainedForeignId('retired_by');
                }
                $dropColumns = [
                    'review_status',
                    'times_attempted',
                    'times_correct',
                    'average_response_time',
                    'discrimination_flag',
                    'retired_at',
                ];
                $existing = array_values(array_filter($dropColumns, fn (string $column): bool => Schema::hasColumn('kcat_questions', $column)));
                if ($existing !== []) {
                    $table->dropColumn($existing);
                }
            });
        }

        if (Schema::hasTable('kcat_answers')) {
            Schema::table('kcat_answers', function (Blueprint $table): void {
                $dropColumns = ['difficulty_at_time', 'answered_at', 'response_time_seconds'];
                $existing = array_values(array_filter($dropColumns, fn (string $column): bool => Schema::hasColumn('kcat_answers', $column)));
                if ($existing !== []) {
                    $table->dropColumn($existing);
                }
            });
        }

        if (Schema::hasTable('kcat_attempts')) {
            Schema::table('kcat_attempts', function (Blueprint $table): void {
                if (Schema::hasColumn('kcat_attempts', 'current_section_id')) {
                    $table->dropConstrainedForeignId('current_section_id');
                }
                if (Schema::hasColumn('kcat_attempts', 'override_by')) {
                    $table->dropConstrainedForeignId('override_by');
                }
                $dropColumns = [
                    'is_adaptive',
                    'current_difficulty',
                    'adaptive_state',
                    'counselor_override_stream',
                    'counselor_override_reason',
                    'override_at',
                ];
                $existing = array_values(array_filter($dropColumns, fn (string $column): bool => Schema::hasColumn('kcat_attempts', $column)));
                if ($existing !== []) {
                    $table->dropColumn($existing);
                }
            });
        }

        if (Schema::hasTable('kcat_tests')) {
            Schema::table('kcat_tests', function (Blueprint $table): void {
                $dropColumns = ['is_adaptive_enabled', 'questions_per_section'];
                $existing = array_values(array_filter($dropColumns, fn (string $column): bool => Schema::hasColumn('kcat_tests', $column)));
                if ($existing !== []) {
                    $table->dropColumn($existing);
                }
            });
        }
    }
};

