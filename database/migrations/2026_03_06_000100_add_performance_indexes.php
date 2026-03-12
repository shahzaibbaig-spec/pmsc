<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->index(['status', 'name'], 'users_status_name_index');
            $table->index(['status', 'email'], 'users_status_email_index');
        });

        Schema::table('school_classes', function (Blueprint $table): void {
            $table->index(['name', 'section'], 'school_classes_name_section_index');
            $table->index(['status', 'name'], 'school_classes_status_name_index');
        });

        Schema::table('subjects', function (Blueprint $table): void {
            $table->index(['name'], 'subjects_name_index');
            $table->index(['code'], 'subjects_code_index');
            $table->index(['is_default', 'name'], 'subjects_is_default_name_index');
        });

        Schema::table('students', function (Blueprint $table): void {
            $table->index(['status', 'class_id'], 'students_status_class_id_index');
            $table->index(['status', 'student_id'], 'students_status_student_id_index');
        });

        Schema::table('teacher_assignments', function (Blueprint $table): void {
            $table->index(['session', 'is_class_teacher', 'class_id'], 'teacher_assignments_session_class_teacher_index');
            $table->index(['session', 'subject_id'], 'teacher_assignments_session_subject_index');
        });

        Schema::table('attendance', function (Blueprint $table): void {
            $table->index(['date', 'status'], 'attendance_date_status_index');
        });

        Schema::table('exams', function (Blueprint $table): void {
            $table->index(['session', 'exam_type', 'class_id'], 'exams_session_exam_type_class_index');
        });

        Schema::table('marks', function (Blueprint $table): void {
            $table->index(['session', 'exam_id'], 'marks_session_exam_id_index');
        });

        Schema::table('medical_referrals', function (Blueprint $table): void {
            $table->index(['doctor_id', 'created_at'], 'medical_referrals_doctor_created_index');
        });

        Schema::table('notifications', function (Blueprint $table): void {
            $table->index(['read_at'], 'notifications_read_at_index');
            $table->index(['created_at'], 'notifications_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table): void {
            $table->dropIndex('notifications_read_at_index');
            $table->dropIndex('notifications_created_at_index');
        });

        Schema::table('medical_referrals', function (Blueprint $table): void {
            $table->dropIndex('medical_referrals_doctor_created_index');
        });

        Schema::table('marks', function (Blueprint $table): void {
            $table->dropIndex('marks_session_exam_id_index');
        });

        Schema::table('exams', function (Blueprint $table): void {
            $table->dropIndex('exams_session_exam_type_class_index');
        });

        Schema::table('attendance', function (Blueprint $table): void {
            $table->dropIndex('attendance_date_status_index');
        });

        Schema::table('teacher_assignments', function (Blueprint $table): void {
            $table->dropIndex('teacher_assignments_session_class_teacher_index');
            $table->dropIndex('teacher_assignments_session_subject_index');
        });

        Schema::table('students', function (Blueprint $table): void {
            $table->dropIndex('students_status_class_id_index');
            $table->dropIndex('students_status_student_id_index');
        });

        Schema::table('subjects', function (Blueprint $table): void {
            $table->dropIndex('subjects_name_index');
            $table->dropIndex('subjects_code_index');
            $table->dropIndex('subjects_is_default_name_index');
        });

        Schema::table('school_classes', function (Blueprint $table): void {
            $table->dropIndex('school_classes_name_section_index');
            $table->dropIndex('school_classes_status_name_index');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex('users_status_name_index');
            $table->dropIndex('users_status_email_index');
        });
    }
};

