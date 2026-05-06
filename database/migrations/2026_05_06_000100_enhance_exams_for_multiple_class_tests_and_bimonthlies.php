<?php

use App\Models\Exam;
use App\Modules\Exams\Enums\ExamType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table): void {
            if (! Schema::hasColumn('exams', 'exam_group')) {
                $table->string('exam_group', 40)->nullable()->after('exam_type');
            }

            if (! Schema::hasColumn('exams', 'exam_label')) {
                $table->string('exam_label', 255)->nullable()->after('exam_group');
            }

            if (! Schema::hasColumn('exams', 'topic')) {
                $table->string('topic', 255)->nullable()->after('exam_label');
            }

            if (! Schema::hasColumn('exams', 'sequence_number')) {
                $table->unsignedTinyInteger('sequence_number')->nullable()->after('topic');
            }
        });

        if ($this->hasIndex('exams', 'exams_class_id_subject_id_exam_type_session_unique')) {
            Schema::table('exams', function (Blueprint $table): void {
                $table->dropUnique('exams_class_id_subject_id_exam_type_session_unique');
            });
        }

        $this->backfillExamColumns();

        Schema::table('exams', function (Blueprint $table): void {
            if (! $this->hasIndex('exams', 'exams_class_id_index')) {
                $table->index('class_id', 'exams_class_id_index');
            }

            if (! $this->hasIndex('exams', 'exams_subject_id_index')) {
                $table->index('subject_id', 'exams_subject_id_index');
            }

            if (! $this->hasIndex('exams', 'exams_teacher_id_index')) {
                $table->index('teacher_id', 'exams_teacher_id_index');
            }

            if (! $this->hasIndex('exams', 'exams_session_index')) {
                $table->index('session', 'exams_session_index');
            }

            if (! $this->hasIndex('exams', 'exams_exam_type_index')) {
                $table->index('exam_type', 'exams_exam_type_index');
            }

            if (! $this->hasIndex('exams', 'exams_sequence_number_index')) {
                $table->index('sequence_number', 'exams_sequence_number_index');
            }

            if (! $this->hasIndex('exams', 'exams_scope_type_label_unique')) {
                $table->unique(
                    ['class_id', 'subject_id', 'teacher_id', 'session', 'exam_type', 'exam_label'],
                    'exams_scope_type_label_unique'
                );
            }
        });
    }

    public function down(): void
    {
        if ($this->hasIndex('exams', 'exams_scope_type_label_unique')) {
            Schema::table('exams', function (Blueprint $table): void {
                $table->dropUnique('exams_scope_type_label_unique');
            });
        }

        if ($this->hasIndex('exams', 'exams_sequence_number_index')) {
            Schema::table('exams', function (Blueprint $table): void {
                $table->dropIndex('exams_sequence_number_index');
            });
        }

        if ($this->hasIndex('exams', 'exams_exam_type_index')) {
            Schema::table('exams', function (Blueprint $table): void {
                $table->dropIndex('exams_exam_type_index');
            });
        }

        if ($this->hasIndex('exams', 'exams_session_index')) {
            Schema::table('exams', function (Blueprint $table): void {
                $table->dropIndex('exams_session_index');
            });
        }

        if ($this->hasIndex('exams', 'exams_teacher_id_index')) {
            Schema::table('exams', function (Blueprint $table): void {
                $table->dropIndex('exams_teacher_id_index');
            });
        }

        if ($this->hasIndex('exams', 'exams_subject_id_index')) {
            Schema::table('exams', function (Blueprint $table): void {
                $table->dropIndex('exams_subject_id_index');
            });
        }

        if ($this->hasIndex('exams', 'exams_class_id_index')) {
            Schema::table('exams', function (Blueprint $table): void {
                $table->dropIndex('exams_class_id_index');
            });
        }

        if (! $this->hasIndex('exams', 'exams_class_id_subject_id_exam_type_session_unique')) {
            Schema::table('exams', function (Blueprint $table): void {
                $table->unique(
                    ['class_id', 'subject_id', 'exam_type', 'session'],
                    'exams_class_id_subject_id_exam_type_session_unique'
                );
            });
        }

        Schema::table('exams', function (Blueprint $table): void {
            if (Schema::hasColumn('exams', 'sequence_number')) {
                $table->dropColumn('sequence_number');
            }

            if (Schema::hasColumn('exams', 'topic')) {
                $table->dropColumn('topic');
            }

            if (Schema::hasColumn('exams', 'exam_label')) {
                $table->dropColumn('exam_label');
            }

            if (Schema::hasColumn('exams', 'exam_group')) {
                $table->dropColumn('exam_group');
            }
        });
    }

    private function backfillExamColumns(): void
    {
        DB::transaction(function (): void {
            $exams = Exam::query()
                ->orderBy('id')
                ->get([
                    'id',
                    'exam_type',
                    'exam_group',
                    'exam_label',
                    'topic',
                    'sequence_number',
                ]);

            foreach ($exams as $exam) {
                $examType = (string) ($exam->exam_type instanceof \BackedEnum ? $exam->exam_type->value : $exam->exam_type);
                $sequenceNumber = $exam->sequence_number !== null ? (int) $exam->sequence_number : null;
                $topic = trim((string) ($exam->topic ?? ''));

                if ($examType === ExamType::BimonthlyTest->value && $sequenceNumber === null) {
                    $sequenceNumber = 1;
                }

                $group = match ($examType) {
                    ExamType::ClassTest->value => 'class_test',
                    ExamType::BimonthlyTest->value => 'bimonthly',
                    default => 'terminal',
                };

                $label = trim((string) ($exam->exam_label ?? ''));
                if ($label === '') {
                    $label = match ($examType) {
                        ExamType::ClassTest->value => $topic !== '' ? 'Class Test - '.$topic : 'Class Test',
                        ExamType::BimonthlyTest->value => $this->bimonthlyLabel($sequenceNumber),
                        ExamType::FirstTerm->value => 'Midterm',
                        ExamType::FinalTerm->value => 'Final Term',
                        default => str_replace('_', ' ', ucfirst($examType)),
                    };
                }

                $exam->forceFill([
                    'exam_group' => $group,
                    'exam_label' => $label,
                    'topic' => $topic !== '' ? $topic : null,
                    'sequence_number' => $sequenceNumber,
                ])->save();
            }
        });
    }

    private function bimonthlyLabel(?int $sequenceNumber): string
    {
        return match ((int) ($sequenceNumber ?? 1)) {
            1 => '1st Bimonthly',
            2 => '2nd Bimonthly',
            3 => '3rd Bimonthly',
            4 => '4th Bimonthly',
            default => 'Bimonthly',
        };
    }

    private function hasIndex(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        return match ($driver) {
            'sqlite' => collect($connection->select("PRAGMA index_list('{$table}')"))
                ->contains(fn (object $row): bool => (string) ($row->name ?? '') === $index),
            'mysql', 'mariadb' => $connection->select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index]) !== [],
            'pgsql' => $connection->select(
                'SELECT indexname FROM pg_indexes WHERE schemaname = current_schema() AND tablename = ? AND indexname = ?',
                [$table, $index]
            ) !== [],
            default => false,
        };
    }
};

