-- MySQL export generated from SQLite database
-- Source DB: C:/Users/Shahzaib/Desktop/nsms\database\nsms_fresh_20260326.sqlite
-- Generated at: 2026-03-27 00:58:54
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `academic_events`;
CREATE TABLE `academic_events` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` TEXT NOT NULL,
  `description` TEXT,
  `start_date` DATE NOT NULL,
  `end_date` DATE,
  `type` TEXT NOT NULL,
  `notify_before` BIGINT NOT NULL DEFAULT '0',
  `notify_days_before` BIGINT NOT NULL DEFAULT '0',
  `created_by` BIGINT,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  KEY `academic_events_notify_before_start_date_index` (`notify_before`, `start_date`),
  KEY `academic_events_type_start_date_index` (`type`, `start_date`),
  CONSTRAINT `fk_academic_events_created_by_users_id` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE NO ACTION ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `academic_notifications`;
CREATE TABLE `academic_notifications` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT NOT NULL,
  `event_id` BIGINT,
  `title` TEXT NOT NULL,
  `message` TEXT NOT NULL,
  `is_read` BIGINT NOT NULL DEFAULT '0',
  `sent_at` DATETIME,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  KEY `academic_notifications_event_id_sent_at_index` (`event_id`, `sent_at`),
  KEY `academic_notifications_sent_at_index` (`sent_at`),
  KEY `academic_notifications_user_id_is_read_index` (`user_id`, `is_read`),
  CONSTRAINT `fk_academic_notifications_event_id_academic_events_id` FOREIGN KEY (`event_id`) REFERENCES `academic_events` (`id`) ON UPDATE NO ACTION ON DELETE SET NULL,
  CONSTRAINT `fk_academic_notifications_user_id_users_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `admit_card_overrides`;
CREATE TABLE `admit_card_overrides` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` BIGINT NOT NULL,
  `exam_session_id` BIGINT NOT NULL,
  `is_allowed` BIGINT NOT NULL DEFAULT '1',
  `reason` TEXT,
  `approved_by` BIGINT,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  KEY `admit_card_overrides_session_allowed_index` (`exam_session_id`, `is_allowed`),
  UNIQUE KEY `admit_card_overrides_student_exam_session_unique` (`student_id`, `exam_session_id`),
  CONSTRAINT `fk_admit_card_overrides_approved_by_users_id` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON UPDATE NO ACTION ON DELETE SET NULL,
  CONSTRAINT `fk_admit_card_overrides_exam_session_id_exam_sessions_id` FOREIGN KEY (`exam_session_id`) REFERENCES `exam_sessions` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
  CONSTRAINT `fk_admit_card_overrides_student_id_students_id` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `attendance`;
CREATE TABLE `attendance` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` BIGINT NOT NULL,
  `class_id` BIGINT NOT NULL,
  `date` DATE NOT NULL,
  `status` TEXT NOT NULL,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  KEY `attendance_date_status_index` (`date`, `status`),
  KEY `attendance_student_id_date_index` (`student_id`, `date`),
  KEY `attendance_class_id_date_index` (`class_id`, `date`),
  UNIQUE KEY `attendance_student_id_date_unique` (`student_id`, `date`),
  CONSTRAINT `fk_attendance_class_id_school_classes_id` FOREIGN KEY (`class_id`) REFERENCES `school_classes` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
  CONSTRAINT `fk_attendance_student_id_students_id` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `cache`;
CREATE TABLE `cache` (
  `key` TEXT NOT NULL,
  `value` TEXT NOT NULL,
  `expiration` BIGINT NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES ('spatie.permission.cache', 'a:3:{s:5:"alias";a:4:{s:1:"a";s:2:"id";s:1:"b";s:4:"name";s:1:"c";s:10:"guard_name";s:1:"r";s:5:"roles";}s:11:"permissions";a:34:{i:0;a:4:{s:1:"a";i:1;s:1:"b";s:12:"manage_users";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:1;a:4:{s:1:"a";i:2;s:1:"b";s:22:"manage_school_settings";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:2;a:4:{s:1:"a";i:3;s:1:"b";s:12:"assign_roles";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:3;a:4:{s:1:"a";i:4;s:1:"b";s:15:"manage_subjects";s:1:"c";s:3:"web";s:1:"r";a:2:{i:0;i:1;i:1;i:2;}}i:4;a:4:{s:1:"a";i:5;s:1:"b";s:15:"assign_subjects";s:1:"c";s:3:"web";s:1:"r";a:2:{i:0;i:1;i:1;i:2;}}i:5;a:4:{s:1:"a";i:6;s:1:"b";s:26:"manage_subject_assignments";s:1:"c";s:3:"web";s:1:"r";a:2:{i:0;i:1;i:1;i:2;}}i:6;a:4:{s:1:"a";i:7;s:1:"b";s:15:"assign_teachers";s:1:"c";s:3:"web";s:1:"r";a:2:{i:0;i:1;i:1;i:2;}}i:7;a:4:{s:1:"a";i:8;s:1:"b";s:15:"view_attendance";s:1:"c";s:3:"web";s:1:"r";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:8;a:4:{s:1:"a";i:9;s:1:"b";s:15:"mark_attendance";s:1:"c";s:3:"web";s:1:"r";a:2:{i:0;i:1;i:1;i:3;}}i:9;a:4:{s:1:"a";i:10;s:1:"b";s:11:"enter_marks";s:1:"c";s:3:"web";s:1:"r";a:2:{i:0;i:1;i:1;i:3;}}i:10;a:4:{s:1:"a";i:11;s:1:"b";s:21:"view_own_mark_entries";s:1:"c";s:3:"web";s:1:"r";a:2:{i:0;i:1;i:1;i:3;}}i:11;a:4:{s:1:"a";i:12;s:1:"b";s:21:"edit_own_mark_entries";s:1:"c";s:3:"web";s:1:"r";a:2:{i:0;i:1;i:1;i:3;}}i:12;a:4:{s:1:"a";i:13;s:1:"b";s:23:"delete_own_mark_entries";s:1:"c";s:3:"web";s:1:"r";a:2:{i:0;i:1;i:1;i:3;}}i:13;a:4:{s:1:"a";i:14;s:1:"b";s:19:"view_mark_edit_logs";s:1:"c";s:3:"web";s:1:"r";a:2:{i:0;i:1;i:1;i:2;}}i:14;a:4:{s:1:"a";i:15;s:1:"b";s:16:"generate_results";s:1:"c";s:3:"web";s:1:"r";a:2:{i:0;i:1;i:1;i:2;}}i:15;a:4:{s:1:"a";i:16;s:1:"b";s:21:"view_medical_requests";s:1:"c";s:3:"web";s:1:"r";a:3:{i:0;i:1;i:1;i:2;i:2;i:4;}}i:16;a:4:{s:1:"a";i:17;s:1:"b";s:23:"create_medical_requests";s:1:"c";s:3:"web";s:1:"r";a:2:{i:0;i:1;i:1;i:2;}}i:17;a:4:{s:1:"a";i:18;s:1:"b";s:24:"view_teacher_performance";s:1:"c";s:3:"web";s:1:"r";a:2:{i:0;i:1;i:1;i:2;}}i:18;a:4:{s:1:"a";i:19;s:1:"b";s:18:"view_fee_structure";s:1:"c";s:3:"web";s:1:"r";a:2:{i:0;i:1;i:1;i:6;}}i:19;a:4:{s:1:"a";i:20;s:1:"b";s:20:"create_fee_structure";s:1:"c";s:3:"web";s:1:"r";a:2:{i:0;i:1;i:1;i:6;}}i:20;a:4:{s:1:"a";i:21;s:1:"b";s:18:"edit_fee_structure";s:1:"c";s:3:"web";s:1:"r";a:2:{i:0;i:1;i:1;i:6;}}i:21;a:4:{s:1:"a";i:22;s:1:"b";s:20:"delete_fee_structure";s:1:"c";s:3:"web";s:1:"r";a:2:{i:0;i:1;i:1;i:6;}}i:22;a:4:{s:1:"a";i:23;s:1:"b";s:21:"generate_fee_challans";s:1:"c";s:3:"web";s:1:"r";a:2:{i:0;i:1;i:1;i:6;}}i:23;a:4:{s:1:"a";i:24;s:1:"b";s:17:"view_fee_challans";s:1:"c";s:3:"web";s:1:"r";a:2:{i:0;i:1;i:1;i:6;}}i:24;a:4:{s:1:"a";i:25;s:1:"b";s:18:"record_fee_payment";s:1:"c";s:3:"web";s:1:"r";a:2:{i:0;i:1;i:1;i:6;}}i:25;a:4:{s:1:"a";i:26;s:1:"b";s:16:"view_fee_reports";s:1:"c";s:3:"web";s:1:"r";a:2:{i:0;i:1;i:1;i:6;}}i:26;a:4:{s:1:"a";i:27;s:1:"b";s:12:"view_payroll";s:1:"c";s:3:"web";s:1:"r";a:2:{i:0;i:1;i:1;i:6;}}i:27;a:4:{s:1:"a";i:28;s:1:"b";s:14:"manage_payroll";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:28;a:4:{s:1:"a";i:29;s:1:"b";s:21:"generate_salary_sheet";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:29;a:4:{s:1:"a";i:30;s:1:"b";s:17:"view_salary_slips";s:1:"c";s:3:"web";s:1:"r";a:2:{i:0;i:1;i:1;i:6;}}i:30;a:4:{s:1:"a";i:31;s:1:"b";s:21:"edit_salary_structure";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:31;a:4:{s:1:"a";i:32;s:1:"b";s:23:"manage_payroll_profiles";s:1:"c";s:3:"web";s:1:"r";a:2:{i:0;i:1;i:1;i:6;}}i:32;a:4:{s:1:"a";i:33;s:1:"b";s:16:"generate_payroll";s:1:"c";s:3:"web";s:1:"r";a:2:{i:0;i:1;i:1;i:6;}}i:33;a:4:{s:1:"a";i:34;s:1:"b";s:20:"view_payroll_reports";s:1:"c";s:3:"web";s:1:"r";a:2:{i:0;i:1;i:1;i:6;}}}s:5:"roles";a:5:{i:0;a:3:{s:1:"a";i:1;s:1:"b";s:5:"Admin";s:1:"c";s:3:"web";}i:1;a:3:{s:1:"a";i:2;s:1:"b";s:9:"Principal";s:1:"c";s:3:"web";}i:2;a:3:{s:1:"a";i:3;s:1:"b";s:7:"Teacher";s:1:"c";s:3:"web";}i:3;a:3:{s:1:"a";i:4;s:1:"b";s:6:"Doctor";s:1:"c";s:3:"web";}i:4;a:3:{s:1:"a";i:6;s:1:"b";s:10:"Accountant";s:1:"c";s:3:"web";}}}', 1774639109);
INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES ('school_settings_single', 'TzoyNDoiQXBwXE1vZGVsc1xTY2hvb2xTZXR0aW5nIjozMDp7czoxMzoiACoAY29ubmVjdGlvbiI7czo2OiJzcWxpdGUiO3M6ODoiACoAdGFibGUiO3M6MTU6InNjaG9vbF9zZXR0aW5ncyI7czoxMzoiACoAcHJpbWFyeUtleSI7czoyOiJpZCI7czoxMDoiACoAa2V5VHlwZSI7czozOiJpbnQiO3M6MTI6ImluY3JlbWVudGluZyI7YjoxO3M6NzoiACoAd2l0aCI7YTowOnt9czoxMjoiACoAd2l0aENvdW50IjthOjA6e31zOjE5OiJwcmV2ZW50c0xhenlMb2FkaW5nIjtiOjA7czoxMDoiACoAcGVyUGFnZSI7aToxNTtzOjY6ImV4aXN0cyI7YjoxO3M6MTg6Indhc1JlY2VudGx5Q3JlYXRlZCI7YjowO3M6Mjg6IgAqAGVzY2FwZVdoZW5DYXN0aW5nVG9TdHJpbmciO2I6MDtzOjEzOiIAKgBhdHRyaWJ1dGVzIjthOjk6e3M6MjoiaWQiO2k6MTtzOjExOiJzY2hvb2xfbmFtZSI7czoxNToiTmF0aW9uYWwgU2Nob29sIjtzOjk6ImxvZ29fcGF0aCI7TjtzOjc6ImFkZHJlc3MiO047czo1OiJwaG9uZSI7TjtzOjU6ImVtYWlsIjtOO3M6Mjg6ImJsb2NrX3Jlc3VsdHNfZm9yX2RlZmF1bHRlcnMiO2k6MDtzOjMxOiJibG9ja19hZG1pdF9jYXJkX2Zvcl9kZWZhdWx0ZXJzIjtpOjA7czoyODoiYmxvY2tfaWRfY2FyZF9mb3JfZGVmYXVsdGVycyI7aTowO31zOjExOiIAKgBvcmlnaW5hbCI7YTo5OntzOjI6ImlkIjtpOjE7czoxMToic2Nob29sX25hbWUiO3M6MTU6Ik5hdGlvbmFsIFNjaG9vbCI7czo5OiJsb2dvX3BhdGgiO047czo3OiJhZGRyZXNzIjtOO3M6NToicGhvbmUiO047czo1OiJlbWFpbCI7TjtzOjI4OiJibG9ja19yZXN1bHRzX2Zvcl9kZWZhdWx0ZXJzIjtpOjA7czozMToiYmxvY2tfYWRtaXRfY2FyZF9mb3JfZGVmYXVsdGVycyI7aTowO3M6Mjg6ImJsb2NrX2lkX2NhcmRfZm9yX2RlZmF1bHRlcnMiO2k6MDt9czoxMDoiACoAY2hhbmdlcyI7YTowOnt9czo4OiIAKgBjYXN0cyI7YTozOntzOjI4OiJibG9ja19yZXN1bHRzX2Zvcl9kZWZhdWx0ZXJzIjtzOjc6ImJvb2xlYW4iO3M6MzE6ImJsb2NrX2FkbWl0X2NhcmRfZm9yX2RlZmF1bHRlcnMiO3M6NzoiYm9vbGVhbiI7czoyODoiYmxvY2tfaWRfY2FyZF9mb3JfZGVmYXVsdGVycyI7czo3OiJib29sZWFuIjt9czoxNzoiACoAY2xhc3NDYXN0Q2FjaGUiO2E6MDp7fXM6MjE6IgAqAGF0dHJpYnV0ZUNhc3RDYWNoZSI7YTowOnt9czoxMzoiACoAZGF0ZUZvcm1hdCI7TjtzOjEwOiIAKgBhcHBlbmRzIjthOjA6e31zOjE5OiIAKgBkaXNwYXRjaGVzRXZlbnRzIjthOjA6e31zOjE0OiIAKgBvYnNlcnZhYmxlcyI7YTowOnt9czoxMjoiACoAcmVsYXRpb25zIjthOjA6e31zOjEwOiIAKgB0b3VjaGVzIjthOjA6e31zOjEwOiJ0aW1lc3RhbXBzIjtiOjE7czoxMzoidXNlc1VuaXF1ZUlkcyI7YjowO3M6OToiACoAaGlkZGVuIjthOjA6e31zOjEwOiIAKgB2aXNpYmxlIjthOjA6e31zOjExOiIAKgBmaWxsYWJsZSI7YTo4OntpOjA7czoxMToic2Nob29sX25hbWUiO2k6MTtzOjk6ImxvZ29fcGF0aCI7aToyO3M6NzoiYWRkcmVzcyI7aTozO3M6NToicGhvbmUiO2k6NDtzOjU6ImVtYWlsIjtpOjU7czoyODoiYmxvY2tfcmVzdWx0c19mb3JfZGVmYXVsdGVycyI7aTo2O3M6MzE6ImJsb2NrX2FkbWl0X2NhcmRfZm9yX2RlZmF1bHRlcnMiO2k6NztzOjI4OiJibG9ja19pZF9jYXJkX2Zvcl9kZWZhdWx0ZXJzIjt9czoxMDoiACoAZ3VhcmRlZCI7YToxOntpOjA7czoxOiIqIjt9fQ==', 1774556312);

DROP TABLE IF EXISTS `cache_locks`;
CREATE TABLE `cache_locks` (
  `key` TEXT NOT NULL,
  `owner` TEXT NOT NULL,
  `expiration` BIGINT NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `class_promotion_mappings`;
CREATE TABLE `class_promotion_mappings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `from_class_id` BIGINT NOT NULL,
  `to_class_id` BIGINT NOT NULL,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  KEY `class_promotion_mappings_from_class_index` (`from_class_id`),
  UNIQUE KEY `class_promotion_mappings_unique` (`from_class_id`, `to_class_id`),
  CONSTRAINT `fk_class_promotion_mappings_to_class_id_school_classes_id` FOREIGN KEY (`to_class_id`) REFERENCES `school_classes` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
  CONSTRAINT `fk_class_promotion_mappings_from_class_id_school_classes_id` FOREIGN KEY (`from_class_id`) REFERENCES `school_classes` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `class_promotion_mappings` (`id`, `from_class_id`, `to_class_id`, `created_at`, `updated_at`) VALUES (1, 54, 18, '2026-03-26 18:14:55', '2026-03-26 18:14:55');
INSERT INTO `class_promotion_mappings` (`id`, `from_class_id`, `to_class_id`, `created_at`, `updated_at`) VALUES (2, 18, 17, '2026-03-26 18:14:56', '2026-03-26 18:14:56');
INSERT INTO `class_promotion_mappings` (`id`, `from_class_id`, `to_class_id`, `created_at`, `updated_at`) VALUES (3, 17, 37, '2026-03-26 18:14:56', '2026-03-26 18:14:56');
INSERT INTO `class_promotion_mappings` (`id`, `from_class_id`, `to_class_id`, `created_at`, `updated_at`) VALUES (4, 37, 41, '2026-03-26 18:14:56', '2026-03-26 18:14:56');
INSERT INTO `class_promotion_mappings` (`id`, `from_class_id`, `to_class_id`, `created_at`, `updated_at`) VALUES (5, 38, 39, '2026-03-26 18:14:57', '2026-03-26 18:14:57');
INSERT INTO `class_promotion_mappings` (`id`, `from_class_id`, `to_class_id`, `created_at`, `updated_at`) VALUES (6, 39, 40, '2026-03-26 18:14:57', '2026-03-26 18:14:57');

DROP TABLE IF EXISTS `class_sections`;
CREATE TABLE `class_sections` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `class_id` BIGINT NOT NULL,
  `section_name` TEXT NOT NULL,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  KEY `class_sections_section_name_index` (`section_name`),
  UNIQUE KEY `class_sections_class_id_section_name_unique` (`class_id`, `section_name`),
  CONSTRAINT `fk_class_sections_class_id_school_classes_id` FOREIGN KEY (`class_id`) REFERENCES `school_classes` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `class_sections` (`id`, `class_id`, `section_name`, `created_at`, `updated_at`) VALUES (1, 54, 'A', '2026-03-25 21:31:41', '2026-03-26 14:50:14');
INSERT INTO `class_sections` (`id`, `class_id`, `section_name`, `created_at`, `updated_at`) VALUES (2, 37, 'ENTIRE CLA', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `class_sections` (`id`, `class_id`, `section_name`, `created_at`, `updated_at`) VALUES (3, 38, 'BIO', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `class_sections` (`id`, `class_id`, `section_name`, `created_at`, `updated_at`) VALUES (4, 38, 'ENTIRE CLA', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `class_sections` (`id`, `class_id`, `section_name`, `created_at`, `updated_at`) VALUES (5, 38, 'COMP', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `class_sections` (`id`, `class_id`, `section_name`, `created_at`, `updated_at`) VALUES (6, 39, 'BIO', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `class_sections` (`id`, `class_id`, `section_name`, `created_at`, `updated_at`) VALUES (7, 39, 'CHEM', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `class_sections` (`id`, `class_id`, `section_name`, `created_at`, `updated_at`) VALUES (8, 39, 'ENTIRE CLA', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `class_sections` (`id`, `class_id`, `section_name`, `created_at`, `updated_at`) VALUES (9, 39, 'COM', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `class_sections` (`id`, `class_id`, `section_name`, `created_at`, `updated_at`) VALUES (10, 39, 'MATH', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `class_sections` (`id`, `class_id`, `section_name`, `created_at`, `updated_at`) VALUES (11, 40, 'BIO', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `class_sections` (`id`, `class_id`, `section_name`, `created_at`, `updated_at`) VALUES (12, 40, 'CHEM', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `class_sections` (`id`, `class_id`, `section_name`, `created_at`, `updated_at`) VALUES (13, 40, 'ENTIRE CLA', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `class_sections` (`id`, `class_id`, `section_name`, `created_at`, `updated_at`) VALUES (14, 40, 'COMP', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `class_sections` (`id`, `class_id`, `section_name`, `created_at`, `updated_at`) VALUES (15, 40, 'MATH', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `class_sections` (`id`, `class_id`, `section_name`, `created_at`, `updated_at`) VALUES (16, 41, 'ENTIRE CLA', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `class_sections` (`id`, `class_id`, `section_name`, `created_at`, `updated_at`) VALUES (17, 42, 'ENTIRE CLA', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `class_sections` (`id`, `class_id`, `section_name`, `created_at`, `updated_at`) VALUES (18, 43, 'ENTIRE CLA', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `class_sections` (`id`, `class_id`, `section_name`, `created_at`, `updated_at`) VALUES (19, 44, 'ENTIRE CLA', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `class_sections` (`id`, `class_id`, `section_name`, `created_at`, `updated_at`) VALUES (20, 45, 'ENTIRE CLA', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `class_sections` (`id`, `class_id`, `section_name`, `created_at`, `updated_at`) VALUES (21, 46, 'ENTIRE CLA', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `class_sections` (`id`, `class_id`, `section_name`, `created_at`, `updated_at`) VALUES (22, 47, 'ENTIRE CLA', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `class_sections` (`id`, `class_id`, `section_name`, `created_at`, `updated_at`) VALUES (23, 48, 'ENTIRE CLA', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `class_sections` (`id`, `class_id`, `section_name`, `created_at`, `updated_at`) VALUES (24, 49, 'ENTIRE CLA', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `class_sections` (`id`, `class_id`, `section_name`, `created_at`, `updated_at`) VALUES (25, 50, 'ENTIRE CLA', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `class_sections` (`id`, `class_id`, `section_name`, `created_at`, `updated_at`) VALUES (26, 51, 'ENTIRE CLA', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `class_sections` (`id`, `class_id`, `section_name`, `created_at`, `updated_at`) VALUES (27, 52, 'BIO', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `class_sections` (`id`, `class_id`, `section_name`, `created_at`, `updated_at`) VALUES (28, 52, 'ENTIRE CLA', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `class_sections` (`id`, `class_id`, `section_name`, `created_at`, `updated_at`) VALUES (29, 52, 'COMP', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `class_sections` (`id`, `class_id`, `section_name`, `created_at`, `updated_at`) VALUES (30, 53, 'BIO', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `class_sections` (`id`, `class_id`, `section_name`, `created_at`, `updated_at`) VALUES (31, 53, 'ENTIRE CLA', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `class_sections` (`id`, `class_id`, `section_name`, `created_at`, `updated_at`) VALUES (32, 53, 'COMP', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `class_sections` (`id`, `class_id`, `section_name`, `created_at`, `updated_at`) VALUES (33, 17, 'ENTIRE CLA', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `class_sections` (`id`, `class_id`, `section_name`, `created_at`, `updated_at`) VALUES (34, 54, 'ENTIRE CLA', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `class_sections` (`id`, `class_id`, `section_name`, `created_at`, `updated_at`) VALUES (35, 18, 'ENTIRE CLA', '2026-03-26 12:19:24', '2026-03-26 12:19:24');

DROP TABLE IF EXISTS `class_subject`;
CREATE TABLE `class_subject` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `class_id` BIGINT NOT NULL,
  `subject_id` BIGINT NOT NULL,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  UNIQUE KEY `class_subject_class_id_subject_id_unique` (`class_id`, `subject_id`),
  CONSTRAINT `fk_class_subject_subject_id_subjects_id` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
  CONSTRAINT `fk_class_subject_class_id_school_classes_id` FOREIGN KEY (`class_id`) REFERENCES `school_classes` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (11, 37, 18, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (12, 37, 21, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (13, 37, 15, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (14, 37, 1, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (16, 37, 19, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (17, 37, 25, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (18, 37, 14, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (19, 37, 2, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (21, 37, 26, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (22, 37, 28, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (23, 37, 3, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (25, 37, 4, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (26, 38, 8, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (27, 38, 6, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (29, 38, 21, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (30, 38, 15, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (31, 38, 1, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (32, 38, 25, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (33, 38, 2, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (35, 38, 27, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (36, 38, 7, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (38, 38, 3, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (39, 39, 8, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (41, 39, 6, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (43, 39, 21, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (44, 39, 15, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (46, 39, 1, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (48, 39, 25, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (49, 39, 14, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (50, 39, 2, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (52, 39, 26, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (53, 39, 7, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (55, 39, 29, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (56, 39, 3, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (58, 40, 8, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (60, 40, 6, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (62, 40, 21, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (63, 40, 15, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (65, 40, 1, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (67, 40, 14, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (68, 40, 2, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (70, 40, 26, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (71, 40, 27, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (72, 40, 7, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (74, 40, 3, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (76, 41, 18, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (77, 41, 21, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (78, 41, 15, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (79, 41, 1, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (81, 41, 19, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (82, 41, 25, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (83, 41, 14, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (84, 41, 2, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (86, 41, 26, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (87, 41, 28, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (88, 41, 3, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (90, 41, 4, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (91, 42, 20, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (92, 42, 21, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (93, 42, 15, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (94, 42, 1, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (96, 42, 19, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (97, 42, 25, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (98, 42, 14, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (99, 42, 2, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (101, 42, 26, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (102, 42, 28, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (103, 42, 3, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (105, 42, 4, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (106, 43, 20, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (107, 43, 21, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (108, 43, 15, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (109, 43, 1, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (111, 43, 19, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (112, 43, 25, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (113, 43, 14, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (114, 43, 2, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (116, 43, 26, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (117, 43, 28, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (118, 43, 3, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (120, 43, 4, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (121, 44, 20, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (122, 44, 21, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (123, 44, 15, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (124, 44, 1, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (126, 44, 19, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (127, 44, 25, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (128, 44, 14, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (129, 44, 2, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (131, 44, 26, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (132, 44, 28, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (133, 44, 3, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (135, 44, 4, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (136, 45, 20, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (137, 45, 21, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (138, 45, 15, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (139, 45, 1, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (141, 45, 19, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (142, 45, 25, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (143, 45, 14, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (144, 45, 2, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (146, 45, 26, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (147, 45, 28, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (148, 45, 3, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (150, 45, 4, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (151, 46, 20, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (152, 46, 21, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (153, 46, 15, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (154, 46, 1, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (156, 46, 19, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (157, 46, 25, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (158, 46, 14, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (159, 46, 2, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (161, 46, 26, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (162, 46, 28, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (163, 46, 3, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (165, 46, 4, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (166, 47, 20, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (167, 47, 21, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (168, 47, 15, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (169, 47, 1, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (171, 47, 22, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (172, 47, 24, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (173, 47, 25, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (174, 47, 2, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (176, 47, 26, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (177, 47, 3, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (178, 47, 4, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (179, 48, 20, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (180, 48, 21, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (181, 48, 15, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (182, 48, 1, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (184, 48, 22, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (185, 48, 24, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (186, 48, 25, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (187, 48, 2, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (189, 48, 26, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (190, 48, 3, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (191, 48, 4, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (192, 49, 21, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (193, 49, 15, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (194, 49, 1, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (196, 49, 22, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (197, 49, 24, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (198, 49, 25, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (199, 49, 2, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (201, 49, 26, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (202, 49, 3, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (204, 49, 4, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (205, 50, 21, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (206, 50, 15, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (207, 50, 1, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (209, 50, 22, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (210, 50, 24, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (211, 50, 25, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (212, 50, 2, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (214, 50, 26, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (215, 50, 3, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (217, 50, 4, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (218, 51, 21, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (219, 51, 15, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (220, 51, 1, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (222, 51, 24, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (223, 51, 25, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (224, 51, 14, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (225, 51, 2, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (227, 51, 26, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (228, 51, 3, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (230, 51, 4, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (232, 52, 8, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (233, 52, 6, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (235, 52, 21, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (236, 52, 15, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (237, 52, 1, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (238, 52, 25, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (240, 52, 14, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (241, 52, 2, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (243, 52, 26, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (244, 52, 7, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (246, 52, 3, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (247, 53, 8, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (248, 53, 6, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (250, 53, 21, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (251, 53, 15, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (252, 53, 1, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (253, 53, 25, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (255, 53, 14, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (256, 53, 2, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (258, 53, 26, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (259, 53, 7, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (261, 53, 3, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (262, 17, 18, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (263, 17, 21, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (264, 17, 1, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (266, 17, 19, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (267, 17, 25, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (268, 17, 2, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (270, 17, 26, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (271, 17, 28, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (272, 17, 3, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (274, 54, 18, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (275, 54, 21, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (276, 54, 1, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (278, 54, 19, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (279, 54, 25, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (280, 54, 2, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (282, 54, 26, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (283, 54, 28, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (284, 54, 3, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (286, 18, 18, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (287, 18, 21, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (288, 18, 1, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (290, 18, 19, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (291, 18, 25, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (292, 18, 2, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (294, 18, 26, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (295, 18, 28, NULL, NULL);
INSERT INTO `class_subject` (`id`, `class_id`, `subject_id`, `created_at`, `updated_at`) VALUES (296, 18, 3, NULL, NULL);

DROP TABLE IF EXISTS `discipline_complaints`;
CREATE TABLE `discipline_complaints` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` BIGINT NOT NULL,
  `complaint_date` DATE,
  `description` TEXT NOT NULL,
  `action_taken` TEXT,
  `status` TEXT NOT NULL DEFAULT 'open',
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  KEY `discipline_complaints_student_id_complaint_date_index` (`student_id`, `complaint_date`),
  CONSTRAINT `fk_discipline_complaints_student_id_students_id` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `exam_attendances`;
CREATE TABLE `exam_attendances` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `exam_session_id` BIGINT NOT NULL,
  `room_id` BIGINT NOT NULL,
  `student_id` BIGINT NOT NULL,
  `seat_assignment_id` BIGINT NOT NULL,
  `status` TEXT NOT NULL DEFAULT 'present',
  `remarks` TEXT,
  `marked_by` BIGINT,
  `marked_at` DATETIME,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  KEY `exam_attendances_seat_assignment_index` (`seat_assignment_id`),
  KEY `exam_attendances_session_room_status_index` (`exam_session_id`, `room_id`, `status`),
  UNIQUE KEY `exam_attendances_unique` (`exam_session_id`, `room_id`, `student_id`),
  CONSTRAINT `fk_exam_attendances_marked_by_users_id` FOREIGN KEY (`marked_by`) REFERENCES `users` (`id`) ON UPDATE NO ACTION ON DELETE SET NULL,
  CONSTRAINT `fk_exam_attendances_seat_assignment_id_exam_seat_assignments_id` FOREIGN KEY (`seat_assignment_id`) REFERENCES `exam_seat_assignments` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
  CONSTRAINT `fk_exam_attendances_student_id_students_id` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
  CONSTRAINT `fk_exam_attendances_room_id_exam_rooms_id` FOREIGN KEY (`room_id`) REFERENCES `exam_rooms` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
  CONSTRAINT `fk_exam_attendances_exam_session_id_exam_sessions_id` FOREIGN KEY (`exam_session_id`) REFERENCES `exam_sessions` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `exam_room_invigilators`;
CREATE TABLE `exam_room_invigilators` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `exam_session_id` BIGINT NOT NULL,
  `room_id` BIGINT NOT NULL,
  `teacher_id` BIGINT NOT NULL,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  KEY `exam_room_invigilators_session_room_index` (`exam_session_id`, `room_id`),
  UNIQUE KEY `exam_room_invigilators_unique` (`exam_session_id`, `room_id`, `teacher_id`),
  CONSTRAINT `fk_exam_room_invigilators_teacher_id_teachers_id` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
  CONSTRAINT `fk_exam_room_invigilators_room_id_exam_rooms_id` FOREIGN KEY (`room_id`) REFERENCES `exam_rooms` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
  CONSTRAINT `fk_exam_room_invigilators_exam_session_id_exam_sessions_id` FOREIGN KEY (`exam_session_id`) REFERENCES `exam_sessions` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `exam_room_invigilators` (`id`, `exam_session_id`, `room_id`, `teacher_id`, `created_at`, `updated_at`) VALUES (1, 1, 1, 36, '2026-03-26 15:27:16', '2026-03-26 15:27:16');
INSERT INTO `exam_room_invigilators` (`id`, `exam_session_id`, `room_id`, `teacher_id`, `created_at`, `updated_at`) VALUES (2, 1, 1, 32, '2026-03-26 15:27:40', '2026-03-26 15:27:40');
INSERT INTO `exam_room_invigilators` (`id`, `exam_session_id`, `room_id`, `teacher_id`, `created_at`, `updated_at`) VALUES (3, 1, 1, 7, '2026-03-26 15:28:02', '2026-03-26 15:28:02');

DROP TABLE IF EXISTS `exam_rooms`;
CREATE TABLE `exam_rooms` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` TEXT NOT NULL,
  `capacity` BIGINT NOT NULL,
  `is_active` BIGINT NOT NULL DEFAULT '1',
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  UNIQUE KEY `exam_rooms_name_unique` (`name`),
  KEY `exam_rooms_active_name_index` (`is_active`, `name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `exam_rooms` (`id`, `name`, `capacity`, `is_active`, `created_at`, `updated_at`) VALUES (1, 'hall', 100, 1, '2026-03-26 15:21:44', '2026-03-26 15:21:44');

DROP TABLE IF EXISTS `exam_seat_assignments`;
CREATE TABLE `exam_seat_assignments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `exam_seating_plan_id` BIGINT NOT NULL,
  `student_id` BIGINT NOT NULL,
  `class_id` BIGINT NOT NULL,
  `exam_room_id` BIGINT NOT NULL,
  `seat_number` BIGINT NOT NULL,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  KEY `seat_assignments_room_seat_index` (`exam_room_id`, `seat_number`),
  KEY `seat_assignments_plan_class_index` (`exam_seating_plan_id`, `class_id`),
  UNIQUE KEY `seat_assignments_plan_room_seat_unique` (`exam_seating_plan_id`, `exam_room_id`, `seat_number`),
  UNIQUE KEY `seat_assignments_plan_student_unique` (`exam_seating_plan_id`, `student_id`),
  CONSTRAINT `fk_exam_seat_assignments_exam_room_id_exam_rooms_id` FOREIGN KEY (`exam_room_id`) REFERENCES `exam_rooms` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
  CONSTRAINT `fk_exam_seat_assignments_class_id_school_classes_id` FOREIGN KEY (`class_id`) REFERENCES `school_classes` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
  CONSTRAINT `fk_exam_seat_assignments_student_id_students_id` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
  CONSTRAINT `fk_exam_seat_assignments_exam_seating_plan_id_exam_seating_plans_id` FOREIGN KEY (`exam_seating_plan_id`) REFERENCES `exam_seating_plans` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `exam_seat_assignments` (`id`, `exam_seating_plan_id`, `student_id`, `class_id`, `exam_room_id`, `seat_number`, `created_at`, `updated_at`) VALUES (1, 1, 579, 39, 1, 1, '2026-03-26 15:26:14', '2026-03-26 15:26:14');
INSERT INTO `exam_seat_assignments` (`id`, `exam_seating_plan_id`, `student_id`, `class_id`, `exam_room_id`, `seat_number`, `created_at`, `updated_at`) VALUES (2, 1, 595, 40, 1, 2, '2026-03-26 15:26:14', '2026-03-26 15:26:14');
INSERT INTO `exam_seat_assignments` (`id`, `exam_seating_plan_id`, `student_id`, `class_id`, `exam_room_id`, `seat_number`, `created_at`, `updated_at`) VALUES (3, 1, 605, 40, 1, 3, '2026-03-26 15:26:14', '2026-03-26 15:26:14');
INSERT INTO `exam_seat_assignments` (`id`, `exam_seating_plan_id`, `student_id`, `class_id`, `exam_room_id`, `seat_number`, `created_at`, `updated_at`) VALUES (4, 1, 584, 39, 1, 4, '2026-03-26 15:26:14', '2026-03-26 15:26:14');
INSERT INTO `exam_seat_assignments` (`id`, `exam_seating_plan_id`, `student_id`, `class_id`, `exam_room_id`, `seat_number`, `created_at`, `updated_at`) VALUES (5, 1, 582, 39, 1, 5, '2026-03-26 15:26:14', '2026-03-26 15:26:14');
INSERT INTO `exam_seat_assignments` (`id`, `exam_seating_plan_id`, `student_id`, `class_id`, `exam_room_id`, `seat_number`, `created_at`, `updated_at`) VALUES (6, 1, 604, 40, 1, 6, '2026-03-26 15:26:14', '2026-03-26 15:26:14');
INSERT INTO `exam_seat_assignments` (`id`, `exam_seating_plan_id`, `student_id`, `class_id`, `exam_room_id`, `seat_number`, `created_at`, `updated_at`) VALUES (7, 1, 592, 40, 1, 7, '2026-03-26 15:26:14', '2026-03-26 15:26:14');
INSERT INTO `exam_seat_assignments` (`id`, `exam_seating_plan_id`, `student_id`, `class_id`, `exam_room_id`, `seat_number`, `created_at`, `updated_at`) VALUES (8, 1, 583, 39, 1, 8, '2026-03-26 15:26:14', '2026-03-26 15:26:14');
INSERT INTO `exam_seat_assignments` (`id`, `exam_seating_plan_id`, `student_id`, `class_id`, `exam_room_id`, `seat_number`, `created_at`, `updated_at`) VALUES (9, 1, 603, 40, 1, 9, '2026-03-26 15:26:14', '2026-03-26 15:26:14');
INSERT INTO `exam_seat_assignments` (`id`, `exam_seating_plan_id`, `student_id`, `class_id`, `exam_room_id`, `seat_number`, `created_at`, `updated_at`) VALUES (10, 1, 576, 39, 1, 10, '2026-03-26 15:26:14', '2026-03-26 15:26:14');
INSERT INTO `exam_seat_assignments` (`id`, `exam_seating_plan_id`, `student_id`, `class_id`, `exam_room_id`, `seat_number`, `created_at`, `updated_at`) VALUES (11, 1, 573, 39, 1, 11, '2026-03-26 15:26:14', '2026-03-26 15:26:14');
INSERT INTO `exam_seat_assignments` (`id`, `exam_seating_plan_id`, `student_id`, `class_id`, `exam_room_id`, `seat_number`, `created_at`, `updated_at`) VALUES (12, 1, 587, 40, 1, 12, '2026-03-26 15:26:14', '2026-03-26 15:26:14');
INSERT INTO `exam_seat_assignments` (`id`, `exam_seating_plan_id`, `student_id`, `class_id`, `exam_room_id`, `seat_number`, `created_at`, `updated_at`) VALUES (13, 1, 588, 40, 1, 13, '2026-03-26 15:26:14', '2026-03-26 15:26:14');
INSERT INTO `exam_seat_assignments` (`id`, `exam_seating_plan_id`, `student_id`, `class_id`, `exam_room_id`, `seat_number`, `created_at`, `updated_at`) VALUES (14, 1, 575, 39, 1, 14, '2026-03-26 15:26:14', '2026-03-26 15:26:14');
INSERT INTO `exam_seat_assignments` (`id`, `exam_seating_plan_id`, `student_id`, `class_id`, `exam_room_id`, `seat_number`, `created_at`, `updated_at`) VALUES (15, 1, 580, 39, 1, 15, '2026-03-26 15:26:14', '2026-03-26 15:26:14');
INSERT INTO `exam_seat_assignments` (`id`, `exam_seating_plan_id`, `student_id`, `class_id`, `exam_room_id`, `seat_number`, `created_at`, `updated_at`) VALUES (16, 1, 585, 40, 1, 16, '2026-03-26 15:26:14', '2026-03-26 15:26:14');
INSERT INTO `exam_seat_assignments` (`id`, `exam_seating_plan_id`, `student_id`, `class_id`, `exam_room_id`, `seat_number`, `created_at`, `updated_at`) VALUES (17, 1, 578, 39, 1, 17, '2026-03-26 15:26:14', '2026-03-26 15:26:14');
INSERT INTO `exam_seat_assignments` (`id`, `exam_seating_plan_id`, `student_id`, `class_id`, `exam_room_id`, `seat_number`, `created_at`, `updated_at`) VALUES (18, 1, 589, 40, 1, 18, '2026-03-26 15:26:14', '2026-03-26 15:26:14');
INSERT INTO `exam_seat_assignments` (`id`, `exam_seating_plan_id`, `student_id`, `class_id`, `exam_room_id`, `seat_number`, `created_at`, `updated_at`) VALUES (19, 1, 594, 40, 1, 19, '2026-03-26 15:26:14', '2026-03-26 15:26:14');
INSERT INTO `exam_seat_assignments` (`id`, `exam_seating_plan_id`, `student_id`, `class_id`, `exam_room_id`, `seat_number`, `created_at`, `updated_at`) VALUES (20, 1, 572, 39, 1, 20, '2026-03-26 15:26:14', '2026-03-26 15:26:14');
INSERT INTO `exam_seat_assignments` (`id`, `exam_seating_plan_id`, `student_id`, `class_id`, `exam_room_id`, `seat_number`, `created_at`, `updated_at`) VALUES (21, 1, 574, 39, 1, 21, '2026-03-26 15:26:14', '2026-03-26 15:26:14');
INSERT INTO `exam_seat_assignments` (`id`, `exam_seating_plan_id`, `student_id`, `class_id`, `exam_room_id`, `seat_number`, `created_at`, `updated_at`) VALUES (22, 1, 591, 40, 1, 22, '2026-03-26 15:26:14', '2026-03-26 15:26:14');
INSERT INTO `exam_seat_assignments` (`id`, `exam_seating_plan_id`, `student_id`, `class_id`, `exam_room_id`, `seat_number`, `created_at`, `updated_at`) VALUES (23, 1, 602, 40, 1, 23, '2026-03-26 15:26:14', '2026-03-26 15:26:14');
INSERT INTO `exam_seat_assignments` (`id`, `exam_seating_plan_id`, `student_id`, `class_id`, `exam_room_id`, `seat_number`, `created_at`, `updated_at`) VALUES (24, 1, 577, 39, 1, 24, '2026-03-26 15:26:14', '2026-03-26 15:26:14');
INSERT INTO `exam_seat_assignments` (`id`, `exam_seating_plan_id`, `student_id`, `class_id`, `exam_room_id`, `seat_number`, `created_at`, `updated_at`) VALUES (25, 1, 586, 40, 1, 25, '2026-03-26 15:26:14', '2026-03-26 15:26:14');
INSERT INTO `exam_seat_assignments` (`id`, `exam_seating_plan_id`, `student_id`, `class_id`, `exam_room_id`, `seat_number`, `created_at`, `updated_at`) VALUES (26, 1, 581, 39, 1, 26, '2026-03-26 15:26:14', '2026-03-26 15:26:14');
INSERT INTO `exam_seat_assignments` (`id`, `exam_seating_plan_id`, `student_id`, `class_id`, `exam_room_id`, `seat_number`, `created_at`, `updated_at`) VALUES (27, 1, 600, 40, 1, 27, '2026-03-26 15:26:14', '2026-03-26 15:26:14');
INSERT INTO `exam_seat_assignments` (`id`, `exam_seating_plan_id`, `student_id`, `class_id`, `exam_room_id`, `seat_number`, `created_at`, `updated_at`) VALUES (28, 1, 606, 40, 1, 28, '2026-03-26 15:26:14', '2026-03-26 15:26:14');
INSERT INTO `exam_seat_assignments` (`id`, `exam_seating_plan_id`, `student_id`, `class_id`, `exam_room_id`, `seat_number`, `created_at`, `updated_at`) VALUES (29, 1, 590, 40, 1, 29, '2026-03-26 15:26:14', '2026-03-26 15:26:14');
INSERT INTO `exam_seat_assignments` (`id`, `exam_seating_plan_id`, `student_id`, `class_id`, `exam_room_id`, `seat_number`, `created_at`, `updated_at`) VALUES (30, 1, 599, 40, 1, 30, '2026-03-26 15:26:14', '2026-03-26 15:26:14');
INSERT INTO `exam_seat_assignments` (`id`, `exam_seating_plan_id`, `student_id`, `class_id`, `exam_room_id`, `seat_number`, `created_at`, `updated_at`) VALUES (31, 1, 596, 40, 1, 31, '2026-03-26 15:26:14', '2026-03-26 15:26:14');
INSERT INTO `exam_seat_assignments` (`id`, `exam_seating_plan_id`, `student_id`, `class_id`, `exam_room_id`, `seat_number`, `created_at`, `updated_at`) VALUES (32, 1, 597, 40, 1, 32, '2026-03-26 15:26:14', '2026-03-26 15:26:14');
INSERT INTO `exam_seat_assignments` (`id`, `exam_seating_plan_id`, `student_id`, `class_id`, `exam_room_id`, `seat_number`, `created_at`, `updated_at`) VALUES (33, 1, 598, 40, 1, 33, '2026-03-26 15:26:14', '2026-03-26 15:26:14');
INSERT INTO `exam_seat_assignments` (`id`, `exam_seating_plan_id`, `student_id`, `class_id`, `exam_room_id`, `seat_number`, `created_at`, `updated_at`) VALUES (34, 1, 593, 40, 1, 34, '2026-03-26 15:26:14', '2026-03-26 15:26:14');
INSERT INTO `exam_seat_assignments` (`id`, `exam_seating_plan_id`, `student_id`, `class_id`, `exam_room_id`, `seat_number`, `created_at`, `updated_at`) VALUES (35, 1, 601, 40, 1, 35, '2026-03-26 15:26:14', '2026-03-26 15:26:14');

DROP TABLE IF EXISTS `exam_seating_plans`;
CREATE TABLE `exam_seating_plans` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `exam_session_id` BIGINT NOT NULL,
  `class_ids` TEXT NOT NULL,
  `is_randomized` BIGINT NOT NULL DEFAULT '0',
  `total_students` BIGINT NOT NULL DEFAULT '0',
  `total_rooms` BIGINT NOT NULL DEFAULT '0',
  `generated_by` BIGINT,
  `generated_at` DATETIME,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  KEY `seating_plans_session_generated_at_index` (`exam_session_id`, `generated_at`),
  CONSTRAINT `fk_exam_seating_plans_generated_by_users_id` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON UPDATE NO ACTION ON DELETE SET NULL,
  CONSTRAINT `fk_exam_seating_plans_exam_session_id_exam_sessions_id` FOREIGN KEY (`exam_session_id`) REFERENCES `exam_sessions` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `exam_seating_plans` (`id`, `exam_session_id`, `class_ids`, `is_randomized`, `total_students`, `total_rooms`, `generated_by`, `generated_at`, `created_at`, `updated_at`) VALUES (1, 1, '[39,40]', 1, 35, 1, 2, '2026-03-26 15:26:14', '2026-03-26 15:26:14', '2026-03-26 15:26:14');

DROP TABLE IF EXISTS `exam_sessions`;
CREATE TABLE `exam_sessions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` TEXT NOT NULL,
  `session` TEXT NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  KEY `exam_sessions_session_start_index` (`session`, `start_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `exam_sessions` (`id`, `name`, `session`, `start_date`, `end_date`, `created_at`, `updated_at`) VALUES (1, 'Mock Exams', '2025-2026', '2026-04-10 00:00:00', '2026-04-20 00:00:00', '2026-03-26 15:25:04', '2026-03-26 15:25:04');

DROP TABLE IF EXISTS `exams`;
CREATE TABLE `exams` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `class_id` BIGINT NOT NULL,
  `subject_id` BIGINT NOT NULL,
  `exam_type` TEXT NOT NULL,
  `session` TEXT NOT NULL,
  `total_marks` BIGINT NOT NULL,
  `teacher_id` BIGINT NOT NULL,
  `locked_at` DATETIME,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  KEY `exams_session_exam_type_class_index` (`session`, `exam_type`, `class_id`),
  KEY `exams_class_id_session_index` (`class_id`, `session`),
  KEY `exams_teacher_id_session_index` (`teacher_id`, `session`),
  UNIQUE KEY `exams_class_id_subject_id_exam_type_session_unique` (`class_id`, `subject_id`, `exam_type`, `session`),
  CONSTRAINT `fk_exams_teacher_id_teachers_id` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
  CONSTRAINT `fk_exams_subject_id_subjects_id` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
  CONSTRAINT `fk_exams_class_id_school_classes_id` FOREIGN KEY (`class_id`) REFERENCES `school_classes` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE `failed_jobs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` TEXT NOT NULL,
  `connection` TEXT NOT NULL,
  `queue` TEXT NOT NULL,
  `payload` TEXT NOT NULL,
  `exception` TEXT NOT NULL,
  `failed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `fee_block_overrides`;
CREATE TABLE `fee_block_overrides` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` BIGINT NOT NULL,
  `session` TEXT NOT NULL,
  `block_type` TEXT NOT NULL,
  `is_allowed` BIGINT NOT NULL DEFAULT '1',
  `reason` TEXT,
  `approved_by` BIGINT,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  KEY `fee_block_overrides_type_allowed_index` (`block_type`, `is_allowed`),
  UNIQUE KEY `fee_block_overrides_student_session_type_unique` (`student_id`, `session`, `block_type`),
  CONSTRAINT `fk_fee_block_overrides_approved_by_users_id` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON UPDATE NO ACTION ON DELETE SET NULL,
  CONSTRAINT `fk_fee_block_overrides_student_id_students_id` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `fee_challan_items`;
CREATE TABLE `fee_challan_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `fee_challan_id` BIGINT NOT NULL,
  `fee_structure_id` BIGINT,
  `title` TEXT NOT NULL,
  `fee_type` TEXT NOT NULL,
  `amount` DECIMAL(20,4) NOT NULL,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  `fee_installment_id` BIGINT,
  `student_arrear_id` BIGINT,
  `paid_amount` DECIMAL(20,4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `fee_challan_items_arrear_index` (`student_arrear_id`),
  KEY `fee_challan_items_installment_index` (`fee_installment_id`),
  KEY `fee_challan_items_fee_challan_id_index` (`fee_challan_id`),
  CONSTRAINT `fk_fee_challan_items_student_arrear_id_student_arrears_id` FOREIGN KEY (`student_arrear_id`) REFERENCES `student_arrears` (`id`) ON UPDATE NO ACTION ON DELETE SET NULL,
  CONSTRAINT `fk_fee_challan_items_fee_installment_id_fee_installments_id` FOREIGN KEY (`fee_installment_id`) REFERENCES `fee_installments` (`id`) ON UPDATE NO ACTION ON DELETE SET NULL,
  CONSTRAINT `fk_fee_challan_items_fee_challan_id_fee_challans_id` FOREIGN KEY (`fee_challan_id`) REFERENCES `fee_challans` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
  CONSTRAINT `fk_fee_challan_items_fee_structure_id_fee_structures_id` FOREIGN KEY (`fee_structure_id`) REFERENCES `fee_structures` (`id`) ON UPDATE NO ACTION ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `fee_challans`;
CREATE TABLE `fee_challans` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `challan_number` TEXT NOT NULL,
  `student_id` BIGINT NOT NULL,
  `class_id` BIGINT NOT NULL,
  `session` TEXT NOT NULL,
  `month` TEXT NOT NULL,
  `issue_date` DATE NOT NULL,
  `due_date` DATE NOT NULL,
  `total_amount` DECIMAL(20,4) NOT NULL,
  `status` TEXT NOT NULL DEFAULT 'unpaid',
  `generated_by` BIGINT,
  `paid_at` DATETIME,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  `arrears` DECIMAL(20,4) NOT NULL DEFAULT '0',
  `late_fee` DECIMAL(20,4) NOT NULL DEFAULT '0',
  `late_fee_waived_at` DATETIME,
  PRIMARY KEY (`id`),
  UNIQUE KEY `fee_challans_challan_number_unique` (`challan_number`),
  KEY `fee_challans_status_due_date_index` (`status`, `due_date`),
  KEY `fee_challans_class_session_month_index` (`class_id`, `session`, `month`),
  UNIQUE KEY `fee_challans_student_session_month_unique` (`student_id`, `session`, `month`),
  CONSTRAINT `fk_fee_challans_generated_by_users_id` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON UPDATE NO ACTION ON DELETE SET NULL,
  CONSTRAINT `fk_fee_challans_class_id_school_classes_id` FOREIGN KEY (`class_id`) REFERENCES `school_classes` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
  CONSTRAINT `fk_fee_challans_student_id_students_id` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `fee_defaulters`;
CREATE TABLE `fee_defaulters` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` BIGINT NOT NULL,
  `session` TEXT NOT NULL,
  `total_due` DECIMAL(20,4) NOT NULL DEFAULT '0',
  `oldest_due_date` DATE,
  `is_active` BIGINT NOT NULL DEFAULT '1',
  `marked_at` DATETIME,
  `cleared_at` DATETIME,
  `remarks` TEXT,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  KEY `fee_defaulters_oldest_due_date_index` (`oldest_due_date`),
  KEY `fee_defaulters_session_active_index` (`session`, `is_active`),
  UNIQUE KEY `fee_defaulters_student_session_unique` (`student_id`, `session`),
  CONSTRAINT `fk_fee_defaulters_student_id_students_id` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `fee_installment_plans`;
CREATE TABLE `fee_installment_plans` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` BIGINT NOT NULL,
  `session` TEXT NOT NULL,
  `plan_name` TEXT,
  `plan_type` TEXT NOT NULL,
  `total_amount` DECIMAL(20,4) NOT NULL,
  `number_of_installments` BIGINT NOT NULL,
  `first_due_date` DATE NOT NULL,
  `custom_interval_days` BIGINT,
  `is_active` BIGINT NOT NULL DEFAULT '1',
  `notes` TEXT,
  `created_by` BIGINT,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  KEY `installment_plans_type_active_index` (`plan_type`, `is_active`),
  KEY `installment_plans_student_session_active_index` (`student_id`, `session`, `is_active`),
  CONSTRAINT `fk_fee_installment_plans_created_by_users_id` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE NO ACTION ON DELETE SET NULL,
  CONSTRAINT `fk_fee_installment_plans_student_id_students_id` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `fee_installments`;
CREATE TABLE `fee_installments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `fee_installment_plan_id` BIGINT NOT NULL,
  `student_id` BIGINT NOT NULL,
  `installment_no` BIGINT NOT NULL,
  `title` TEXT,
  `due_date` DATE NOT NULL,
  `amount` DECIMAL(20,4) NOT NULL,
  `paid_amount` DECIMAL(20,4) NOT NULL DEFAULT '0',
  `status` TEXT NOT NULL DEFAULT 'pending',
  `paid_at` DATETIME,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  KEY `installments_plan_due_index` (`fee_installment_plan_id`, `due_date`),
  KEY `installments_student_status_due_index` (`student_id`, `status`, `due_date`),
  UNIQUE KEY `installments_plan_number_unique` (`fee_installment_plan_id`, `installment_no`),
  CONSTRAINT `fk_fee_installments_student_id_students_id` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
  CONSTRAINT `fk_fee_installments_fee_installment_plan_id_fee_installment_plans_id` FOREIGN KEY (`fee_installment_plan_id`) REFERENCES `fee_installment_plans` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `fee_payments`;
CREATE TABLE `fee_payments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `fee_challan_id` BIGINT NOT NULL,
  `amount_paid` DECIMAL(20,4) NOT NULL,
  `payment_date` DATE NOT NULL,
  `payment_method` TEXT,
  `reference_no` TEXT,
  `received_by` BIGINT,
  `notes` TEXT,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  KEY `fee_payments_challan_payment_date_index` (`fee_challan_id`, `payment_date`),
  CONSTRAINT `fk_fee_payments_received_by_users_id` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON UPDATE NO ACTION ON DELETE SET NULL,
  CONSTRAINT `fk_fee_payments_fee_challan_id_fee_challans_id` FOREIGN KEY (`fee_challan_id`) REFERENCES `fee_challans` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `fee_reminders`;
CREATE TABLE `fee_reminders` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` BIGINT NOT NULL,
  `challan_id` BIGINT,
  `session` TEXT NOT NULL,
  `channel` TEXT NOT NULL DEFAULT 'in_app',
  `title` TEXT NOT NULL,
  `message` TEXT NOT NULL,
  `sent_by` BIGINT,
  `sent_at` DATETIME,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  KEY `fee_reminders_sent_at_index` (`sent_at`),
  KEY `fee_reminders_student_session_index` (`student_id`, `session`),
  CONSTRAINT `fk_fee_reminders_sent_by_users_id` FOREIGN KEY (`sent_by`) REFERENCES `users` (`id`) ON UPDATE NO ACTION ON DELETE SET NULL,
  CONSTRAINT `fk_fee_reminders_challan_id_fee_challans_id` FOREIGN KEY (`challan_id`) REFERENCES `fee_challans` (`id`) ON UPDATE NO ACTION ON DELETE SET NULL,
  CONSTRAINT `fk_fee_reminders_student_id_students_id` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `fee_structures`;
CREATE TABLE `fee_structures` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `session` TEXT NOT NULL,
  `class_id` BIGINT NOT NULL,
  `title` TEXT NOT NULL,
  `amount` DECIMAL(20,4) NOT NULL,
  `fee_type` TEXT NOT NULL,
  `is_monthly` BIGINT NOT NULL DEFAULT '0',
  `is_active` BIGINT NOT NULL DEFAULT '1',
  `created_by` BIGINT,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  KEY `fee_structures_class_id_is_active_index` (`class_id`, `is_active`),
  KEY `fee_structures_session_class_id_index` (`session`, `class_id`),
  CONSTRAINT `fk_fee_structures_created_by_users_id` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE NO ACTION ON DELETE SET NULL,
  CONSTRAINT `fk_fee_structures_class_id_school_classes_id` FOREIGN KEY (`class_id`) REFERENCES `school_classes` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `job_batches`;
CREATE TABLE `job_batches` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` TEXT NOT NULL,
  `total_jobs` BIGINT NOT NULL,
  `pending_jobs` BIGINT NOT NULL,
  `failed_jobs` BIGINT NOT NULL,
  `failed_job_ids` TEXT NOT NULL,
  `options` TEXT,
  `cancelled_at` BIGINT,
  `created_at` BIGINT NOT NULL,
  `finished_at` BIGINT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `jobs`;
CREATE TABLE `jobs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `queue` TEXT NOT NULL,
  `payload` TEXT NOT NULL,
  `attempts` BIGINT NOT NULL,
  `reserved_at` BIGINT,
  `available_at` BIGINT NOT NULL,
  `created_at` BIGINT NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `mark_edit_logs`;
CREATE TABLE `mark_edit_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `mark_id` BIGINT NOT NULL,
  `old_marks` BIGINT,
  `new_marks` BIGINT,
  `edited_by` BIGINT NOT NULL,
  `edit_reason` TEXT NOT NULL,
  `action_type` TEXT NOT NULL,
  `edited_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `mark_edit_logs_edited_at_index` (`edited_at`),
  KEY `mark_edit_logs_mark_id_action_type_index` (`mark_id`, `action_type`),
  CONSTRAINT `fk_mark_edit_logs_edited_by_users_id` FOREIGN KEY (`edited_by`) REFERENCES `users` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `marks`;
CREATE TABLE `marks` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `exam_id` BIGINT NOT NULL,
  `student_id` BIGINT NOT NULL,
  `obtained_marks` BIGINT NOT NULL,
  `total_marks` BIGINT NOT NULL,
  `teacher_id` BIGINT NOT NULL,
  `session` TEXT NOT NULL,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  KEY `marks_session_exam_id_index` (`session`, `exam_id`),
  KEY `marks_teacher_id_session_index` (`teacher_id`, `session`),
  KEY `marks_student_id_session_index` (`student_id`, `session`),
  UNIQUE KEY `marks_exam_id_student_id_unique` (`exam_id`, `student_id`),
  CONSTRAINT `fk_marks_teacher_id_teachers_id` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
  CONSTRAINT `fk_marks_student_id_students_id` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
  CONSTRAINT `fk_marks_exam_id_exams_id` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `medical_histories`;
CREATE TABLE `medical_histories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` BIGINT NOT NULL,
  `visit_date` DATE,
  `details` TEXT NOT NULL,
  `treatment` TEXT,
  `status` TEXT NOT NULL DEFAULT 'pending',
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  KEY `medical_histories_student_id_visit_date_index` (`student_id`, `visit_date`),
  CONSTRAINT `fk_medical_histories_student_id_students_id` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `medical_referrals`;
CREATE TABLE `medical_referrals` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` BIGINT NOT NULL,
  `principal_id` BIGINT NOT NULL,
  `doctor_id` BIGINT NOT NULL,
  `illness_type` TEXT NOT NULL,
  `illness_other_text` TEXT,
  `diagnosis` TEXT,
  `prescription` TEXT,
  `notes` TEXT,
  `status` TEXT NOT NULL DEFAULT 'pending',
  `referred_at` DATETIME,
  `consulted_at` DATETIME,
  `completed_at` DATETIME,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  KEY `medical_referrals_doctor_created_index` (`doctor_id`, `created_at`),
  KEY `medical_referrals_status_created_at_index` (`status`, `created_at`),
  KEY `medical_referrals_doctor_id_status_index` (`doctor_id`, `status`),
  KEY `medical_referrals_student_id_created_at_index` (`student_id`, `created_at`),
  CONSTRAINT `fk_medical_referrals_doctor_id_users_id` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
  CONSTRAINT `fk_medical_referrals_principal_id_users_id` FOREIGN KEY (`principal_id`) REFERENCES `users` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
  CONSTRAINT `fk_medical_referrals_student_id_students_id` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `migration` TEXT NOT NULL,
  `batch` BIGINT NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1, '0001_01_01_000000_create_users_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2, '0001_01_01_000001_create_cache_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3, '0001_01_01_000002_create_jobs_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4, '2026_03_05_093953_create_permission_tables', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5, '2026_03_05_120000_add_status_to_users_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6, '2026_03_05_130000_create_student_management_tables', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7, '2026_03_05_140000_create_teacher_module_tables', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8, '2026_03_05_150000_add_is_default_to_subjects_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9, '2026_03_05_160000_create_class_subject_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10, '2026_03_05_170000_create_student_subjects_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11, '2026_03_05_180000_create_attendance_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12, '2026_03_05_190000_create_exams_and_marks_tables', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13, '2026_03_05_200000_create_school_settings_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14, '2026_03_05_210000_create_medical_referrals_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15, '2026_03_05_220000_create_notifications_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16, '2026_03_06_000100_add_performance_indexes', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17, '2026_03_06_010000_create_timetable_module_tables', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18, '2026_03_06_020000_create_ai_analytics_tables', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19, '2026_03_06_030000_create_push_subscriptions_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20, '2026_03_06_040000_add_class_teacher_and_teacher_subject_assignments_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21, '2026_03_11_010000_create_mark_edit_logs_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22, '2026_03_11_020000_create_fee_management_tables', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23, '2026_03_11_030000_create_payroll_management_tables', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24, '2026_03_11_040000_create_student_subject_assignments_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25, '2026_03_11_041000_create_subject_groups_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26, '2026_03_11_042000_create_subject_group_subject_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27, '2026_03_11_043000_add_subject_group_id_to_student_subject_assignments_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28, '2026_03_12_120000_create_student_learning_profiles_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29, '2026_03_12_121000_create_report_comments_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30, '2026_03_12_130000_add_arrears_late_fee_fields_to_fee_challans_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31, '2026_03_12_180000_add_force_password_change_fields_to_users_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32, '2026_03_17_210000_create_student_fee_structures_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33, '2026_03_18_090000_add_photo_path_to_students_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34, '2026_03_20_000000_create_academic_events_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35, '2026_03_20_000100_create_academic_notifications_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36, '2026_03_20_000200_add_qr_token_to_students_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37, '2026_03_24_000300_create_fee_installment_tables', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38, '2026_03_24_000400_add_installment_links_to_fee_challan_items_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39, '2026_03_24_000500_create_fee_defaulter_tables', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40, '2026_03_24_000600_add_fee_block_settings_to_school_settings_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41, '2026_03_24_000700_create_exam_sessions_and_admit_card_overrides_tables', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42, '2026_03_24_000800_create_exam_seating_plan_tables', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43, '2026_03_24_000900_create_exam_hall_attendance_tables', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44, '2026_03_26_010000_create_class_promotion_tables', 2);

DROP TABLE IF EXISTS `model_has_permissions`;
CREATE TABLE `model_has_permissions` (
  `permission_id` BIGINT NOT NULL,
  `model_type` TEXT NOT NULL,
  `model_id` BIGINT NOT NULL,
  PRIMARY KEY (`permission_id`, `model_type`, `model_id`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`, `model_type`),
  CONSTRAINT `fk_model_has_permissions_permission_id_permissions_id` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `model_has_roles`;
CREATE TABLE `model_has_roles` (
  `role_id` BIGINT NOT NULL,
  `model_type` TEXT NOT NULL,
  `model_id` BIGINT NOT NULL,
  PRIMARY KEY (`role_id`, `model_type`, `model_id`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`, `model_type`),
  CONSTRAINT `fk_model_has_roles_role_id_roles_id` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES (1, 'App\\Models\\User', 1);
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES (2, 'App\\Models\\User', 2);
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES (3, 'App\\Models\\User', 3);
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES (4, 'App\\Models\\User', 4);
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES (5, 'App\\Models\\User', 5);
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES (6, 'App\\Models\\User', 6);
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES (3, 'App\\Models\\User', 7);
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES (3, 'App\\Models\\User', 8);
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES (3, 'App\\Models\\User', 9);
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES (3, 'App\\Models\\User', 10);
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES (3, 'App\\Models\\User', 11);
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES (3, 'App\\Models\\User', 12);
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES (3, 'App\\Models\\User', 13);
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES (3, 'App\\Models\\User', 14);
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES (3, 'App\\Models\\User', 15);
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES (3, 'App\\Models\\User', 16);
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES (3, 'App\\Models\\User', 17);
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES (3, 'App\\Models\\User', 18);
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES (3, 'App\\Models\\User', 19);
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES (3, 'App\\Models\\User', 20);
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES (3, 'App\\Models\\User', 21);
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES (3, 'App\\Models\\User', 22);
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES (3, 'App\\Models\\User', 23);
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES (3, 'App\\Models\\User', 24);
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES (3, 'App\\Models\\User', 25);
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES (3, 'App\\Models\\User', 26);
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES (3, 'App\\Models\\User', 27);
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES (3, 'App\\Models\\User', 28);
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES (3, 'App\\Models\\User', 29);
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES (3, 'App\\Models\\User', 30);
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES (3, 'App\\Models\\User', 31);
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES (3, 'App\\Models\\User', 32);
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES (3, 'App\\Models\\User', 33);
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES (3, 'App\\Models\\User', 34);
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES (3, 'App\\Models\\User', 35);
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES (3, 'App\\Models\\User', 36);
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES (3, 'App\\Models\\User', 37);
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES (3, 'App\\Models\\User', 38);
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES (3, 'App\\Models\\User', 39);
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES (3, 'App\\Models\\User', 40);
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES (3, 'App\\Models\\User', 41);
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES (3, 'App\\Models\\User', 42);
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES (3, 'App\\Models\\User', 43);

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` TEXT NOT NULL,
  `notifiable_type` TEXT NOT NULL,
  `notifiable_id` BIGINT NOT NULL,
  `data` TEXT NOT NULL,
  `read_at` DATETIME,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  KEY `notifications_created_at_index` (`created_at`),
  KEY `notifications_read_at_index` (`read_at`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`, `notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE `password_reset_tokens` (
  `email` TEXT NOT NULL,
  `token` TEXT NOT NULL,
  `created_at` DATETIME,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `payroll_allowances`;
CREATE TABLE `payroll_allowances` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `payroll_profile_id` BIGINT NOT NULL,
  `title` TEXT NOT NULL,
  `amount` DECIMAL(20,4) NOT NULL,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  KEY `payroll_allowances_payroll_profile_id_index` (`payroll_profile_id`),
  CONSTRAINT `fk_payroll_allowances_payroll_profile_id_payroll_profiles_id` FOREIGN KEY (`payroll_profile_id`) REFERENCES `payroll_profiles` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `payroll_deductions`;
CREATE TABLE `payroll_deductions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `payroll_profile_id` BIGINT NOT NULL,
  `title` TEXT NOT NULL,
  `amount` DECIMAL(20,4) NOT NULL,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  KEY `payroll_deductions_payroll_profile_id_index` (`payroll_profile_id`),
  CONSTRAINT `fk_payroll_deductions_payroll_profile_id_payroll_profiles_id` FOREIGN KEY (`payroll_profile_id`) REFERENCES `payroll_profiles` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `payroll_items`;
CREATE TABLE `payroll_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `payroll_run_id` BIGINT NOT NULL,
  `payroll_profile_id` BIGINT NOT NULL,
  `user_id` BIGINT NOT NULL,
  `basic_salary` DECIMAL(20,4) NOT NULL,
  `allowances_total` DECIMAL(20,4) NOT NULL DEFAULT '0',
  `deductions_total` DECIMAL(20,4) NOT NULL DEFAULT '0',
  `net_salary` DECIMAL(20,4) NOT NULL,
  `status` TEXT NOT NULL DEFAULT 'generated',
  `paid_at` DATETIME,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  KEY `payroll_items_user_id_status_index` (`user_id`, `status`),
  KEY `payroll_items_payroll_run_id_status_index` (`payroll_run_id`, `status`),
  UNIQUE KEY `payroll_items_payroll_run_id_payroll_profile_id_unique` (`payroll_run_id`, `payroll_profile_id`),
  CONSTRAINT `fk_payroll_items_user_id_users_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
  CONSTRAINT `fk_payroll_items_payroll_profile_id_payroll_profiles_id` FOREIGN KEY (`payroll_profile_id`) REFERENCES `payroll_profiles` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
  CONSTRAINT `fk_payroll_items_payroll_run_id_payroll_runs_id` FOREIGN KEY (`payroll_run_id`) REFERENCES `payroll_runs` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `payroll_profiles`;
CREATE TABLE `payroll_profiles` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT NOT NULL,
  `basic_salary` DECIMAL(20,4) NOT NULL,
  `allowances` DECIMAL(20,4) NOT NULL DEFAULT '0',
  `deductions` DECIMAL(20,4) NOT NULL DEFAULT '0',
  `bank_name` TEXT,
  `account_no` TEXT,
  `status` TEXT NOT NULL DEFAULT 'active',
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  KEY `payroll_profiles_status_user_id_index` (`status`, `user_id`),
  UNIQUE KEY `payroll_profiles_user_id_unique` (`user_id`),
  CONSTRAINT `fk_payroll_profiles_user_id_users_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `payroll_runs`;
CREATE TABLE `payroll_runs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `month` TEXT NOT NULL,
  `run_date` DATE NOT NULL,
  `status` TEXT NOT NULL DEFAULT 'generated',
  `generated_by` BIGINT,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  KEY `payroll_runs_month_status_index` (`month`, `status`),
  UNIQUE KEY `payroll_runs_month_unique` (`month`),
  CONSTRAINT `fk_payroll_runs_generated_by_users_id` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON UPDATE NO ACTION ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `permissions`;
CREATE TABLE `permissions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` TEXT NOT NULL,
  `guard_name` TEXT NOT NULL,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`, `guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES (1, 'manage_users', 'web', '2026-03-25 21:18:16', '2026-03-25 21:18:16');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES (2, 'manage_school_settings', 'web', '2026-03-25 21:18:17', '2026-03-25 21:18:17');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES (3, 'assign_roles', 'web', '2026-03-25 21:18:17', '2026-03-25 21:18:17');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES (4, 'manage_subjects', 'web', '2026-03-25 21:18:17', '2026-03-25 21:18:17');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES (5, 'assign_subjects', 'web', '2026-03-25 21:18:18', '2026-03-25 21:18:18');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES (6, 'manage_subject_assignments', 'web', '2026-03-25 21:18:18', '2026-03-25 21:18:18');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES (7, 'assign_teachers', 'web', '2026-03-25 21:18:18', '2026-03-25 21:18:18');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES (8, 'view_attendance', 'web', '2026-03-25 21:18:19', '2026-03-25 21:18:19');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES (9, 'mark_attendance', 'web', '2026-03-25 21:18:19', '2026-03-25 21:18:19');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES (10, 'enter_marks', 'web', '2026-03-25 21:18:19', '2026-03-25 21:18:19');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES (11, 'view_own_mark_entries', 'web', '2026-03-25 21:18:20', '2026-03-25 21:18:20');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES (12, 'edit_own_mark_entries', 'web', '2026-03-25 21:18:20', '2026-03-25 21:18:20');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES (13, 'delete_own_mark_entries', 'web', '2026-03-25 21:18:20', '2026-03-25 21:18:20');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES (14, 'view_mark_edit_logs', 'web', '2026-03-25 21:18:20', '2026-03-25 21:18:20');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES (15, 'generate_results', 'web', '2026-03-25 21:18:21', '2026-03-25 21:18:21');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES (16, 'view_medical_requests', 'web', '2026-03-25 21:18:21', '2026-03-25 21:18:21');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES (17, 'create_medical_requests', 'web', '2026-03-25 21:18:21', '2026-03-25 21:18:21');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES (18, 'view_teacher_performance', 'web', '2026-03-25 21:18:22', '2026-03-25 21:18:22');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES (19, 'view_fee_structure', 'web', '2026-03-25 21:18:22', '2026-03-25 21:18:22');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES (20, 'create_fee_structure', 'web', '2026-03-25 21:18:23', '2026-03-25 21:18:23');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES (21, 'edit_fee_structure', 'web', '2026-03-25 21:18:23', '2026-03-25 21:18:23');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES (22, 'delete_fee_structure', 'web', '2026-03-25 21:18:23', '2026-03-25 21:18:23');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES (23, 'generate_fee_challans', 'web', '2026-03-25 21:18:23', '2026-03-25 21:18:23');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES (24, 'view_fee_challans', 'web', '2026-03-25 21:18:24', '2026-03-25 21:18:24');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES (25, 'record_fee_payment', 'web', '2026-03-25 21:18:24', '2026-03-25 21:18:24');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES (26, 'view_fee_reports', 'web', '2026-03-25 21:18:25', '2026-03-25 21:18:25');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES (27, 'view_payroll', 'web', '2026-03-25 21:18:25', '2026-03-25 21:18:25');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES (28, 'manage_payroll', 'web', '2026-03-25 21:18:25', '2026-03-25 21:18:25');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES (29, 'generate_salary_sheet', 'web', '2026-03-25 21:18:26', '2026-03-25 21:18:26');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES (30, 'view_salary_slips', 'web', '2026-03-25 21:18:26', '2026-03-25 21:18:26');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES (31, 'edit_salary_structure', 'web', '2026-03-25 21:18:26', '2026-03-25 21:18:26');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES (32, 'manage_payroll_profiles', 'web', '2026-03-25 21:18:27', '2026-03-25 21:18:27');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES (33, 'generate_payroll', 'web', '2026-03-25 21:18:27', '2026-03-25 21:18:27');
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES (34, 'view_payroll_reports', 'web', '2026-03-25 21:18:27', '2026-03-25 21:18:27');

DROP TABLE IF EXISTS `promotion_campaigns`;
CREATE TABLE `promotion_campaigns` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `from_session` TEXT NOT NULL,
  `to_session` TEXT NOT NULL,
  `class_id` BIGINT NOT NULL,
  `created_by` BIGINT NOT NULL,
  `approved_by` BIGINT,
  `status` TEXT NOT NULL DEFAULT 'draft',
  `submitted_at` DATETIME,
  `approved_at` DATETIME,
  `executed_at` DATETIME,
  `principal_note` TEXT,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  KEY `promotion_campaigns_status_submitted_index` (`status`, `submitted_at`),
  UNIQUE KEY `promotion_campaigns_session_class_unique` (`from_session`, `to_session`, `class_id`),
  CONSTRAINT `fk_promotion_campaigns_approved_by_users_id` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON UPDATE NO ACTION ON DELETE SET NULL,
  CONSTRAINT `fk_promotion_campaigns_created_by_users_id` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
  CONSTRAINT `fk_promotion_campaigns_class_id_school_classes_id` FOREIGN KEY (`class_id`) REFERENCES `school_classes` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `push_subscriptions`;
CREATE TABLE `push_subscriptions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `subscribable_type` TEXT NOT NULL,
  `subscribable_id` BIGINT NOT NULL,
  `endpoint` TEXT NOT NULL,
  `public_key` TEXT,
  `auth_token` TEXT,
  `content_encoding` TEXT,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  UNIQUE KEY `push_subscriptions_endpoint_unique` (`endpoint`),
  KEY `push_subscriptions_subscribable_morph_idx` (`subscribable_type`, `subscribable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `report_comments`;
CREATE TABLE `report_comments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` BIGINT NOT NULL,
  `session` TEXT NOT NULL,
  `exam_type` TEXT NOT NULL,
  `generated_by` BIGINT,
  `auto_comment` TEXT,
  `final_comment` TEXT,
  `is_edited` BIGINT NOT NULL DEFAULT '0',
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  KEY `report_comments_session_exam_type_index` (`session`, `exam_type`),
  UNIQUE KEY `report_comments_student_id_session_exam_type_unique` (`student_id`, `session`, `exam_type`),
  CONSTRAINT `fk_report_comments_generated_by_users_id` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON UPDATE NO ACTION ON DELETE SET NULL,
  CONSTRAINT `fk_report_comments_student_id_students_id` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `role_has_permissions`;
CREATE TABLE `role_has_permissions` (
  `permission_id` BIGINT NOT NULL,
  `role_id` BIGINT NOT NULL,
  PRIMARY KEY (`permission_id`, `role_id`),
  CONSTRAINT `fk_role_has_permissions_role_id_roles_id` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
  CONSTRAINT `fk_role_has_permissions_permission_id_permissions_id` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (1, 1);
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (2, 1);
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (3, 1);
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (4, 1);
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (5, 1);
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (6, 1);
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (7, 1);
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (8, 1);
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (9, 1);
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (10, 1);
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (11, 1);
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (12, 1);
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (13, 1);
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (14, 1);
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (15, 1);
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (16, 1);
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (17, 1);
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (18, 1);
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (19, 1);
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (20, 1);
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (21, 1);
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (22, 1);
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (23, 1);
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (24, 1);
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (25, 1);
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (26, 1);
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (27, 1);
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (28, 1);
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (29, 1);
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (30, 1);
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (31, 1);
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (32, 1);
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (33, 1);
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (34, 1);
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (4, 2);
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (5, 2);
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (6, 2);
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (7, 2);
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (8, 2);
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (14, 2);
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (15, 2);
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (16, 2);
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (17, 2);
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (18, 2);
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (8, 3);
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (9, 3);
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (10, 3);
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (11, 3);
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (12, 3);
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (13, 3);
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (16, 4);
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (19, 6);
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (20, 6);
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (21, 6);
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (22, 6);
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (23, 6);
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (24, 6);
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (25, 6);
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (26, 6);
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (27, 6);
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (32, 6);
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (33, 6);
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (30, 6);
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (34, 6);

DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` TEXT NOT NULL,
  `guard_name` TEXT NOT NULL,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`, `guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES (1, 'Admin', 'web', '2026-03-25 21:18:28', '2026-03-25 21:18:28');
INSERT INTO `roles` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES (2, 'Principal', 'web', '2026-03-25 21:18:28', '2026-03-25 21:18:28');
INSERT INTO `roles` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES (3, 'Teacher', 'web', '2026-03-25 21:18:28', '2026-03-25 21:18:28');
INSERT INTO `roles` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES (4, 'Doctor', 'web', '2026-03-25 21:18:28', '2026-03-25 21:18:28');
INSERT INTO `roles` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES (5, 'Student', 'web', '2026-03-25 21:18:29', '2026-03-25 21:18:29');
INSERT INTO `roles` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES (6, 'Accountant', 'web', '2026-03-25 21:18:30', '2026-03-25 21:18:30');

DROP TABLE IF EXISTS `rooms`;
CREATE TABLE `rooms` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` TEXT NOT NULL,
  `capacity` BIGINT,
  `type` TEXT NOT NULL DEFAULT 'classroom',
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rooms_name_unique` (`name`),
  KEY `rooms_type_name_index` (`type`, `name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `rooms` (`id`, `name`, `capacity`, `type`, `created_at`, `updated_at`) VALUES (1, 'P.G Room', NULL, 'classroom', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `rooms` (`id`, `name`, `capacity`, `type`, `created_at`, `updated_at`) VALUES (2, 'Auto Classroom 1', NULL, 'classroom', '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `rooms` (`id`, `name`, `capacity`, `type`, `created_at`, `updated_at`) VALUES (3, 'Auto Lab 1', NULL, 'lab', '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `rooms` (`id`, `name`, `capacity`, `type`, `created_at`, `updated_at`) VALUES (4, 'Auto Lab 2', NULL, 'lab', '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `rooms` (`id`, `name`, `capacity`, `type`, `created_at`, `updated_at`) VALUES (5, 'Auto Classroom 2', NULL, 'classroom', '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `rooms` (`id`, `name`, `capacity`, `type`, `created_at`, `updated_at`) VALUES (6, 'Auto Lab 3', NULL, 'lab', '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `rooms` (`id`, `name`, `capacity`, `type`, `created_at`, `updated_at`) VALUES (7, 'Auto Classroom 3', NULL, 'classroom', '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `rooms` (`id`, `name`, `capacity`, `type`, `created_at`, `updated_at`) VALUES (8, 'Auto Classroom 4', NULL, 'classroom', '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `rooms` (`id`, `name`, `capacity`, `type`, `created_at`, `updated_at`) VALUES (9, 'Auto Classroom 5', NULL, 'classroom', '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `rooms` (`id`, `name`, `capacity`, `type`, `created_at`, `updated_at`) VALUES (10, 'Auto Classroom 6', NULL, 'classroom', '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `rooms` (`id`, `name`, `capacity`, `type`, `created_at`, `updated_at`) VALUES (11, 'Auto Classroom 7', NULL, 'classroom', '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `rooms` (`id`, `name`, `capacity`, `type`, `created_at`, `updated_at`) VALUES (12, 'Auto Classroom 8', NULL, 'classroom', '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `rooms` (`id`, `name`, `capacity`, `type`, `created_at`, `updated_at`) VALUES (13, 'Auto Classroom 9', NULL, 'classroom', '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `rooms` (`id`, `name`, `capacity`, `type`, `created_at`, `updated_at`) VALUES (14, 'Auto Classroom 10', NULL, 'classroom', '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `rooms` (`id`, `name`, `capacity`, `type`, `created_at`, `updated_at`) VALUES (15, 'Auto Classroom 11', NULL, 'classroom', '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `rooms` (`id`, `name`, `capacity`, `type`, `created_at`, `updated_at`) VALUES (16, 'Auto Classroom 12', NULL, 'classroom', '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `rooms` (`id`, `name`, `capacity`, `type`, `created_at`, `updated_at`) VALUES (17, 'Auto Classroom 13', NULL, 'classroom', '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `rooms` (`id`, `name`, `capacity`, `type`, `created_at`, `updated_at`) VALUES (18, 'Auto Classroom 14', NULL, 'classroom', '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `rooms` (`id`, `name`, `capacity`, `type`, `created_at`, `updated_at`) VALUES (19, 'Auto Classroom 15', NULL, 'classroom', '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `rooms` (`id`, `name`, `capacity`, `type`, `created_at`, `updated_at`) VALUES (20, 'Auto Classroom 16', NULL, 'classroom', '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `rooms` (`id`, `name`, `capacity`, `type`, `created_at`, `updated_at`) VALUES (21, 'Auto Classroom 17', NULL, 'classroom', '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `rooms` (`id`, `name`, `capacity`, `type`, `created_at`, `updated_at`) VALUES (22, 'Auto Classroom 18', NULL, 'classroom', '2026-03-26 12:19:27', '2026-03-26 12:19:27');

DROP TABLE IF EXISTS `school_classes`;
CREATE TABLE `school_classes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` TEXT NOT NULL,
  `section` TEXT,
  `status` TEXT NOT NULL DEFAULT 'active',
  `created_at` DATETIME,
  `updated_at` DATETIME,
  `class_teacher_id` BIGINT,
  PRIMARY KEY (`id`),
  KEY `school_classes_class_teacher_id_index` (`class_teacher_id`),
  KEY `school_classes_status_name_index` (`status`, `name`),
  KEY `school_classes_name_section_index` (`name`, `section`),
  CONSTRAINT `fk_school_classes_class_teacher_id_teachers_id` FOREIGN KEY (`class_teacher_id`) REFERENCES `teachers` (`id`) ON UPDATE NO ACTION ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `school_classes` (`id`, `name`, `section`, `status`, `created_at`, `updated_at`, `class_teacher_id`) VALUES (17, 'Nursery', NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:31:41', 1);
INSERT INTO `school_classes` (`id`, `name`, `section`, `status`, `created_at`, `updated_at`, `class_teacher_id`) VALUES (18, 'Prep', NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:19:23', 28);
INSERT INTO `school_classes` (`id`, `name`, `section`, `status`, `created_at`, `updated_at`, `class_teacher_id`) VALUES (36, 'Circle Time', NULL, 'active', '2026-03-25 21:31:41', '2026-03-25 21:31:41', NULL);
INSERT INTO `school_classes` (`id`, `name`, `section`, `status`, `created_at`, `updated_at`, `class_teacher_id`) VALUES (37, '1', NULL, 'active', '2026-03-26 12:19:10', '2026-03-26 12:19:23', 26);
INSERT INTO `school_classes` (`id`, `name`, `section`, `status`, `created_at`, `updated_at`, `class_teacher_id`) VALUES (38, '10', NULL, 'active', '2026-03-26 12:19:10', '2026-03-26 12:19:23', 7);
INSERT INTO `school_classes` (`id`, `name`, `section`, `status`, `created_at`, `updated_at`, `class_teacher_id`) VALUES (39, '11', NULL, 'active', '2026-03-26 12:19:10', '2026-03-26 12:19:23', 30);
INSERT INTO `school_classes` (`id`, `name`, `section`, `status`, `created_at`, `updated_at`, `class_teacher_id`) VALUES (40, '12', NULL, 'active', '2026-03-26 12:19:10', '2026-03-26 12:19:23', 6);
INSERT INTO `school_classes` (`id`, `name`, `section`, `status`, `created_at`, `updated_at`, `class_teacher_id`) VALUES (41, '2', NULL, 'active', '2026-03-26 12:19:10', '2026-03-26 12:19:23', 29);
INSERT INTO `school_classes` (`id`, `name`, `section`, `status`, `created_at`, `updated_at`, `class_teacher_id`) VALUES (42, '3-A', NULL, 'active', '2026-03-26 12:19:10', '2026-03-26 12:19:23', 23);
INSERT INTO `school_classes` (`id`, `name`, `section`, `status`, `created_at`, `updated_at`, `class_teacher_id`) VALUES (43, '3-B', NULL, 'active', '2026-03-26 12:19:10', '2026-03-26 12:19:23', 25);
INSERT INTO `school_classes` (`id`, `name`, `section`, `status`, `created_at`, `updated_at`, `class_teacher_id`) VALUES (44, '4-A', NULL, 'active', '2026-03-26 12:19:10', '2026-03-26 12:19:23', 22);
INSERT INTO `school_classes` (`id`, `name`, `section`, `status`, `created_at`, `updated_at`, `class_teacher_id`) VALUES (45, '4-B', NULL, 'active', '2026-03-26 12:19:10', '2026-03-26 12:19:23', 27);
INSERT INTO `school_classes` (`id`, `name`, `section`, `status`, `created_at`, `updated_at`, `class_teacher_id`) VALUES (46, '5', NULL, 'active', '2026-03-26 12:19:10', '2026-03-26 12:19:23', 15);
INSERT INTO `school_classes` (`id`, `name`, `section`, `status`, `created_at`, `updated_at`, `class_teacher_id`) VALUES (47, '6-A', NULL, 'active', '2026-03-26 12:19:10', '2026-03-26 12:19:23', 21);
INSERT INTO `school_classes` (`id`, `name`, `section`, `status`, `created_at`, `updated_at`, `class_teacher_id`) VALUES (48, '6-B', NULL, 'active', '2026-03-26 12:19:10', '2026-03-26 12:19:23', 14);
INSERT INTO `school_classes` (`id`, `name`, `section`, `status`, `created_at`, `updated_at`, `class_teacher_id`) VALUES (49, '7-A', NULL, 'active', '2026-03-26 12:19:10', '2026-03-26 12:19:23', 9);
INSERT INTO `school_classes` (`id`, `name`, `section`, `status`, `created_at`, `updated_at`, `class_teacher_id`) VALUES (50, '7-B', NULL, 'active', '2026-03-26 12:19:10', '2026-03-26 12:19:23', 19);
INSERT INTO `school_classes` (`id`, `name`, `section`, `status`, `created_at`, `updated_at`, `class_teacher_id`) VALUES (51, '8', NULL, 'active', '2026-03-26 12:19:10', '2026-03-26 12:19:23', 20);
INSERT INTO `school_classes` (`id`, `name`, `section`, `status`, `created_at`, `updated_at`, `class_teacher_id`) VALUES (52, '9-A', NULL, 'active', '2026-03-26 12:19:10', '2026-03-26 12:19:23', 36);
INSERT INTO `school_classes` (`id`, `name`, `section`, `status`, `created_at`, `updated_at`, `class_teacher_id`) VALUES (53, '9-B', NULL, 'active', '2026-03-26 12:19:10', '2026-03-26 12:19:23', 13);
INSERT INTO `school_classes` (`id`, `name`, `section`, `status`, `created_at`, `updated_at`, `class_teacher_id`) VALUES (54, 'PG', NULL, 'active', '2026-03-26 12:19:10', '2026-03-26 12:19:23', 38);

DROP TABLE IF EXISTS `school_settings`;
CREATE TABLE `school_settings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `school_name` TEXT NOT NULL,
  `logo_path` TEXT,
  `address` TEXT,
  `phone` TEXT,
  `email` TEXT,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  `block_results_for_defaulters` BIGINT NOT NULL DEFAULT '0',
  `block_admit_card_for_defaulters` BIGINT NOT NULL DEFAULT '0',
  `block_id_card_for_defaulters` BIGINT NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `school_settings` (`id`, `school_name`, `logo_path`, `address`, `phone`, `email`, `created_at`, `updated_at`, `block_results_for_defaulters`, `block_admit_card_for_defaulters`, `block_id_card_for_defaulters`) VALUES (1, 'National School', NULL, NULL, NULL, NULL, '2026-03-25 21:18:48', '2026-03-25 21:18:48', 0, 0, 0);

DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT,
  `ip_address` TEXT,
  `user_agent` TEXT,
  `payload` TEXT NOT NULL,
  `last_activity` BIGINT NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_last_activity_index` (`last_activity`),
  KEY `sessions_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `student_arrears`;
CREATE TABLE `student_arrears` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` BIGINT NOT NULL,
  `session` TEXT,
  `title` TEXT NOT NULL,
  `amount` DECIMAL(20,4) NOT NULL,
  `paid_amount` DECIMAL(20,4) NOT NULL DEFAULT '0',
  `status` TEXT NOT NULL DEFAULT 'pending',
  `due_date` DATE,
  `notes` TEXT,
  `added_by` BIGINT,
  `resolved_at` DATETIME,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  KEY `student_arrears_session_status_index` (`session`, `status`),
  KEY `student_arrears_student_status_index` (`student_id`, `status`),
  CONSTRAINT `fk_student_arrears_added_by_users_id` FOREIGN KEY (`added_by`) REFERENCES `users` (`id`) ON UPDATE NO ACTION ON DELETE SET NULL,
  CONSTRAINT `fk_student_arrears_student_id_students_id` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `student_attendance`;
CREATE TABLE `student_attendance` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` BIGINT NOT NULL,
  `date` DATE NOT NULL,
  `status` TEXT NOT NULL,
  `remarks` TEXT,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  KEY `student_attendance_student_id_date_index` (`student_id`, `date`),
  CONSTRAINT `fk_student_attendance_student_id_students_id` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `student_class_histories`;
CREATE TABLE `student_class_histories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` BIGINT NOT NULL,
  `class_id` BIGINT NOT NULL,
  `session` TEXT NOT NULL,
  `status` TEXT NOT NULL DEFAULT 'active',
  `joined_on` DATE,
  `left_on` DATE,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  KEY `student_class_histories_class_session_status_index` (`class_id`, `session`, `status`),
  UNIQUE KEY `student_class_histories_student_class_session_unique` (`student_id`, `class_id`, `session`),
  CONSTRAINT `fk_student_class_histories_class_id_school_classes_id` FOREIGN KEY (`class_id`) REFERENCES `school_classes` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
  CONSTRAINT `fk_student_class_histories_student_id_students_id` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `student_fee_assignments`;
CREATE TABLE `student_fee_assignments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` BIGINT NOT NULL,
  `fee_structure_id` BIGINT NOT NULL,
  `session` TEXT NOT NULL,
  `custom_amount` DECIMAL(20,4),
  `is_active` BIGINT NOT NULL DEFAULT '1',
  `assigned_by` BIGINT,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  KEY `student_fee_assignments_session_is_active_index` (`session`, `is_active`),
  UNIQUE KEY `student_fee_assignments_unique` (`student_id`, `fee_structure_id`, `session`),
  CONSTRAINT `fk_student_fee_assignments_assigned_by_users_id` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON UPDATE NO ACTION ON DELETE SET NULL,
  CONSTRAINT `fk_student_fee_assignments_fee_structure_id_fee_structures_id` FOREIGN KEY (`fee_structure_id`) REFERENCES `fee_structures` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
  CONSTRAINT `fk_student_fee_assignments_student_id_students_id` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `student_fee_structures`;
CREATE TABLE `student_fee_structures` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` BIGINT NOT NULL,
  `session` TEXT NOT NULL,
  `tuition_fee` DECIMAL(20,4) NOT NULL DEFAULT '0',
  `computer_fee` DECIMAL(20,4) NOT NULL DEFAULT '0',
  `exam_fee` DECIMAL(20,4) NOT NULL DEFAULT '0',
  `is_active` BIGINT NOT NULL DEFAULT '1',
  `created_by` BIGINT,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  KEY `student_fee_structures_session_active_index` (`session`, `is_active`),
  UNIQUE KEY `student_fee_structures_student_session_unique` (`student_id`, `session`),
  CONSTRAINT `fk_student_fee_structures_created_by_users_id` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE NO ACTION ON DELETE SET NULL,
  CONSTRAINT `fk_student_fee_structures_student_id_students_id` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `student_learning_profiles`;
CREATE TABLE `student_learning_profiles` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` BIGINT NOT NULL,
  `session` TEXT NOT NULL,
  `strengths` TEXT,
  `support_areas` TEXT,
  `best_aptitude` TEXT,
  `learning_pattern` TEXT,
  `attendance_percentage` DECIMAL(20,4),
  `overall_average` DECIMAL(20,4),
  `subject_scores` TEXT,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  KEY `student_learning_profiles_session_student_id_index` (`session`, `student_id`),
  UNIQUE KEY `student_learning_profiles_student_id_session_unique` (`student_id`, `session`),
  CONSTRAINT `fk_student_learning_profiles_student_id_students_id` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `student_performance_features`;
CREATE TABLE `student_performance_features` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `session` TEXT NOT NULL,
  `student_id` BIGINT NOT NULL,
  `attendance_rate` DOUBLE NOT NULL DEFAULT '0',
  `avg_class_test` DOUBLE,
  `avg_bimonthly` DOUBLE,
  `avg_first_term` DOUBLE,
  `trend_slope` DOUBLE NOT NULL DEFAULT '0',
  `last_assessment_score` DOUBLE,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  KEY `student_performance_features_student_id_index` (`student_id`),
  KEY `student_performance_features_session_index` (`session`),
  UNIQUE KEY `spf_session_student_unique` (`session`, `student_id`),
  CONSTRAINT `fk_student_performance_features_student_id_students_id` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `student_promotions`;
CREATE TABLE `student_promotions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `promotion_campaign_id` BIGINT NOT NULL,
  `student_id` BIGINT NOT NULL,
  `from_class_id` BIGINT NOT NULL,
  `to_class_id` BIGINT,
  `final_percentage` DECIMAL(20,4),
  `final_grade` TEXT,
  `is_passed` BIGINT NOT NULL DEFAULT '0',
  `teacher_decision` TEXT,
  `teacher_note` TEXT,
  `principal_decision` TEXT,
  `principal_note` TEXT,
  `final_status` TEXT NOT NULL DEFAULT 'pending',
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  KEY `student_promotions_campaign_status_index` (`promotion_campaign_id`, `final_status`),
  UNIQUE KEY `student_promotions_campaign_student_unique` (`promotion_campaign_id`, `student_id`),
  CONSTRAINT `fk_student_promotions_to_class_id_school_classes_id` FOREIGN KEY (`to_class_id`) REFERENCES `school_classes` (`id`) ON UPDATE NO ACTION ON DELETE SET NULL,
  CONSTRAINT `fk_student_promotions_from_class_id_school_classes_id` FOREIGN KEY (`from_class_id`) REFERENCES `school_classes` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
  CONSTRAINT `fk_student_promotions_student_id_students_id` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
  CONSTRAINT `fk_student_promotions_promotion_campaign_id_promotion_campaigns_id` FOREIGN KEY (`promotion_campaign_id`) REFERENCES `promotion_campaigns` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `student_results`;
CREATE TABLE `student_results` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` BIGINT NOT NULL,
  `subject_id` BIGINT NOT NULL,
  `exam_name` TEXT NOT NULL,
  `total_marks` BIGINT NOT NULL,
  `obtained_marks` BIGINT NOT NULL,
  `result_date` DATE,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  KEY `student_results_student_id_result_date_index` (`student_id`, `result_date`),
  CONSTRAINT `fk_student_results_subject_id_subjects_id` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
  CONSTRAINT `fk_student_results_student_id_students_id` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `student_risk_predictions`;
CREATE TABLE `student_risk_predictions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `session` TEXT NOT NULL,
  `student_id` BIGINT NOT NULL,
  `target_exam` TEXT NOT NULL,
  `predicted_percentage` DOUBLE NOT NULL,
  `risk_level` TEXT NOT NULL,
  `confidence` DOUBLE NOT NULL,
  `explanation` TEXT,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  KEY `student_risk_predictions_student_id_index` (`student_id`),
  KEY `student_risk_predictions_risk_level_index` (`risk_level`),
  KEY `student_risk_predictions_session_target_exam_index` (`session`, `target_exam`),
  UNIQUE KEY `srp_session_student_exam_unique` (`session`, `student_id`, `target_exam`),
  CONSTRAINT `fk_student_risk_predictions_student_id_students_id` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `student_subject`;
CREATE TABLE `student_subject` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` BIGINT NOT NULL,
  `subject_id` BIGINT NOT NULL,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_subject_student_id_subject_id_unique` (`student_id`, `subject_id`),
  CONSTRAINT `fk_student_subject_subject_id_subjects_id` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
  CONSTRAINT `fk_student_subject_student_id_students_id` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `student_subject_assignments`;
CREATE TABLE `student_subject_assignments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `session` TEXT NOT NULL,
  `student_id` BIGINT NOT NULL,
  `class_id` BIGINT NOT NULL,
  `subject_id` BIGINT NOT NULL,
  `assigned_by` BIGINT,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  `subject_group_id` BIGINT,
  PRIMARY KEY (`id`),
  KEY `student_subject_assignments_group_lookup` (`session`, `student_id`, `subject_group_id`),
  UNIQUE KEY `student_subject_assignments_unique` (`session`, `student_id`, `subject_id`),
  KEY `student_subject_assignments_session_class_id_index` (`session`, `class_id`),
  KEY `student_subject_assignments_session_assigned_by_index` (`session`, `assigned_by`),
  CONSTRAINT `fk_student_subject_assignments_subject_group_id_subject_groups_id` FOREIGN KEY (`subject_group_id`) REFERENCES `subject_groups` (`id`) ON UPDATE NO ACTION ON DELETE SET NULL,
  CONSTRAINT `fk_student_subject_assignments_student_id_students_id` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
  CONSTRAINT `fk_student_subject_assignments_class_id_school_classes_id` FOREIGN KEY (`class_id`) REFERENCES `school_classes` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
  CONSTRAINT `fk_student_subject_assignments_subject_id_subjects_id` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
  CONSTRAINT `fk_student_subject_assignments_assigned_by_users_id` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON UPDATE NO ACTION ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (1, '2025-2026', 284, 37, 18, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (2, '2025-2026', 284, 37, 21, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (3, '2025-2026', 284, 37, 1, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (4, '2025-2026', 284, 37, 15, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (5, '2025-2026', 284, 37, 19, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (6, '2025-2026', 284, 37, 25, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (7, '2025-2026', 284, 37, 14, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (8, '2025-2026', 284, 37, 2, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (9, '2025-2026', 284, 37, 4, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (10, '2025-2026', 284, 37, 26, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (11, '2025-2026', 284, 37, 28, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (12, '2025-2026', 284, 37, 3, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (13, '2025-2026', 292, 37, 18, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (14, '2025-2026', 292, 37, 21, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (15, '2025-2026', 292, 37, 1, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (16, '2025-2026', 292, 37, 15, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (17, '2025-2026', 292, 37, 19, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (18, '2025-2026', 292, 37, 25, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (19, '2025-2026', 292, 37, 14, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (20, '2025-2026', 292, 37, 2, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (21, '2025-2026', 292, 37, 4, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (22, '2025-2026', 292, 37, 26, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (23, '2025-2026', 292, 37, 28, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (24, '2025-2026', 292, 37, 3, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (25, '2025-2026', 295, 37, 18, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (26, '2025-2026', 295, 37, 21, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (27, '2025-2026', 295, 37, 1, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (28, '2025-2026', 295, 37, 15, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (29, '2025-2026', 295, 37, 19, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (30, '2025-2026', 295, 37, 25, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (31, '2025-2026', 295, 37, 14, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (32, '2025-2026', 295, 37, 2, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (33, '2025-2026', 295, 37, 4, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (34, '2025-2026', 295, 37, 26, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (35, '2025-2026', 295, 37, 28, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (36, '2025-2026', 295, 37, 3, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (37, '2025-2026', 288, 37, 18, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (38, '2025-2026', 288, 37, 21, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (39, '2025-2026', 288, 37, 1, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (40, '2025-2026', 288, 37, 15, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (41, '2025-2026', 288, 37, 19, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (42, '2025-2026', 288, 37, 25, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (43, '2025-2026', 288, 37, 14, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (44, '2025-2026', 288, 37, 2, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (45, '2025-2026', 288, 37, 4, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (46, '2025-2026', 288, 37, 26, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (47, '2025-2026', 288, 37, 28, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (48, '2025-2026', 288, 37, 3, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (49, '2025-2026', 280, 37, 18, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (50, '2025-2026', 280, 37, 21, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (51, '2025-2026', 280, 37, 1, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (52, '2025-2026', 280, 37, 15, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (53, '2025-2026', 280, 37, 19, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (54, '2025-2026', 280, 37, 25, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (55, '2025-2026', 280, 37, 14, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (56, '2025-2026', 280, 37, 2, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (57, '2025-2026', 280, 37, 4, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (58, '2025-2026', 280, 37, 26, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (59, '2025-2026', 280, 37, 28, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (60, '2025-2026', 280, 37, 3, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (61, '2025-2026', 291, 37, 18, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (62, '2025-2026', 291, 37, 21, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (63, '2025-2026', 291, 37, 1, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (64, '2025-2026', 291, 37, 15, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (65, '2025-2026', 291, 37, 19, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (66, '2025-2026', 291, 37, 25, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (67, '2025-2026', 291, 37, 14, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (68, '2025-2026', 291, 37, 2, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (69, '2025-2026', 291, 37, 4, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (70, '2025-2026', 291, 37, 26, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (71, '2025-2026', 291, 37, 28, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (72, '2025-2026', 291, 37, 3, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (73, '2025-2026', 290, 37, 18, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (74, '2025-2026', 290, 37, 21, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (75, '2025-2026', 290, 37, 1, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (76, '2025-2026', 290, 37, 15, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (77, '2025-2026', 290, 37, 19, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (78, '2025-2026', 290, 37, 25, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (79, '2025-2026', 290, 37, 14, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (80, '2025-2026', 290, 37, 2, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (81, '2025-2026', 290, 37, 4, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (82, '2025-2026', 290, 37, 26, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (83, '2025-2026', 290, 37, 28, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (84, '2025-2026', 290, 37, 3, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (85, '2025-2026', 285, 37, 18, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (86, '2025-2026', 285, 37, 21, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (87, '2025-2026', 285, 37, 1, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (88, '2025-2026', 285, 37, 15, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (89, '2025-2026', 285, 37, 19, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (90, '2025-2026', 285, 37, 25, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (91, '2025-2026', 285, 37, 14, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (92, '2025-2026', 285, 37, 2, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (93, '2025-2026', 285, 37, 4, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (94, '2025-2026', 285, 37, 26, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (95, '2025-2026', 285, 37, 28, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (96, '2025-2026', 285, 37, 3, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (97, '2025-2026', 279, 37, 18, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (98, '2025-2026', 279, 37, 21, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (99, '2025-2026', 279, 37, 1, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (100, '2025-2026', 279, 37, 15, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (101, '2025-2026', 279, 37, 19, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (102, '2025-2026', 279, 37, 25, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (103, '2025-2026', 279, 37, 14, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (104, '2025-2026', 279, 37, 2, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (105, '2025-2026', 279, 37, 4, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (106, '2025-2026', 279, 37, 26, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (107, '2025-2026', 279, 37, 28, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (108, '2025-2026', 279, 37, 3, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (109, '2025-2026', 286, 37, 18, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (110, '2025-2026', 286, 37, 21, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (111, '2025-2026', 286, 37, 1, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (112, '2025-2026', 286, 37, 15, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (113, '2025-2026', 286, 37, 19, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (114, '2025-2026', 286, 37, 25, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (115, '2025-2026', 286, 37, 14, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (116, '2025-2026', 286, 37, 2, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (117, '2025-2026', 286, 37, 4, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (118, '2025-2026', 286, 37, 26, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (119, '2025-2026', 286, 37, 28, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (120, '2025-2026', 286, 37, 3, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (121, '2025-2026', 282, 37, 18, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (122, '2025-2026', 282, 37, 21, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (123, '2025-2026', 282, 37, 1, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (124, '2025-2026', 282, 37, 15, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (125, '2025-2026', 282, 37, 19, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (126, '2025-2026', 282, 37, 25, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (127, '2025-2026', 282, 37, 14, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (128, '2025-2026', 282, 37, 2, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (129, '2025-2026', 282, 37, 4, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (130, '2025-2026', 282, 37, 26, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (131, '2025-2026', 282, 37, 28, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (132, '2025-2026', 282, 37, 3, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (133, '2025-2026', 278, 37, 18, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (134, '2025-2026', 278, 37, 21, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (135, '2025-2026', 278, 37, 1, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (136, '2025-2026', 278, 37, 15, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (137, '2025-2026', 278, 37, 19, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (138, '2025-2026', 278, 37, 25, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (139, '2025-2026', 278, 37, 14, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (140, '2025-2026', 278, 37, 2, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (141, '2025-2026', 278, 37, 4, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (142, '2025-2026', 278, 37, 26, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (143, '2025-2026', 278, 37, 28, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (144, '2025-2026', 278, 37, 3, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (145, '2025-2026', 287, 37, 18, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (146, '2025-2026', 287, 37, 21, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (147, '2025-2026', 287, 37, 1, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (148, '2025-2026', 287, 37, 15, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (149, '2025-2026', 287, 37, 19, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (150, '2025-2026', 287, 37, 25, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (151, '2025-2026', 287, 37, 14, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (152, '2025-2026', 287, 37, 2, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (153, '2025-2026', 287, 37, 4, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (154, '2025-2026', 287, 37, 26, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (155, '2025-2026', 287, 37, 28, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (156, '2025-2026', 287, 37, 3, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (157, '2025-2026', 289, 37, 18, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (158, '2025-2026', 289, 37, 21, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (159, '2025-2026', 289, 37, 1, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (160, '2025-2026', 289, 37, 15, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (161, '2025-2026', 289, 37, 19, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (162, '2025-2026', 289, 37, 25, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (163, '2025-2026', 289, 37, 14, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (164, '2025-2026', 289, 37, 2, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (165, '2025-2026', 289, 37, 4, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (166, '2025-2026', 289, 37, 26, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (167, '2025-2026', 289, 37, 28, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (168, '2025-2026', 289, 37, 3, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (169, '2025-2026', 294, 37, 18, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (170, '2025-2026', 294, 37, 21, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (171, '2025-2026', 294, 37, 1, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (172, '2025-2026', 294, 37, 15, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (173, '2025-2026', 294, 37, 19, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (174, '2025-2026', 294, 37, 25, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (175, '2025-2026', 294, 37, 14, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (176, '2025-2026', 294, 37, 2, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (177, '2025-2026', 294, 37, 4, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (178, '2025-2026', 294, 37, 26, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (179, '2025-2026', 294, 37, 28, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (180, '2025-2026', 294, 37, 3, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (181, '2025-2026', 293, 37, 18, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (182, '2025-2026', 293, 37, 21, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (183, '2025-2026', 293, 37, 1, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (184, '2025-2026', 293, 37, 15, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (185, '2025-2026', 293, 37, 19, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (186, '2025-2026', 293, 37, 25, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (187, '2025-2026', 293, 37, 14, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (188, '2025-2026', 293, 37, 2, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (189, '2025-2026', 293, 37, 4, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (190, '2025-2026', 293, 37, 26, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (191, '2025-2026', 293, 37, 28, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (192, '2025-2026', 293, 37, 3, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (193, '2025-2026', 281, 37, 18, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (194, '2025-2026', 281, 37, 21, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (195, '2025-2026', 281, 37, 1, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (196, '2025-2026', 281, 37, 15, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (197, '2025-2026', 281, 37, 19, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (198, '2025-2026', 281, 37, 25, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (199, '2025-2026', 281, 37, 14, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (200, '2025-2026', 281, 37, 2, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (201, '2025-2026', 281, 37, 4, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (202, '2025-2026', 281, 37, 26, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (203, '2025-2026', 281, 37, 28, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (204, '2025-2026', 281, 37, 3, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (205, '2025-2026', 283, 37, 18, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (206, '2025-2026', 283, 37, 21, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (207, '2025-2026', 283, 37, 1, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (208, '2025-2026', 283, 37, 15, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (209, '2025-2026', 283, 37, 19, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (210, '2025-2026', 283, 37, 25, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (211, '2025-2026', 283, 37, 14, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (212, '2025-2026', 283, 37, 2, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (213, '2025-2026', 283, 37, 4, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (214, '2025-2026', 283, 37, 26, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (215, '2025-2026', 283, 37, 28, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);
INSERT INTO `student_subject_assignments` (`id`, `session`, `student_id`, `class_id`, `subject_id`, `assigned_by`, `created_at`, `updated_at`, `subject_group_id`) VALUES (216, '2025-2026', 283, 37, 3, 2, '2026-03-26 15:11:29', '2026-03-26 15:11:29', NULL);

DROP TABLE IF EXISTS `student_subjects`;
CREATE TABLE `student_subjects` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` BIGINT NOT NULL,
  `subject_id` BIGINT NOT NULL,
  `session` TEXT NOT NULL,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  KEY `student_subjects_subject_id_session_index` (`subject_id`, `session`),
  KEY `student_subjects_student_id_session_index` (`student_id`, `session`),
  UNIQUE KEY `student_subjects_student_id_subject_id_session_unique` (`student_id`, `subject_id`, `session`),
  CONSTRAINT `fk_student_subjects_subject_id_subjects_id` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
  CONSTRAINT `fk_student_subjects_student_id_students_id` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `students`;
CREATE TABLE `students` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` TEXT NOT NULL,
  `name` TEXT NOT NULL,
  `father_name` TEXT,
  `class_id` BIGINT NOT NULL,
  `date_of_birth` DATE,
  `age` BIGINT,
  `contact` TEXT,
  `address` TEXT,
  `status` TEXT NOT NULL DEFAULT 'active',
  `created_at` DATETIME,
  `updated_at` DATETIME,
  `deleted_at` DATETIME,
  `photo_path` TEXT,
  `qr_token` TEXT,
  PRIMARY KEY (`id`),
  UNIQUE KEY `students_qr_token_unique` (`qr_token`),
  KEY `students_status_student_id_index` (`status`, `student_id`),
  KEY `students_status_class_id_index` (`status`, `class_id`),
  UNIQUE KEY `students_student_id_unique` (`student_id`),
  KEY `students_class_id_index` (`class_id`),
  KEY `students_father_name_index` (`father_name`),
  KEY `students_name_status_index` (`name`, `status`),
  CONSTRAINT `fk_students_class_id_school_classes_id` FOREIGN KEY (`class_id`) REFERENCES `school_classes` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (243, 'IMP000001', 'Abu-bakar', NULL, 54, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (244, 'IMP000002', 'Ruqia', NULL, 54, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (245, 'IMP000003', 'Mujtaba', NULL, 54, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (246, 'IMP000004', 'Aahil Touqeer Butt', NULL, 54, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (247, 'IMP000005', 'Areeba Touqeer Butt', NULL, 54, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (248, 'IMP000006', 'Sehar', NULL, 54, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (249, 'IMP000007', 'Hadia', NULL, 54, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (250, 'IMP000008', 'Abdul qadir', NULL, 17, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (251, 'IMP000009', 'Amber', NULL, 17, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (252, 'IMP000010', 'Ameera', NULL, 17, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (253, 'IMP000011', 'Mustafa', NULL, 17, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (254, 'IMP000012', 'Rehan Maskoor', NULL, 17, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (255, 'IMP000013', 'Marwa', NULL, 17, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (256, 'IMP000014', 'Zeeshan', NULL, 17, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (257, 'IMP000015', 'Uzair', NULL, 17, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (258, 'IMP000016', 'Rohan', NULL, 17, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (259, 'IMP000017', 'Ibrahim', NULL, 17, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (260, 'IMP000018', 'Ismail', NULL, 17, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (261, '229', 'Naqash Naveed', NULL, 18, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (262, '131', 'Saim Akhlaq', NULL, 18, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (263, '348', 'Haleema Akhtar Sajawal', NULL, 18, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (264, '362', 'Alina akhtar Haroon', NULL, 18, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (265, '360', 'Ayat Fatima Sartaj', NULL, 18, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (266, '390', 'Sehar Mohsin', NULL, 18, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (267, '504', 'Ali Hamza', NULL, 18, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (268, '400', 'Zartaj Ejaz', NULL, 18, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (269, 'IMP000027', 'Essa Ali Akhtar', NULL, 18, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (270, '490', 'Adeeb Ch', NULL, 18, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (271, '473', 'Saim raza', NULL, 18, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (272, 'IMP000030', 'Saqib butt', NULL, 18, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (273, 'IMP000031', 'Zahid iqbal', NULL, 18, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (274, 'IMP000032', 'Ishtiaq', NULL, 18, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (275, 'IMP000033', 'Maryam', NULL, 18, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (276, 'IMP000034', 'Samaviya', NULL, 18, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (277, 'IMP000035', 'Muhammad Hassan Majid', NULL, 18, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (278, 'IMP000036', 'Ibrahim Jameel', NULL, 37, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (279, 'IMP000037', 'Hamza Ali Akhtar', NULL, 37, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (280, 'IMP000038', 'Asad Farooq', NULL, 37, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (281, 'IMP000039', 'safa orangazaib', NULL, 37, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (282, 'IMP000040', 'Hussnain ali', NULL, 37, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (283, 'IMP000041', 'zulqarnain', NULL, 37, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (284, 'IMP000042', 'Afshan zakir', NULL, 37, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (285, 'IMP000043', 'Fatima Zahra', NULL, 37, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (286, 'IMP000044', 'Hashim', NULL, 37, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (287, '298', 'Maida bibi', NULL, 37, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (288, '236', 'Arooj Fatima', NULL, 37, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (289, 'IMP000047', 'Saqib shahzad', NULL, 37, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (290, 'IMP000048', 'Fatima Maskoor', NULL, 37, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (291, 'IMP000049', 'Asad abbas', NULL, 37, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (292, 'IMP000050', 'Aiman', NULL, 37, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (293, 'IMP000051', 'Ume Habiba', NULL, 37, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (294, '488', 'Syeda Bibi Eman Fatima', NULL, 37, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (295, 'KORT545', 'Arman Zahoor', NULL, 37, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (296, '273', 'Laiba Akhtar Shameer', NULL, 41, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (297, '401', 'zoya Rani', NULL, 41, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (298, '242', 'Ateeqa Shoukat', NULL, 41, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (299, '496', 'Aiza Fatima', NULL, 41, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (300, '45', 'Abdullah Majeed', NULL, 41, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (301, 'IMP000059', 'Arman shahzad', NULL, 41, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (302, '69', 'Iqrar Shoukat', NULL, 41, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (303, '82', 'Muhammad ali', NULL, 41, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (304, 'IMP000062', 'Adil Akhtar', NULL, 41, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (305, 'IMP000063', 'Muhammad balaj', NULL, 41, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (306, '175', 'Nouman Majeed', NULL, 41, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (307, 'IMP000065', 'Haris ali akhtar', NULL, 41, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (308, 'IMP000066', 'Muhammad sheryar', NULL, 41, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (309, '480', 'samar mazhar', NULL, 41, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (310, 'IMP000068', 'zulikha asghar', NULL, 41, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (311, 'IMP000069', 'Zahra asgar', NULL, 41, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (312, 'IMP000070', 'aqib', NULL, 41, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (313, '44', 'Ehsan Ullah', NULL, 41, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (314, 'IMP000072', 'Alishba Shahpal', NULL, 41, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (315, 'IMP000073', 'Maisam Ikhlaq', NULL, 41, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (316, 'IMP000074', 'Ali Maroof', NULL, 41, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (317, '198', 'Sayyam Khalid', NULL, 42, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (318, '220', 'Wahab Ishaq', NULL, 42, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (319, '193', 'Saqib Gull', NULL, 42, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (320, '234', 'Muhammad Ahsan Mehmood', NULL, 42, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (321, '70', 'Hadi Hassan', NULL, 42, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (322, 'IMP000080', 'Muhammad Ibrahim', NULL, 42, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (323, '232', 'Muhammad Husnain', NULL, 42, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (324, '300', 'Meerab Shehzadi', NULL, 42, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (325, '304', 'Kashaf Naveed', NULL, 42, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (326, '386', 'Syeda Zainab Batool Sherazi', NULL, 42, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (327, '419', 'Shanza Bibi', NULL, 42, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (328, 'IMP000086', 'Rukhsar Mashkoor', NULL, 42, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (329, '489', 'Anaya Fatima', NULL, 42, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (330, 'IMP000088', 'Um e Amara Shakir', NULL, 42, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (331, 'IMP000089', 'Musa jamil', NULL, 42, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (332, 'IMP000090', 'M. Huzaifa', NULL, 42, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (333, 'IMP000091', 'javeria bibi', NULL, 42, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (334, 'IMP000092', 'sami khan', NULL, 42, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (335, 'IMP000093', 'Sameer', NULL, 42, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (336, 'IMP000094', 'Ayan', NULL, 42, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (337, '100', 'Muhammad Ali', NULL, 43, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (338, '181', 'Muhammad Adil', NULL, 43, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (339, '267', 'Eshal Asghar', NULL, 43, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (340, '375', 'Iqra Bibi', NULL, 43, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (341, '398', 'Maliha Shafique', NULL, 43, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (342, 'IMP000100', 'Hoorain Fatima', NULL, 43, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (343, '486', 'Bushra Arif', NULL, 43, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (344, '487', 'Bibi Tehreem', NULL, 43, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (345, 'IMP000103', 'Bilal Sher Khan', NULL, 43, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (346, '429', 'Naqeeb ullah', NULL, 43, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (347, 'IMP000105', 'Hassan Ali Akhtar', NULL, 43, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (348, '144', 'Zain Manzoor', NULL, 43, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (349, 'IMP000107', 'M. Zahid', NULL, 43, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (350, 'IMP000108', 'Dua Jannat (D.S)', NULL, 43, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (351, 'IMP000109', 'Sayam', NULL, 43, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (352, 'IMP000110', 'Anas', NULL, 43, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (353, 'IMP000111', 'Eman', NULL, 43, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (354, '6', 'Arshan Ali Mudassar', NULL, 44, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (355, '31', 'Abdul Rehman', NULL, 44, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (356, '160', 'Muhammad Ghulam Khaliq', NULL, 44, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (357, '38', 'Hashim Zafar', NULL, 44, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (358, 'IMP000116', 'Farhan-Ur-Rehman', NULL, 44, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (359, '420', 'Syeda Razia Batool', NULL, 44, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (360, '367', 'Qurat ul Ain', NULL, 44, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (361, '371', 'Omaima Farooq', NULL, 44, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (362, '402', 'Saba Sajid', NULL, 44, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (363, 'IMP000121', 'Nasta Bibi (D.S)', NULL, 44, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (364, 'IMP000122', 'Bilal Mustafa', NULL, 44, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (365, 'IMP000123', 'Khizar Farooq', NULL, 44, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (366, 'IMP000124', 'M.Adil', NULL, 44, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (367, 'IMP000125', 'Zeeshan', NULL, 44, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (368, 'IMP000126', 'Abdullah Aziz', NULL, 45, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (369, '166', 'Muhammad Hashir', NULL, 45, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (370, '90', 'Hammad Shakeel', NULL, 45, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (371, '281', 'Anfal Younas', NULL, 45, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (372, '325', 'Noor-Ul-Ain', NULL, 45, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (373, '328', 'Mehnaz Bibi', NULL, 45, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (374, 'IMP000132', 'Meerab Fatima', NULL, 45, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (375, 'IMP000133', 'Meshal Ali', NULL, 45, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (376, 'IMP000134', 'Huzaifa shehzad', NULL, 45, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (377, 'IMP000135', 'Fatima Azam', NULL, 45, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (378, 'IMP000136', 'Wajid sajid', NULL, 45, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (379, 'IMP000137', 'Shamim Khan (D.S)', NULL, 45, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (380, 'IMP000138', 'Zulqarnain Nawaz', NULL, 45, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (381, 'IMP000139', 'Muhammad Zayan', NULL, 45, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (382, '120', 'Muhammad Arman Sajid', NULL, 46, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (383, '126', 'Shahid Khan', NULL, 46, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (384, '52', 'Azan Khalid', NULL, 46, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (385, '230', 'Muhammad Azan', NULL, 46, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (386, '161', 'Muhammad Daud Shahzad', NULL, 46, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (387, '183', 'Qasim Ali', NULL, 46, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (388, '201', 'Uzair Yaseen', NULL, 46, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (389, '284', 'Fiza Noor', NULL, 46, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (390, '324', 'Naseem Fatima', NULL, 46, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (391, '352', 'Zoya Mudassar', NULL, 46, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (392, '378', 'Hadia Noor', NULL, 46, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (393, 'IMP000151', 'Wasalat raza', NULL, 46, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (394, 'IMP000152', 'Umer Siddique', NULL, 46, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (395, '476', 'Shoaib Saleem', NULL, 46, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (396, 'IMP000154', 'Ali Raza', NULL, 46, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (397, 'IMP000155', 'Muzaffar safeer', NULL, 46, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (398, '363', 'Rania shahid', NULL, 46, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (399, 'IMP000157', 'Alishba eman', NULL, 46, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (400, 'IMP000158', 'Mahnoor kausar', NULL, 46, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (401, 'IMP000159', 'Haleema sadia', NULL, 46, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (402, 'IMP000160', 'Shehryar Jhangir', NULL, 46, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (403, 'IMP000161', 'Jaweria Bibi', NULL, 46, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (404, 'IMP000162', 'Ruqiya Bibi', NULL, 46, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (405, 'IMP000163', 'Raja Manzar Khan', NULL, 46, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (406, '224', 'Umar Khatab', NULL, 47, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (407, '111', 'Muhammad Sajeel', NULL, 47, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (408, 'IMP000166', 'Jabran Fareed', NULL, 47, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (409, '59', 'Khayam Khalil', NULL, 47, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (410, '180', 'Muhammad Abdullah Shahid', NULL, 47, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (411, 'IMP000169', 'Abdullah Azam', NULL, 47, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (412, '53', 'Attiq Anjum', NULL, 47, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (413, '290', 'Kalsoom Bibi', NULL, 47, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (414, '341', 'Tayyaba Aziz', NULL, 47, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (415, '467', 'Aliha Tariq', NULL, 47, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (416, '239', 'Afia Bibi', NULL, 47, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (417, '287', 'Maryam Bibi', NULL, 47, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (418, '323', 'Noor ul Ain Shahzad', NULL, 47, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (419, '246', 'Ayesha Rafiq', NULL, 47, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (420, '272', 'Irum Matloob', NULL, 47, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (421, 'IMP000179', 'Eisha Arshad', NULL, 47, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (422, 'IMP000180', 'Awal Khan', NULL, 47, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (423, 'IMP000181', 'Aqib Tariq', NULL, 47, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (424, '1', 'Aqeel khadim', NULL, 47, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (425, 'IMP000183', 'Moin Imtiaz', NULL, 48, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (426, '227', 'Sheheryar', NULL, 48, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (427, '424', 'Muhammad Ali', NULL, 48, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (428, '466', 'Shaista Mehmood', NULL, 48, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (429, '251', 'Aliha Saeed', NULL, 48, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (430, 'IMP000188', 'Mahnoor Zahoor', NULL, 48, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (431, '464', 'Zaineb Ashraf', NULL, 48, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (432, 'IMP000190', 'Zoya bibi', NULL, 48, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (433, '491', 'Usama Tariq', NULL, 48, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (434, '192', 'Saim Khalid', NULL, 48, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (435, 'IMP000193', 'Arsalan Mohsin', NULL, 48, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (436, '408', 'Shaheen Bibi', NULL, 48, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (437, 'IMP000195', 'Eisha Tariq', NULL, 48, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (438, 'IMP000196', 'Maheen Fatima', NULL, 48, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (439, 'IMP000197', 'Zoya Fatima(d.s)', NULL, 48, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (440, 'IMP000198', 'Muhammad Ubaid Raza', NULL, 49, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (441, 'IMP000199', 'Ahmed talal', NULL, 49, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (442, 'IMP000200', 'Shoaib Maroof', NULL, 49, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (443, '57', 'Jawad Ahmed', NULL, 49, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (444, '15', 'Abdullah Tariq', NULL, 49, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (445, '204', 'Sohrab Ahmed', NULL, 49, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (446, 'IMP000204', 'Wasif Sharif', NULL, 49, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (447, 'IMP000205', 'Nasir Nazir', NULL, 49, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (448, '26', 'Anees Anjum', NULL, 49, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (449, '2', 'Atif Manzoor', NULL, 49, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (450, 'IMP000208', 'Maira Marooof', NULL, 49, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (451, 'IMP000209', 'Eman Fatima', NULL, 49, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (452, 'IMP000210', 'Amna Shoukat', NULL, 49, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (453, '379', 'Hoor fatima', NULL, 49, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (454, 'IMP000212', 'Neelo Zaman', NULL, 49, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (455, 'IMP000213', 'Rubaisha Nadeem', NULL, 49, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (456, '373', 'Tayyaba Waheed', NULL, 49, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (457, '28', 'Afzal Ahmed', NULL, 50, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (458, '132', 'Zaheer Ahmed', NULL, 50, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (459, 'IMP000217', 'M.Wasif Manzoor', NULL, 50, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (460, 'IMP000218', 'Umair Shabbir', NULL, 50, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (461, '205', 'Sadaqat Ahmed', NULL, 50, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (462, 'IMP000220', 'Junaid Yaseen', NULL, 50, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (463, '128', 'Sohail Irfan', NULL, 50, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (464, 'IMP000222', 'Sheriyar Shehbaz', NULL, 50, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (465, 'IMP000223', 'Ali Zafer', NULL, 50, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (466, 'IMP000224', 'Bahlol Shah', NULL, 50, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (467, '274', 'Haleema Manzoor', NULL, 50, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (468, 'IMP000226', 'Maria Nazir', NULL, 50, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (469, '297', 'Malaika Murtaza', NULL, 50, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (470, 'IMP000228', 'Nazia Zamaan', NULL, 50, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (471, 'IMP000229', 'Maiza Akhter', NULL, 50, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (472, 'IMP000230', 'Sohail Shoukat', NULL, 50, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (473, 'IMP000231', 'Bilal Siddique', NULL, 50, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (474, '55', 'Karamat Hussain', NULL, 51, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (475, '142', 'Moiz Ali Akhtar Imran', NULL, 51, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (476, 'IMP000234', 'Muhammad Hassan', NULL, 51, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (477, '151', 'Yasir Shoukat', NULL, 51, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (478, '195', 'Ubaid Majid', NULL, 51, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (479, '169', 'Muhammad Mateen', NULL, 51, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (480, '211', 'Shaheer Ahmed', NULL, 51, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (481, '254', 'Ehsan Nasar Ullah', NULL, 51, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (482, '21', 'Arslan Shabir', NULL, 51, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (483, 'IMP000241', 'Muhammad Suffian', NULL, 51, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (484, '113', 'Muhammad Faizan Ali', NULL, 51, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (485, '92', 'Hashim Jarral', NULL, 51, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (486, '345', 'Tammana Sajid', NULL, 51, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (487, '359', 'Zainab', NULL, 51, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (488, '376', 'Halima Bibi', NULL, 51, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (489, '413', 'Muqadas Shahzad', NULL, 51, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (490, '260', 'Eman Fatima', NULL, 51, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (491, '364', 'Rukhmah Shahzadi Niyamat', NULL, 51, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (492, '380', 'Fatima', NULL, 51, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (493, '365', 'Raiqa Khalid', NULL, 51, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (494, '43', 'Danish Ali', NULL, 51, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (495, 'IMP000253', 'Sabeel Khan (D.S)', NULL, 51, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (496, 'IMP000254', 'Ume Aiman (D.S)', NULL, 51, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (497, 'IMP000255', 'Bilal Ghulam Nabi', NULL, 51, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (498, '245', 'Ayesha Abdul Khaliq', NULL, 52, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (499, 'IMP000257', 'Misbah Khani', NULL, 52, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (500, 'IMP000258', 'Hajara bibi', NULL, 52, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (501, 'IMP000259', 'shahzadi javed', NULL, 52, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (502, 'IMP000260', 'sherish ramzan', NULL, 52, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (503, '326', 'Nimra bibi', NULL, 52, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (504, '342', 'Tabeer fareed', NULL, 52, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (505, 'IMP000263', 'Mahreen hashim', NULL, 52, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (506, 'IMP000264', 'Alina jahangir', NULL, 52, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (507, 'IMP000265', 'Sadia Majeed', NULL, 52, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (508, '196', 'Umer ahmed awan', NULL, 52, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (509, '171', 'Muhammad asif', NULL, 52, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (510, '202', 'Shakeel hassan', NULL, 52, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (511, '106', 'Muhammad saqib', NULL, 52, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (512, '186', 'Umair Majid', NULL, 52, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (513, 'IMP000271', 'jameel samandar', NULL, 52, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (514, 'IMP000272', 'gull nawaz', NULL, 52, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (515, 'IMP000273', 'bilal yousf', NULL, 52, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (516, 'IMP000274', 'muhammad Khuzama', NULL, 52, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (517, 'IMP000275', 'Syed raheel Abbas kazami', NULL, 52, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (518, 'IMP000276', 'Baber zulfiqar', NULL, 52, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (519, '24', 'aliyan khurshid', NULL, 52, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (520, 'IMP000278', 'safeer aslam', NULL, 52, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (521, '88', 'jasim shoukat', NULL, 52, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (522, 'IMP000280', 'Raja Umer', NULL, 53, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (523, '213', 'Sohail Ahmed', NULL, 53, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (524, 'IMP000282', 'Shahid Ali', NULL, 53, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (525, '23', 'Asif Akbar', NULL, 53, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (526, 'IMP000284', 'Nabeel Rafiq', NULL, 53, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (527, '39', 'Gulfam Zahid', NULL, 53, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (528, 'IMP000286', 'Hamza Khail', NULL, 53, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (529, 'IMP000287', 'Arman Salman', NULL, 53, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (530, 'IMP000288', 'Ahsan Naeir', NULL, 53, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (531, '37', 'Faheem Zafran', NULL, 53, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (532, 'IMP000290', 'M. Younas', NULL, 53, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (533, 'IMP000291', 'Haider Khurshid', NULL, 53, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (534, 'IMP000292', 'Wasiq Shaheed', NULL, 53, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (535, 'IMP000293', 'M. Musa', NULL, 53, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (536, '93', 'Husnain Majeed', NULL, 53, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (537, 'IMP000295', 'Siraj Tariq', NULL, 53, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (538, '414', 'Munaza Majeed', NULL, 53, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (539, '264', 'Iqra Manzoor', NULL, 53, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (540, 'IMP000298', 'Zara Nasir', NULL, 53, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (541, '250', 'Areesha Saeed', NULL, 53, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (542, 'IMP000300', 'Hijab Zara', NULL, 53, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (543, '393', 'Sania Zahid', NULL, 53, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (544, 'IMP000302', 'Pakeeza Ismail', NULL, 53, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (545, '308', 'Mahnoor Waheed', NULL, 53, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (546, 'IMP000304', 'Khiza Khalil', NULL, 53, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (547, 'IMP000305', 'Haleema Sadia', NULL, 53, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (548, '280', 'Ayesha Saddique', NULL, 38, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (549, 'IMP000307', 'Aqse Bibi', NULL, 38, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (550, '248', 'Ayesha Fatima', NULL, 38, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (551, '339', 'Noor bano', NULL, 38, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (552, 'IMP000310', 'Eman Tariq', NULL, 38, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (553, 'IMP000311', 'Laiba Beshir', NULL, 38, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (554, '337', 'Noor Akhtar', NULL, 38, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (555, 'IMP000313', 'Khalida Hameed', NULL, 38, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (556, 'IMP000314', 'Farzane Farooq', NULL, 38, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (557, '263', 'Abeera Maroof', NULL, 38, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (558, 'IMP000316', 'Sehar Shahjahan', NULL, 38, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (559, 'IMP000317', 'Syeda Sunadas', NULL, 38, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (560, 'IMP000318', 'Jzba Naseem', NULL, 38, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (561, '430', 'Sikandar Aftab', NULL, 38, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (562, '5', 'Affan Khurshid', NULL, 38, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (563, 'IMP000321', 'Mazhar-ul-Haq', NULL, 38, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (564, 'IMP000322', 'Sufiyan Bashir', NULL, 38, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (565, '135', 'Zeeshan Waheed', NULL, 38, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (566, '97', 'Muhammad Ali', NULL, 38, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (567, 'IMP000325', 'Waleed Chugtai', NULL, 38, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (568, '54', 'Abdul Manan', NULL, 38, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (569, 'IMP000327', 'Hafeez Aziz', NULL, 38, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (570, 'IMP000328', 'Tayyab Shehzad', NULL, 38, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (571, 'IMP000329', 'Farhan Shahjahan', NULL, 38, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (572, '276', 'Esha Mehdi', NULL, 39, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (573, '315', 'Misbah Rani', NULL, 39, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (574, '418', 'Sehrish Razzaq', NULL, 39, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (575, 'IMP000333', 'Nadia Akram', NULL, 39, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (576, '291', 'Shazia Bibi', NULL, 39, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (577, 'IMP000335', 'Saba Shabbir', NULL, 39, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (578, 'IMP000336', 'Iqra Saddique', NULL, 39, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (579, '329', 'Momina Gulshan', NULL, 39, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (580, '366', 'Rashida Bibi', NULL, 39, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (581, '4', 'Abdul Rehman', NULL, 39, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (582, '182', 'Qaiser Qasim', NULL, 39, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (583, 'IMP000341', 'Abdl Rauf', NULL, 39, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (584, '145', 'Zaib Zahoor', NULL, 39, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (585, '129', 'Safeer Ahmed', NULL, 40, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (586, 'IMP000344', 'Sadeem Ahmed', NULL, 40, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (587, '133', 'Zubair Ahmed', NULL, 40, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (588, 'IMP000346', 'AhsanNaseem', NULL, 40, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (589, 'IMP000347', 'Mansoor Majid', NULL, 40, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (590, 'IMP000348', 'Tayyab tariq', NULL, 40, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (591, 'IMP000349', 'M.sadeer', NULL, 40, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (592, 'IMP000350', 'Raja Haseeb', NULL, 40, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (593, 'IMP000351', 'Muhammad Hasseeb', NULL, 40, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (594, 'IMP000352', 'Mubashir bashir', NULL, 40, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (595, '10', 'Awais khan', NULL, 40, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (596, 'IMP000354', 'Abdul Sami', NULL, 40, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (597, 'IMP000355', 'Kiran Shahzadi', NULL, 40, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (598, 'IMP000356', 'Zahira Ali khan', NULL, 40, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (599, '317', 'Mehwish Ramzan', NULL, 40, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (600, '421', 'Sehrish Gulshan', NULL, 40, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (601, 'IMP000359', 'Amina Khaliq', NULL, 40, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (602, 'IMP000360', 'Subhana zara', NULL, 40, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (603, 'IMP000361', 'Sakina Kazmi', NULL, 40, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (604, 'IMP000362', 'Nirma Bibi', NULL, 40, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (605, 'IMP000363', 'Nageen Fatima', NULL, 40, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (606, 'IMP000364', 'Shakeela Bibi', NULL, 40, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (607, 'IMP000365', 'Muneeb Satti', NULL, 52, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (608, 'IMP000366', 'Kousar Bibi', NULL, 51, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (609, '73', 'Hassan Majeed', NULL, 38, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (610, 'IMP000368', 'zeeshan', NULL, 44, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (611, 'IMP000369', 'Anas', NULL, 43, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (612, 'IMP000370', 'Sayam', NULL, 43, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (613, 'IMP000371', 'Ishtiaq', NULL, 18, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (614, 'KORT549', 'Eman Fatima', NULL, 43, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-26 12:10:13', NULL, NULL, NULL);
INSERT INTO `students` (`id`, `student_id`, `name`, `father_name`, `class_id`, `date_of_birth`, `age`, `contact`, `address`, `status`, `created_at`, `updated_at`, `deleted_at`, `photo_path`, `qr_token`) VALUES (615, 'IMP000373', 'behlol', NULL, 50, NULL, NULL, NULL, NULL, 'active', '2026-03-25 21:22:03', '2026-03-25 21:22:03', NULL, NULL, NULL);

DROP TABLE IF EXISTS `subject_group_subject`;
CREATE TABLE `subject_group_subject` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `subject_group_id` BIGINT NOT NULL,
  `subject_id` BIGINT NOT NULL,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subject_group_subject_unique` (`subject_group_id`, `subject_id`),
  CONSTRAINT `fk_subject_group_subject_subject_id_subjects_id` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
  CONSTRAINT `fk_subject_group_subject_subject_group_id_subject_groups_id` FOREIGN KEY (`subject_group_id`) REFERENCES `subject_groups` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `subject_groups`;
CREATE TABLE `subject_groups` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `session` TEXT NOT NULL,
  `class_id` BIGINT NOT NULL,
  `name` TEXT NOT NULL,
  `description` TEXT,
  `is_active` BIGINT NOT NULL DEFAULT '1',
  `created_by` BIGINT,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  KEY `subject_groups_session_class_active_index` (`session`, `class_id`, `is_active`),
  UNIQUE KEY `subject_groups_session_class_name_unique` (`session`, `class_id`, `name`),
  CONSTRAINT `fk_subject_groups_created_by_users_id` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE NO ACTION ON DELETE SET NULL,
  CONSTRAINT `fk_subject_groups_class_id_school_classes_id` FOREIGN KEY (`class_id`) REFERENCES `school_classes` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `subject_period_rules`;
CREATE TABLE `subject_period_rules` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `session` TEXT NOT NULL,
  `class_section_id` BIGINT NOT NULL,
  `subject_id` BIGINT NOT NULL,
  `periods_per_week` BIGINT NOT NULL,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  KEY `subject_period_rules_session_class_section_id_index` (`session`, `class_section_id`),
  UNIQUE KEY `subject_period_rules_unique` (`session`, `class_section_id`, `subject_id`),
  CONSTRAINT `fk_subject_period_rules_subject_id_subjects_id` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
  CONSTRAINT `fk_subject_period_rules_class_section_id_class_sections_id` FOREIGN KEY (`class_section_id`) REFERENCES `class_sections` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (1, '2026-2027', 2, 18, 2, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (2, '2026-2027', 2, 21, 6, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (3, '2026-2027', 2, 15, 5, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (4, '2026-2027', 2, 1, 5, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (5, '2026-2027', 2, 19, 1, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (6, '2026-2027', 2, 25, 5, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (7, '2026-2027', 2, 14, 1, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (8, '2026-2027', 2, 2, 5, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (9, '2026-2027', 2, 26, 2, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (10, '2026-2027', 2, 28, 5, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (11, '2026-2027', 2, 3, 1, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (12, '2026-2027', 2, 4, 6, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (13, '2026-2027', 3, 8, 6, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (14, '2026-2027', 4, 6, 1, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (15, '2026-2027', 4, 21, 6, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (16, '2026-2027', 5, 15, 6, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (17, '2026-2027', 4, 1, 6, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (18, '2026-2027', 4, 25, 5, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (19, '2026-2027', 4, 2, 1, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (20, '2026-2027', 4, 27, 4, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (21, '2026-2027', 4, 7, 1, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (22, '2026-2027', 4, 3, 6, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (23, '2026-2027', 6, 8, 4, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (24, '2026-2027', 7, 6, 2, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (25, '2026-2027', 8, 21, 6, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (26, '2026-2027', 9, 15, 2, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (27, '2026-2027', 8, 1, 2, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (28, '2026-2027', 8, 25, 4, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (29, '2026-2027', 8, 14, 1, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (30, '2026-2027', 10, 2, 4, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (31, '2026-2027', 8, 26, 1, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (32, '2026-2027', 8, 7, 2, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (33, '2026-2027', 8, 29, 2, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (34, '2026-2027', 8, 3, 2, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (35, '2026-2027', 11, 8, 4, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (36, '2026-2027', 12, 6, 2, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (37, '2026-2027', 13, 21, 6, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (38, '2026-2027', 14, 15, 2, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (39, '2026-2027', 13, 1, 2, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (40, '2026-2027', 13, 14, 1, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (41, '2026-2027', 15, 2, 4, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (42, '2026-2027', 13, 26, 1, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (43, '2026-2027', 13, 27, 6, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (44, '2026-2027', 13, 7, 2, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (45, '2026-2027', 13, 3, 2, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (46, '2026-2027', 16, 18, 2, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (47, '2026-2027', 16, 21, 6, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (48, '2026-2027', 16, 15, 5, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (49, '2026-2027', 16, 1, 1, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (50, '2026-2027', 16, 19, 1, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (51, '2026-2027', 16, 25, 5, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (52, '2026-2027', 16, 14, 1, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (53, '2026-2027', 16, 2, 1, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (54, '2026-2027', 16, 26, 2, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (55, '2026-2027', 16, 28, 5, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (56, '2026-2027', 16, 3, 1, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (57, '2026-2027', 16, 4, 6, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (58, '2026-2027', 17, 20, 2, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (59, '2026-2027', 17, 21, 6, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (60, '2026-2027', 17, 15, 5, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (61, '2026-2027', 17, 1, 1, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (62, '2026-2027', 17, 19, 1, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (63, '2026-2027', 17, 25, 5, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (64, '2026-2027', 17, 14, 1, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (65, '2026-2027', 17, 2, 1, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (66, '2026-2027', 17, 26, 2, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (67, '2026-2027', 17, 28, 5, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (68, '2026-2027', 17, 3, 1, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (69, '2026-2027', 17, 4, 6, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (70, '2026-2027', 18, 20, 2, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (71, '2026-2027', 18, 21, 6, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (72, '2026-2027', 18, 15, 5, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (73, '2026-2027', 18, 1, 1, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (74, '2026-2027', 18, 19, 1, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (75, '2026-2027', 18, 25, 5, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (76, '2026-2027', 18, 14, 1, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (77, '2026-2027', 18, 2, 1, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (78, '2026-2027', 18, 26, 2, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (79, '2026-2027', 18, 28, 5, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (80, '2026-2027', 18, 3, 1, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (81, '2026-2027', 18, 4, 6, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (82, '2026-2027', 19, 20, 2, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (83, '2026-2027', 19, 21, 6, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (84, '2026-2027', 19, 15, 5, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (85, '2026-2027', 19, 1, 1, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (86, '2026-2027', 19, 19, 1, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (87, '2026-2027', 19, 25, 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (88, '2026-2027', 19, 14, 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (89, '2026-2027', 19, 2, 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (90, '2026-2027', 19, 26, 2, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (91, '2026-2027', 19, 28, 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (92, '2026-2027', 19, 3, 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (93, '2026-2027', 19, 4, 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (94, '2026-2027', 20, 20, 2, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (95, '2026-2027', 20, 21, 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (96, '2026-2027', 20, 15, 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (97, '2026-2027', 20, 1, 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (98, '2026-2027', 20, 19, 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (99, '2026-2027', 20, 25, 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (100, '2026-2027', 20, 14, 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (101, '2026-2027', 20, 2, 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (102, '2026-2027', 20, 26, 2, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (103, '2026-2027', 20, 28, 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (104, '2026-2027', 20, 3, 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (105, '2026-2027', 20, 4, 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (106, '2026-2027', 21, 20, 2, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (107, '2026-2027', 21, 21, 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (108, '2026-2027', 21, 15, 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (109, '2026-2027', 21, 1, 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (110, '2026-2027', 21, 19, 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (111, '2026-2027', 21, 25, 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (112, '2026-2027', 21, 14, 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (113, '2026-2027', 21, 2, 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (114, '2026-2027', 21, 26, 2, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (115, '2026-2027', 21, 28, 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (116, '2026-2027', 21, 3, 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (117, '2026-2027', 21, 4, 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (118, '2026-2027', 22, 20, 2, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (119, '2026-2027', 22, 21, 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (120, '2026-2027', 22, 15, 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (121, '2026-2027', 22, 1, 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (122, '2026-2027', 22, 22, 4, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (123, '2026-2027', 22, 24, 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (124, '2026-2027', 22, 25, 4, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (125, '2026-2027', 22, 2, 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (126, '2026-2027', 22, 26, 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (127, '2026-2027', 22, 3, 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (128, '2026-2027', 22, 4, 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (129, '2026-2027', 23, 20, 2, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (130, '2026-2027', 23, 21, 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (131, '2026-2027', 23, 15, 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (132, '2026-2027', 23, 1, 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (133, '2026-2027', 23, 22, 4, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (134, '2026-2027', 23, 24, 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (135, '2026-2027', 23, 25, 4, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (136, '2026-2027', 23, 2, 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (137, '2026-2027', 23, 26, 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (138, '2026-2027', 23, 3, 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (139, '2026-2027', 23, 4, 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (140, '2026-2027', 24, 21, 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (141, '2026-2027', 24, 15, 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (142, '2026-2027', 24, 1, 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (143, '2026-2027', 24, 22, 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (144, '2026-2027', 24, 24, 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (145, '2026-2027', 24, 25, 4, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (146, '2026-2027', 24, 2, 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (147, '2026-2027', 24, 26, 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (148, '2026-2027', 24, 3, 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (149, '2026-2027', 24, 4, 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (150, '2026-2027', 25, 21, 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (151, '2026-2027', 25, 15, 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (152, '2026-2027', 25, 1, 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (153, '2026-2027', 25, 22, 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (154, '2026-2027', 25, 24, 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (155, '2026-2027', 25, 25, 4, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (156, '2026-2027', 25, 2, 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (157, '2026-2027', 25, 26, 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (158, '2026-2027', 25, 3, 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (159, '2026-2027', 25, 4, 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (160, '2026-2027', 26, 21, 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (161, '2026-2027', 26, 15, 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (162, '2026-2027', 26, 1, 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (163, '2026-2027', 26, 24, 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (164, '2026-2027', 26, 25, 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (165, '2026-2027', 26, 14, 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (166, '2026-2027', 26, 2, 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (167, '2026-2027', 26, 26, 2, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (168, '2026-2027', 26, 3, 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (169, '2026-2027', 26, 4, 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (170, '2026-2027', 27, 8, 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (171, '2026-2027', 28, 6, 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (172, '2026-2027', 28, 21, 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (173, '2026-2027', 29, 15, 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (174, '2026-2027', 28, 1, 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (175, '2026-2027', 28, 25, 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (176, '2026-2027', 28, 14, 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (177, '2026-2027', 28, 2, 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (178, '2026-2027', 28, 26, 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (179, '2026-2027', 28, 7, 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (180, '2026-2027', 28, 3, 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (181, '2026-2027', 30, 8, 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (182, '2026-2027', 31, 6, 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (183, '2026-2027', 31, 21, 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (184, '2026-2027', 32, 15, 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (185, '2026-2027', 31, 1, 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (186, '2026-2027', 31, 25, 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (187, '2026-2027', 31, 14, 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (188, '2026-2027', 31, 2, 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (189, '2026-2027', 31, 26, 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (190, '2026-2027', 31, 7, 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (191, '2026-2027', 31, 3, 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (192, '2026-2027', 33, 18, 2, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (193, '2026-2027', 33, 21, 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (194, '2026-2027', 33, 1, 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (195, '2026-2027', 33, 19, 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (196, '2026-2027', 33, 25, 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (197, '2026-2027', 33, 2, 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (198, '2026-2027', 33, 26, 2, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (199, '2026-2027', 33, 28, 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (200, '2026-2027', 33, 3, 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (201, '2026-2027', 34, 18, 2, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (202, '2026-2027', 34, 21, 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (203, '2026-2027', 34, 1, 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (204, '2026-2027', 34, 19, 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (205, '2026-2027', 34, 25, 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (206, '2026-2027', 34, 2, 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (207, '2026-2027', 34, 26, 2, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (208, '2026-2027', 34, 28, 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (209, '2026-2027', 34, 3, 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (210, '2026-2027', 35, 18, 2, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (211, '2026-2027', 35, 21, 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (212, '2026-2027', 35, 1, 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (213, '2026-2027', 35, 19, 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (214, '2026-2027', 35, 25, 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (215, '2026-2027', 35, 2, 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (216, '2026-2027', 35, 26, 2, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (217, '2026-2027', 35, 28, 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `subject_period_rules` (`id`, `session`, `class_section_id`, `subject_id`, `periods_per_week`, `created_at`, `updated_at`) VALUES (218, '2026-2027', 35, 3, 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');

DROP TABLE IF EXISTS `subjects`;
CREATE TABLE `subjects` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` TEXT NOT NULL,
  `code` TEXT,
  `status` TEXT NOT NULL DEFAULT 'active',
  `created_at` DATETIME,
  `updated_at` DATETIME,
  `is_default` BIGINT NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `subjects_is_default_name_index` (`is_default`, `name`),
  KEY `subjects_code_index` (`code`),
  KEY `subjects_name_index` (`name`),
  KEY `subjects_is_default_index` (`is_default`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `subjects` (`id`, `name`, `code`, `status`, `created_at`, `updated_at`, `is_default`) VALUES (1, 'English', 'Eng', 'active', '2026-03-25 21:31:41', '2026-03-25 21:31:41', 0);
INSERT INTO `subjects` (`id`, `name`, `code`, `status`, `created_at`, `updated_at`, `is_default`) VALUES (2, 'Math', 'Math', 'active', '2026-03-25 21:31:41', '2026-03-25 21:31:41', 0);
INSERT INTO `subjects` (`id`, `name`, `code`, `status`, `created_at`, `updated_at`, `is_default`) VALUES (3, 'Urdu', 'urdu', 'active', '2026-03-25 21:31:41', '2026-03-25 21:31:41', 0);
INSERT INTO `subjects` (`id`, `name`, `code`, `status`, `created_at`, `updated_at`, `is_default`) VALUES (4, 'Science', 'science', 'active', '2026-03-25 21:31:41', '2026-03-25 21:31:41', 0);
INSERT INTO `subjects` (`id`, `name`, `code`, `status`, `created_at`, `updated_at`, `is_default`) VALUES (5, 'Islamiat', 'isl', 'active', '2026-03-25 21:31:41', '2026-03-25 21:31:41', 0);
INSERT INTO `subjects` (`id`, `name`, `code`, `status`, `created_at`, `updated_at`, `is_default`) VALUES (6, 'Chemistry', 'chem', 'active', '2026-03-25 21:31:41', '2026-03-25 21:31:41', 0);
INSERT INTO `subjects` (`id`, `name`, `code`, `status`, `created_at`, `updated_at`, `is_default`) VALUES (7, 'Physics', 'phy', 'active', '2026-03-25 21:31:41', '2026-03-25 21:31:41', 0);
INSERT INTO `subjects` (`id`, `name`, `code`, `status`, `created_at`, `updated_at`, `is_default`) VALUES (8, 'Biology', 'bio', 'active', '2026-03-25 21:31:41', '2026-03-25 21:31:41', 0);
INSERT INTO `subjects` (`id`, `name`, `code`, `status`, `created_at`, `updated_at`, `is_default`) VALUES (9, 'General Knowledge', 'Gk', 'active', '2026-03-25 21:31:41', '2026-03-25 21:31:41', 0);
INSERT INTO `subjects` (`id`, `name`, `code`, `status`, `created_at`, `updated_at`, `is_default`) VALUES (10, 'F/Q', 'F/Q', 'active', '2026-03-25 21:31:41', '2026-03-25 21:31:41', 0);
INSERT INTO `subjects` (`id`, `name`, `code`, `status`, `created_at`, `updated_at`, `is_default`) VALUES (11, 'H/G', 'H/G', 'active', '2026-03-25 21:31:41', '2026-03-25 21:31:41', 0);
INSERT INTO `subjects` (`id`, `name`, `code`, `status`, `created_at`, `updated_at`, `is_default`) VALUES (12, 'Pakistan Studies', 'p.k', 'active', '2026-03-25 21:31:41', '2026-03-25 21:31:41', 0);
INSERT INTO `subjects` (`id`, `name`, `code`, `status`, `created_at`, `updated_at`, `is_default`) VALUES (13, 'P.e', 'P.E', 'active', '2026-03-25 21:31:41', '2026-03-25 21:31:41', 0);
INSERT INTO `subjects` (`id`, `name`, `code`, `status`, `created_at`, `updated_at`, `is_default`) VALUES (14, 'Library', 'Library', 'active', '2026-03-25 21:31:41', '2026-03-25 21:31:41', 0);
INSERT INTO `subjects` (`id`, `name`, `code`, `status`, `created_at`, `updated_at`, `is_default`) VALUES (15, 'Computer', 'Computer', 'active', '2026-03-25 21:31:41', '2026-03-25 21:31:41', 0);
INSERT INTO `subjects` (`id`, `name`, `code`, `status`, `created_at`, `updated_at`, `is_default`) VALUES (16, 'T/Q', 'T/Q', 'active', '2026-03-25 21:31:41', '2026-03-25 21:31:41', 0);
INSERT INTO `subjects` (`id`, `name`, `code`, `status`, `created_at`, `updated_at`, `is_default`) VALUES (17, 'S.S.T', 'S.S.T', 'active', '2026-03-25 21:31:41', '2026-03-25 21:31:41', 0);
INSERT INTO `subjects` (`id`, `name`, `code`, `status`, `created_at`, `updated_at`, `is_default`) VALUES (18, 'Art', 'Art', 'active', '2026-03-25 21:31:41', '2026-03-25 21:31:41', 0);
INSERT INTO `subjects` (`id`, `name`, `code`, `status`, `created_at`, `updated_at`, `is_default`) VALUES (19, 'English Comm', 'English Comm', 'active', '2026-03-25 21:31:41', '2026-03-25 21:31:41', 0);
INSERT INTO `subjects` (`id`, `name`, `code`, `status`, `created_at`, `updated_at`, `is_default`) VALUES (20, 'Arabic', 'Arabic', 'active', '2026-03-25 21:31:41', '2026-03-25 21:31:41', 0);
INSERT INTO `subjects` (`id`, `name`, `code`, `status`, `created_at`, `updated_at`, `is_default`) VALUES (21, 'Circle Time', 'Circle Time', 'active', '2026-03-25 21:31:41', '2026-03-25 21:31:41', 0);
INSERT INTO `subjects` (`id`, `name`, `code`, `status`, `created_at`, `updated_at`, `is_default`) VALUES (22, 'F-Q', 'F/Q', 'active', '2026-03-26 12:19:23', '2026-03-26 12:19:23', 0);
INSERT INTO `subjects` (`id`, `name`, `code`, `status`, `created_at`, `updated_at`, `is_default`) VALUES (23, 'GK', 'G.K', 'active', '2026-03-26 12:19:23', '2026-03-26 12:19:23', 0);
INSERT INTO `subjects` (`id`, `name`, `code`, `status`, `created_at`, `updated_at`, `is_default`) VALUES (24, 'H-G', 'H/G', 'active', '2026-03-26 12:19:23', '2026-03-26 12:19:23', 0);
INSERT INTO `subjects` (`id`, `name`, `code`, `status`, `created_at`, `updated_at`, `is_default`) VALUES (25, 'Islamiyat', 'isl', 'active', '2026-03-26 12:19:23', '2026-03-26 12:19:23', 0);
INSERT INTO `subjects` (`id`, `name`, `code`, `status`, `created_at`, `updated_at`, `is_default`) VALUES (26, 'PE', 'P.E', 'active', '2026-03-26 12:19:23', '2026-03-26 12:19:23', 0);
INSERT INTO `subjects` (`id`, `name`, `code`, `status`, `created_at`, `updated_at`, `is_default`) VALUES (27, 'Pak Study', 'p.k', 'active', '2026-03-26 12:19:23', '2026-03-26 12:19:23', 0);
INSERT INTO `subjects` (`id`, `name`, `code`, `status`, `created_at`, `updated_at`, `is_default`) VALUES (28, 'SST', 'S.S.T', 'active', '2026-03-26 12:19:23', '2026-03-26 12:19:23', 0);
INSERT INTO `subjects` (`id`, `name`, `code`, `status`, `created_at`, `updated_at`, `is_default`) VALUES (29, 'T-Q', 'T/Q', 'active', '2026-03-26 12:19:23', '2026-03-26 12:19:23', 0);

DROP TABLE IF EXISTS `teacher_assignments`;
CREATE TABLE `teacher_assignments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `teacher_id` BIGINT NOT NULL,
  `class_id` BIGINT NOT NULL,
  `subject_id` BIGINT,
  `is_class_teacher` BIGINT NOT NULL DEFAULT '0',
  `session` TEXT NOT NULL,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  KEY `teacher_assignments_session_subject_index` (`session`, `subject_id`),
  KEY `teacher_assignments_session_class_teacher_index` (`session`, `is_class_teacher`, `class_id`),
  KEY `teacher_assignments_class_id_session_index` (`class_id`, `session`),
  KEY `teacher_assignments_teacher_id_session_index` (`teacher_id`, `session`),
  CONSTRAINT `fk_teacher_assignments_subject_id_subjects_id` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON UPDATE NO ACTION ON DELETE SET NULL,
  CONSTRAINT `fk_teacher_assignments_class_id_school_classes_id` FOREIGN KEY (`class_id`) REFERENCES `school_classes` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
  CONSTRAINT `fk_teacher_assignments_teacher_id_teachers_id` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (1, 33, 54, NULL, 1, '2025-2026', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (2, 1, 17, NULL, 1, '2025-2026', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (3, 34, 18, NULL, 1, '2025-2026', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (4, 26, 37, NULL, 1, '2025-2026', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (5, 29, 41, NULL, 1, '2025-2026', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (6, 23, 42, NULL, 1, '2025-2026', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (7, 22, 44, NULL, 1, '2025-2026', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (8, 27, 45, NULL, 1, '2025-2026', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (9, 5, 46, NULL, 1, '2025-2026', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (10, 21, 47, NULL, 1, '2025-2026', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (11, 9, 49, NULL, 1, '2025-2026', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (12, 19, 50, NULL, 1, '2025-2026', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (13, 7, 52, NULL, 1, '2025-2026', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (14, 12, 38, NULL, 1, '2025-2026', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (15, 6, 39, NULL, 1, '2025-2026', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (16, 20, 51, NULL, 1, '2025-2026', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (17, 30, 40, NULL, 1, '2025-2026', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (18, 13, 38, NULL, 1, '2025-2026', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (19, 28, 51, NULL, 1, '2025-2026', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (20, 33, 54, 2, 0, '2025-2026', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (21, 1, 54, 1, 0, '2025-2026', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (22, 26, 37, NULL, 1, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (23, 7, 38, NULL, 1, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (24, 30, 39, NULL, 1, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (25, 6, 40, NULL, 1, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (26, 29, 41, NULL, 1, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (27, 23, 42, NULL, 1, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (28, 25, 43, NULL, 1, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (29, 22, 44, NULL, 1, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (30, 27, 45, NULL, 1, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (31, 15, 46, NULL, 1, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (32, 21, 47, NULL, 1, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (33, 14, 48, NULL, 1, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (34, 9, 49, NULL, 1, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (35, 19, 50, NULL, 1, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (36, 20, 51, NULL, 1, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (37, 36, 52, NULL, 1, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (38, 13, 53, NULL, 1, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (39, 1, 17, NULL, 1, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (40, 38, 54, NULL, 1, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (41, 28, 18, NULL, 1, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (42, 34, 37, 18, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (43, 26, 37, 21, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (44, 23, 37, 15, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (45, 1, 37, 1, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (46, 24, 37, 19, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (47, 16, 37, 25, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (48, 18, 37, 14, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (49, 38, 37, 2, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (50, 3, 37, 26, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (51, 29, 37, 28, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (52, 26, 37, 3, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (53, 27, 37, 4, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (54, 15, 38, 8, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (55, 7, 38, 6, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (56, 7, 38, 21, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (57, 32, 38, 15, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (58, 30, 38, 1, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (59, 10, 38, 25, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (60, 13, 38, 2, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (61, 8, 38, 27, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (62, 36, 38, 7, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (63, 6, 38, 3, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (64, 21, 39, 8, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (65, 7, 39, 6, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (66, 30, 39, 21, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (67, 32, 39, 15, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (68, 30, 39, 1, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (69, 10, 39, 25, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (70, 18, 39, 14, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (71, 13, 39, 2, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (72, 37, 39, 26, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (73, 36, 39, 7, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (74, 10, 39, 29, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (75, 6, 39, 3, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (76, 21, 40, 8, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (77, 7, 40, 6, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (78, 6, 40, 21, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (79, 32, 40, 15, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (80, 30, 40, 1, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (81, 18, 40, 14, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (82, 13, 40, 2, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (83, 37, 40, 26, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (84, 8, 40, 27, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (85, 36, 40, 7, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (86, 6, 40, 3, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (87, 34, 41, 18, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (88, 29, 41, 21, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (89, 23, 41, 15, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (90, 24, 41, 1, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (91, 24, 41, 19, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (92, 26, 41, 25, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (93, 18, 41, 14, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (94, 38, 41, 2, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (95, 3, 41, 26, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (96, 29, 41, 28, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (97, 26, 41, 3, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (98, 27, 41, 4, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (99, 4, 42, 20, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (100, 23, 42, 21, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (101, 23, 42, 15, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (102, 25, 42, 1, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (103, 24, 42, 19, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (104, 16, 42, 25, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (105, 18, 42, 14, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (106, 22, 42, 2, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (107, 3, 42, 26, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (108, 8, 42, 28, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (109, 26, 42, 3, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (110, 27, 42, 4, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (111, 4, 43, 20, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (112, 25, 43, 21, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (113, 23, 43, 15, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (114, 25, 43, 1, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (115, 24, 43, 19, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (116, 16, 43, 25, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (117, 18, 43, 14, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (118, 22, 43, 2, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (119, 3, 43, 26, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (120, 8, 43, 28, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (121, 26, 43, 3, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (122, 27, 43, 4, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (123, 4, 44, 20, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (124, 22, 44, 21, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (125, 23, 44, 15, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (126, 25, 44, 1, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (127, 24, 44, 19, 0, '2026-2027', '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (128, 17, 44, 25, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (129, 18, 44, 14, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (130, 22, 44, 2, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (131, 37, 44, 26, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (132, 8, 44, 28, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (133, 28, 44, 3, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (134, 27, 44, 4, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (135, 4, 45, 20, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (136, 27, 45, 21, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (137, 23, 45, 15, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (138, 25, 45, 1, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (139, 24, 45, 19, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (140, 17, 45, 25, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (141, 18, 45, 14, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (142, 22, 45, 2, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (143, 37, 45, 26, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (144, 8, 45, 28, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (145, 28, 45, 3, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (146, 27, 45, 4, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (147, 4, 46, 20, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (148, 15, 46, 21, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (149, 5, 46, 15, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (150, 25, 46, 1, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (151, 24, 46, 19, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (152, 17, 46, 25, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (153, 18, 46, 14, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (154, 22, 46, 2, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (155, 37, 46, 26, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (156, 29, 46, 28, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (157, 28, 46, 3, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (158, 15, 46, 4, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (159, 4, 47, 20, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (160, 21, 47, 21, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (161, 5, 47, 15, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (162, 19, 47, 1, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (163, 17, 47, 22, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (164, 9, 47, 24, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (165, 17, 47, 25, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (166, 14, 47, 2, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (167, 37, 47, 26, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (168, 20, 47, 3, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (169, 21, 47, 4, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (170, 4, 48, 20, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (171, 14, 48, 21, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (172, 5, 48, 15, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (173, 19, 48, 1, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (174, 17, 48, 22, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (175, 9, 48, 24, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (176, 17, 48, 25, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (177, 14, 48, 2, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (178, 37, 48, 26, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (179, 20, 48, 3, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (180, 21, 48, 4, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (181, 9, 49, 21, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (182, 5, 49, 15, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (183, 19, 49, 1, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (184, 10, 49, 22, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (185, 9, 49, 24, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (186, 10, 49, 25, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (187, 14, 49, 2, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (188, 37, 49, 26, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (189, 20, 49, 3, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (190, 15, 49, 4, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (191, 19, 50, 21, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (192, 5, 50, 15, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (193, 19, 50, 1, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (194, 10, 50, 22, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (195, 9, 50, 24, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (196, 10, 50, 25, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (197, 14, 50, 2, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (198, 37, 50, 26, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (199, 20, 50, 3, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (200, 15, 50, 4, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (201, 20, 51, 21, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (202, 5, 51, 15, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (203, 19, 51, 1, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (204, 9, 51, 24, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (205, 11, 51, 25, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (206, 18, 51, 14, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (207, 14, 51, 2, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (208, 37, 51, 26, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (209, 20, 51, 3, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (210, 21, 51, 4, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (211, 15, 52, 8, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (212, 7, 52, 6, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (213, 36, 52, 21, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (214, 32, 52, 15, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (215, 30, 52, 1, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (216, 11, 52, 25, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (217, 18, 52, 14, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (218, 13, 52, 2, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (219, 37, 52, 26, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (220, 36, 52, 7, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (221, 6, 52, 3, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (222, 15, 53, 8, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (223, 7, 53, 6, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (224, 13, 53, 21, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (225, 32, 53, 15, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (226, 30, 53, 1, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (227, 11, 53, 25, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (228, 18, 53, 14, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (229, 13, 53, 2, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (230, 37, 53, 26, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (231, 36, 53, 7, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (232, 6, 53, 3, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (233, 34, 17, 18, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (234, 1, 17, 21, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (235, 1, 17, 1, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (236, 31, 17, 19, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (237, 16, 17, 25, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (238, 38, 17, 2, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (239, 3, 17, 26, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (240, 29, 17, 28, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (241, 31, 17, 3, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (242, 34, 54, 18, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (243, 38, 54, 21, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (244, 1, 54, 1, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (245, 31, 54, 19, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (246, 1, 54, 25, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (247, 38, 54, 2, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (248, 3, 54, 26, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (249, 29, 54, 28, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (250, 31, 54, 3, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (251, 34, 18, 18, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (252, 28, 18, 21, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (253, 1, 18, 1, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (254, 31, 18, 19, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (255, 16, 18, 25, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (256, 38, 18, 2, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (257, 3, 18, 26, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (258, 29, 18, 28, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `is_class_teacher`, `session`, `created_at`, `updated_at`) VALUES (259, 28, 18, 3, 0, '2026-2027', '2026-03-26 12:19:24', '2026-03-26 12:19:24');

DROP TABLE IF EXISTS `teacher_availability`;
CREATE TABLE `teacher_availability` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `teacher_id` BIGINT NOT NULL,
  `day_of_week` TEXT NOT NULL,
  `slot_index` BIGINT NOT NULL,
  `is_available` BIGINT NOT NULL DEFAULT '1',
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  KEY `teacher_availability_teacher_id_is_available_index` (`teacher_id`, `is_available`),
  KEY `teacher_availability_day_of_week_slot_index_index` (`day_of_week`, `slot_index`),
  UNIQUE KEY `teacher_availability_unique` (`teacher_id`, `day_of_week`, `slot_index`),
  CONSTRAINT `fk_teacher_availability_teacher_id_teachers_id` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `teacher_subject_assignments`;
CREATE TABLE `teacher_subject_assignments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `session` TEXT NOT NULL,
  `class_id` BIGINT NOT NULL,
  `class_section_id` BIGINT,
  `subject_id` BIGINT NOT NULL,
  `teacher_id` BIGINT NOT NULL,
  `group_name` TEXT NOT NULL DEFAULT '',
  `lessons_per_week` BIGINT NOT NULL DEFAULT '1',
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  KEY `teacher_subject_assignments_session_teacher_id_index` (`session`, `teacher_id`),
  KEY `teacher_subject_assignments_session_class_id_group_name_index` (`session`, `class_id`, `group_name`),
  UNIQUE KEY `teacher_subject_assignments_unique` (`session`, `class_id`, `subject_id`, `teacher_id`, `group_name`),
  CONSTRAINT `fk_teacher_subject_assignments_teacher_id_teachers_id` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
  CONSTRAINT `fk_teacher_subject_assignments_subject_id_subjects_id` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
  CONSTRAINT `fk_teacher_subject_assignments_class_section_id_class_sections_id` FOREIGN KEY (`class_section_id`) REFERENCES `class_sections` (`id`) ON UPDATE NO ACTION ON DELETE SET NULL,
  CONSTRAINT `fk_teacher_subject_assignments_class_id_school_classes_id` FOREIGN KEY (`class_id`) REFERENCES `school_classes` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (1, '2025-2026', 54, 1, 2, 33, 'a', 7, '2026-03-25 21:31:41', '2026-03-26 14:50:14');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (2, '2025-2026', 54, 1, 1, 1, 'a', 3, '2026-03-25 21:31:41', '2026-03-26 14:50:14');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (3, '2026-2027', 37, 2, 18, 34, 'ENTIRE CLA', 2, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (4, '2026-2027', 37, 2, 21, 26, 'ENTIRE CLA', 6, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (5, '2026-2027', 37, 2, 15, 23, 'ENTIRE CLA', 5, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (6, '2026-2027', 37, 2, 1, 1, 'ENTIRE CLA', 5, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (7, '2026-2027', 37, 2, 19, 24, 'ENTIRE CLA', 1, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (8, '2026-2027', 37, 2, 25, 16, 'ENTIRE CLA', 5, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (9, '2026-2027', 37, 2, 14, 18, 'ENTIRE CLA', 1, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (10, '2026-2027', 37, 2, 2, 38, 'ENTIRE CLA', 5, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (11, '2026-2027', 37, 2, 26, 3, 'ENTIRE CLA', 2, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (12, '2026-2027', 37, 2, 28, 29, 'ENTIRE CLA', 5, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (13, '2026-2027', 37, 2, 3, 26, 'ENTIRE CLA', 1, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (14, '2026-2027', 37, 2, 4, 27, 'ENTIRE CLA', 6, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (15, '2026-2027', 38, 3, 8, 15, 'BIO', 6, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (16, '2026-2027', 38, 4, 6, 7, 'ENTIRE CLA', 1, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (17, '2026-2027', 38, 4, 21, 7, 'ENTIRE CLA', 6, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (18, '2026-2027', 38, 5, 15, 32, 'COMP', 6, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (19, '2026-2027', 38, 4, 1, 30, 'ENTIRE CLA', 6, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (20, '2026-2027', 38, 4, 25, 10, 'ENTIRE CLA', 5, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (21, '2026-2027', 38, 4, 2, 13, 'ENTIRE CLA', 1, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (22, '2026-2027', 38, 4, 27, 8, 'ENTIRE CLA', 4, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (23, '2026-2027', 38, 4, 7, 36, 'ENTIRE CLA', 1, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (24, '2026-2027', 38, 4, 3, 6, 'ENTIRE CLA', 6, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (25, '2026-2027', 39, 6, 8, 21, 'BIO', 4, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (26, '2026-2027', 39, 7, 6, 7, 'CHEM', 2, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (27, '2026-2027', 39, 8, 21, 30, 'ENTIRE CLA', 6, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (28, '2026-2027', 39, 9, 15, 32, 'COM', 2, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (29, '2026-2027', 39, 8, 1, 30, 'ENTIRE CLA', 2, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (30, '2026-2027', 39, 8, 25, 10, 'ENTIRE CLA', 4, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (31, '2026-2027', 39, 8, 14, 18, 'ENTIRE CLA', 1, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (32, '2026-2027', 39, 10, 2, 13, 'MATH', 4, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (33, '2026-2027', 39, 8, 26, 37, 'ENTIRE CLA', 1, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (34, '2026-2027', 39, 8, 7, 36, 'ENTIRE CLA', 2, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (35, '2026-2027', 39, 8, 29, 10, 'ENTIRE CLA', 2, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (36, '2026-2027', 39, 8, 3, 6, 'ENTIRE CLA', 2, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (37, '2026-2027', 40, 11, 8, 21, 'BIO', 4, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (38, '2026-2027', 40, 12, 6, 7, 'CHEM', 2, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (39, '2026-2027', 40, 13, 21, 6, 'ENTIRE CLA', 6, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (40, '2026-2027', 40, 14, 15, 32, 'COMP', 2, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (41, '2026-2027', 40, 13, 1, 30, 'ENTIRE CLA', 2, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (42, '2026-2027', 40, 13, 14, 18, 'ENTIRE CLA', 1, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (43, '2026-2027', 40, 15, 2, 13, 'MATH', 4, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (44, '2026-2027', 40, 13, 26, 37, 'ENTIRE CLA', 1, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (45, '2026-2027', 40, 13, 27, 8, 'ENTIRE CLA', 6, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (46, '2026-2027', 40, 13, 7, 36, 'ENTIRE CLA', 2, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (47, '2026-2027', 40, 13, 3, 6, 'ENTIRE CLA', 2, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (48, '2026-2027', 41, 16, 18, 34, 'ENTIRE CLA', 2, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (49, '2026-2027', 41, 16, 21, 29, 'ENTIRE CLA', 6, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (50, '2026-2027', 41, 16, 15, 23, 'ENTIRE CLA', 5, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (51, '2026-2027', 41, 16, 1, 24, 'ENTIRE CLA', 1, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (52, '2026-2027', 41, 16, 19, 24, 'ENTIRE CLA', 1, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (53, '2026-2027', 41, 16, 25, 26, 'ENTIRE CLA', 5, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (54, '2026-2027', 41, 16, 14, 18, 'ENTIRE CLA', 1, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (55, '2026-2027', 41, 16, 2, 38, 'ENTIRE CLA', 1, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (56, '2026-2027', 41, 16, 26, 3, 'ENTIRE CLA', 2, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (57, '2026-2027', 41, 16, 28, 29, 'ENTIRE CLA', 5, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (58, '2026-2027', 41, 16, 3, 26, 'ENTIRE CLA', 1, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (59, '2026-2027', 41, 16, 4, 27, 'ENTIRE CLA', 6, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (60, '2026-2027', 42, 17, 20, 4, 'ENTIRE CLA', 2, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (61, '2026-2027', 42, 17, 21, 23, 'ENTIRE CLA', 6, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (62, '2026-2027', 42, 17, 15, 23, 'ENTIRE CLA', 5, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (63, '2026-2027', 42, 17, 1, 25, 'ENTIRE CLA', 1, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (64, '2026-2027', 42, 17, 19, 24, 'ENTIRE CLA', 1, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (65, '2026-2027', 42, 17, 25, 16, 'ENTIRE CLA', 5, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (66, '2026-2027', 42, 17, 14, 18, 'ENTIRE CLA', 1, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (67, '2026-2027', 42, 17, 2, 22, 'ENTIRE CLA', 1, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (68, '2026-2027', 42, 17, 26, 3, 'ENTIRE CLA', 2, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (69, '2026-2027', 42, 17, 28, 8, 'ENTIRE CLA', 5, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (70, '2026-2027', 42, 17, 3, 26, 'ENTIRE CLA', 1, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (71, '2026-2027', 42, 17, 4, 27, 'ENTIRE CLA', 6, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (72, '2026-2027', 43, 18, 20, 4, 'ENTIRE CLA', 2, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (73, '2026-2027', 43, 18, 21, 25, 'ENTIRE CLA', 6, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (74, '2026-2027', 43, 18, 15, 23, 'ENTIRE CLA', 5, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (75, '2026-2027', 43, 18, 1, 25, 'ENTIRE CLA', 1, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (76, '2026-2027', 43, 18, 19, 24, 'ENTIRE CLA', 1, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (77, '2026-2027', 43, 18, 25, 16, 'ENTIRE CLA', 5, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (78, '2026-2027', 43, 18, 14, 18, 'ENTIRE CLA', 1, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (79, '2026-2027', 43, 18, 2, 22, 'ENTIRE CLA', 1, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (80, '2026-2027', 43, 18, 26, 3, 'ENTIRE CLA', 2, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (81, '2026-2027', 43, 18, 28, 8, 'ENTIRE CLA', 5, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (82, '2026-2027', 43, 18, 3, 26, 'ENTIRE CLA', 1, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (83, '2026-2027', 43, 18, 4, 27, 'ENTIRE CLA', 6, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (84, '2026-2027', 44, 19, 20, 4, 'ENTIRE CLA', 2, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (85, '2026-2027', 44, 19, 21, 22, 'ENTIRE CLA', 6, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (86, '2026-2027', 44, 19, 15, 23, 'ENTIRE CLA', 5, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (87, '2026-2027', 44, 19, 1, 25, 'ENTIRE CLA', 1, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (88, '2026-2027', 44, 19, 19, 24, 'ENTIRE CLA', 1, '2026-03-26 12:19:23', '2026-03-26 12:19:23');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (89, '2026-2027', 44, 19, 25, 17, 'ENTIRE CLA', 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (90, '2026-2027', 44, 19, 14, 18, 'ENTIRE CLA', 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (91, '2026-2027', 44, 19, 2, 22, 'ENTIRE CLA', 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (92, '2026-2027', 44, 19, 26, 37, 'ENTIRE CLA', 2, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (93, '2026-2027', 44, 19, 28, 8, 'ENTIRE CLA', 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (94, '2026-2027', 44, 19, 3, 28, 'ENTIRE CLA', 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (95, '2026-2027', 44, 19, 4, 27, 'ENTIRE CLA', 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (96, '2026-2027', 45, 20, 20, 4, 'ENTIRE CLA', 2, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (97, '2026-2027', 45, 20, 21, 27, 'ENTIRE CLA', 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (98, '2026-2027', 45, 20, 15, 23, 'ENTIRE CLA', 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (99, '2026-2027', 45, 20, 1, 25, 'ENTIRE CLA', 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (100, '2026-2027', 45, 20, 19, 24, 'ENTIRE CLA', 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (101, '2026-2027', 45, 20, 25, 17, 'ENTIRE CLA', 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (102, '2026-2027', 45, 20, 14, 18, 'ENTIRE CLA', 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (103, '2026-2027', 45, 20, 2, 22, 'ENTIRE CLA', 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (104, '2026-2027', 45, 20, 26, 37, 'ENTIRE CLA', 2, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (105, '2026-2027', 45, 20, 28, 8, 'ENTIRE CLA', 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (106, '2026-2027', 45, 20, 3, 28, 'ENTIRE CLA', 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (107, '2026-2027', 45, 20, 4, 27, 'ENTIRE CLA', 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (108, '2026-2027', 46, 21, 20, 4, 'ENTIRE CLA', 2, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (109, '2026-2027', 46, 21, 21, 15, 'ENTIRE CLA', 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (110, '2026-2027', 46, 21, 15, 5, 'ENTIRE CLA', 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (111, '2026-2027', 46, 21, 1, 25, 'ENTIRE CLA', 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (112, '2026-2027', 46, 21, 19, 24, 'ENTIRE CLA', 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (113, '2026-2027', 46, 21, 25, 17, 'ENTIRE CLA', 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (114, '2026-2027', 46, 21, 14, 18, 'ENTIRE CLA', 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (115, '2026-2027', 46, 21, 2, 22, 'ENTIRE CLA', 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (116, '2026-2027', 46, 21, 26, 37, 'ENTIRE CLA', 2, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (117, '2026-2027', 46, 21, 28, 29, 'ENTIRE CLA', 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (118, '2026-2027', 46, 21, 3, 28, 'ENTIRE CLA', 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (119, '2026-2027', 46, 21, 4, 15, 'ENTIRE CLA', 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (120, '2026-2027', 47, 22, 20, 4, 'ENTIRE CLA', 2, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (121, '2026-2027', 47, 22, 21, 21, 'ENTIRE CLA', 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (122, '2026-2027', 47, 22, 15, 5, 'ENTIRE CLA', 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (123, '2026-2027', 47, 22, 1, 19, 'ENTIRE CLA', 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (124, '2026-2027', 47, 22, 22, 17, 'ENTIRE CLA', 4, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (125, '2026-2027', 47, 22, 24, 9, 'ENTIRE CLA', 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (126, '2026-2027', 47, 22, 25, 17, 'ENTIRE CLA', 4, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (127, '2026-2027', 47, 22, 2, 14, 'ENTIRE CLA', 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (128, '2026-2027', 47, 22, 26, 37, 'ENTIRE CLA', 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (129, '2026-2027', 47, 22, 3, 20, 'ENTIRE CLA', 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (130, '2026-2027', 47, 22, 4, 21, 'ENTIRE CLA', 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (131, '2026-2027', 48, 23, 20, 4, 'ENTIRE CLA', 2, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (132, '2026-2027', 48, 23, 21, 14, 'ENTIRE CLA', 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (133, '2026-2027', 48, 23, 15, 5, 'ENTIRE CLA', 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (134, '2026-2027', 48, 23, 1, 19, 'ENTIRE CLA', 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (135, '2026-2027', 48, 23, 22, 17, 'ENTIRE CLA', 4, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (136, '2026-2027', 48, 23, 24, 9, 'ENTIRE CLA', 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (137, '2026-2027', 48, 23, 25, 17, 'ENTIRE CLA', 4, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (138, '2026-2027', 48, 23, 2, 14, 'ENTIRE CLA', 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (139, '2026-2027', 48, 23, 26, 37, 'ENTIRE CLA', 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (140, '2026-2027', 48, 23, 3, 20, 'ENTIRE CLA', 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (141, '2026-2027', 48, 23, 4, 21, 'ENTIRE CLA', 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (142, '2026-2027', 49, 24, 21, 9, 'ENTIRE CLA', 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (143, '2026-2027', 49, 24, 15, 5, 'ENTIRE CLA', 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (144, '2026-2027', 49, 24, 1, 19, 'ENTIRE CLA', 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (145, '2026-2027', 49, 24, 22, 10, 'ENTIRE CLA', 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (146, '2026-2027', 49, 24, 24, 9, 'ENTIRE CLA', 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (147, '2026-2027', 49, 24, 25, 10, 'ENTIRE CLA', 4, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (148, '2026-2027', 49, 24, 2, 14, 'ENTIRE CLA', 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (149, '2026-2027', 49, 24, 26, 37, 'ENTIRE CLA', 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (150, '2026-2027', 49, 24, 3, 20, 'ENTIRE CLA', 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (151, '2026-2027', 49, 24, 4, 15, 'ENTIRE CLA', 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (152, '2026-2027', 50, 25, 21, 19, 'ENTIRE CLA', 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (153, '2026-2027', 50, 25, 15, 5, 'ENTIRE CLA', 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (154, '2026-2027', 50, 25, 1, 19, 'ENTIRE CLA', 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (155, '2026-2027', 50, 25, 22, 10, 'ENTIRE CLA', 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (156, '2026-2027', 50, 25, 24, 9, 'ENTIRE CLA', 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (157, '2026-2027', 50, 25, 25, 10, 'ENTIRE CLA', 4, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (158, '2026-2027', 50, 25, 2, 14, 'ENTIRE CLA', 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (159, '2026-2027', 50, 25, 26, 37, 'ENTIRE CLA', 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (160, '2026-2027', 50, 25, 3, 20, 'ENTIRE CLA', 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (161, '2026-2027', 50, 25, 4, 15, 'ENTIRE CLA', 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (162, '2026-2027', 51, 26, 21, 20, 'ENTIRE CLA', 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (163, '2026-2027', 51, 26, 15, 5, 'ENTIRE CLA', 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (164, '2026-2027', 51, 26, 1, 19, 'ENTIRE CLA', 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (165, '2026-2027', 51, 26, 24, 9, 'ENTIRE CLA', 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (166, '2026-2027', 51, 26, 25, 11, 'ENTIRE CLA', 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (167, '2026-2027', 51, 26, 14, 18, 'ENTIRE CLA', 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (168, '2026-2027', 51, 26, 2, 14, 'ENTIRE CLA', 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (169, '2026-2027', 51, 26, 26, 37, 'ENTIRE CLA', 2, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (170, '2026-2027', 51, 26, 3, 20, 'ENTIRE CLA', 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (171, '2026-2027', 51, 26, 4, 21, 'ENTIRE CLA', 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (172, '2026-2027', 52, 27, 8, 15, 'BIO', 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (173, '2026-2027', 52, 28, 6, 7, 'ENTIRE CLA', 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (174, '2026-2027', 52, 28, 21, 36, 'ENTIRE CLA', 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (175, '2026-2027', 52, 29, 15, 32, 'COMP', 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (176, '2026-2027', 52, 28, 1, 30, 'ENTIRE CLA', 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (177, '2026-2027', 52, 28, 25, 11, 'ENTIRE CLA', 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (178, '2026-2027', 52, 28, 14, 18, 'ENTIRE CLA', 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (179, '2026-2027', 52, 28, 2, 13, 'ENTIRE CLA', 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (180, '2026-2027', 52, 28, 26, 37, 'ENTIRE CLA', 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (181, '2026-2027', 52, 28, 7, 36, 'ENTIRE CLA', 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (182, '2026-2027', 52, 28, 3, 6, 'ENTIRE CLA', 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (183, '2026-2027', 53, 30, 8, 15, 'BIO', 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (184, '2026-2027', 53, 31, 6, 7, 'ENTIRE CLA', 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (185, '2026-2027', 53, 31, 21, 13, 'ENTIRE CLA', 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (186, '2026-2027', 53, 32, 15, 32, 'COMP', 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (187, '2026-2027', 53, 31, 1, 30, 'ENTIRE CLA', 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (188, '2026-2027', 53, 31, 25, 11, 'ENTIRE CLA', 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (189, '2026-2027', 53, 31, 14, 18, 'ENTIRE CLA', 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (190, '2026-2027', 53, 31, 2, 13, 'ENTIRE CLA', 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (191, '2026-2027', 53, 31, 26, 37, 'ENTIRE CLA', 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (192, '2026-2027', 53, 31, 7, 36, 'ENTIRE CLA', 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (193, '2026-2027', 53, 31, 3, 6, 'ENTIRE CLA', 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (194, '2026-2027', 17, 33, 18, 34, 'ENTIRE CLA', 2, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (195, '2026-2027', 17, 33, 21, 1, 'ENTIRE CLA', 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (196, '2026-2027', 17, 33, 1, 1, 'ENTIRE CLA', 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (197, '2026-2027', 17, 33, 19, 31, 'ENTIRE CLA', 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (198, '2026-2027', 17, 33, 25, 16, 'ENTIRE CLA', 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (199, '2026-2027', 17, 33, 2, 38, 'ENTIRE CLA', 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (200, '2026-2027', 17, 33, 26, 3, 'ENTIRE CLA', 2, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (201, '2026-2027', 17, 33, 28, 29, 'ENTIRE CLA', 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (202, '2026-2027', 17, 33, 3, 31, 'ENTIRE CLA', 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (203, '2026-2027', 54, 34, 18, 34, 'ENTIRE CLA', 2, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (204, '2026-2027', 54, 34, 21, 38, 'ENTIRE CLA', 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (205, '2026-2027', 54, 34, 1, 1, 'ENTIRE CLA', 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (206, '2026-2027', 54, 34, 19, 31, 'ENTIRE CLA', 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (207, '2026-2027', 54, 34, 25, 1, 'ENTIRE CLA', 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (208, '2026-2027', 54, 34, 2, 38, 'ENTIRE CLA', 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (209, '2026-2027', 54, 34, 26, 3, 'ENTIRE CLA', 2, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (210, '2026-2027', 54, 34, 28, 29, 'ENTIRE CLA', 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (211, '2026-2027', 54, 34, 3, 31, 'ENTIRE CLA', 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (212, '2026-2027', 18, 35, 18, 34, 'ENTIRE CLA', 2, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (213, '2026-2027', 18, 35, 21, 28, 'ENTIRE CLA', 6, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (214, '2026-2027', 18, 35, 1, 1, 'ENTIRE CLA', 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (215, '2026-2027', 18, 35, 19, 31, 'ENTIRE CLA', 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (216, '2026-2027', 18, 35, 25, 16, 'ENTIRE CLA', 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (217, '2026-2027', 18, 35, 2, 38, 'ENTIRE CLA', 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (218, '2026-2027', 18, 35, 26, 3, 'ENTIRE CLA', 2, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (219, '2026-2027', 18, 35, 28, 29, 'ENTIRE CLA', 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `teacher_subject_assignments` (`id`, `session`, `class_id`, `class_section_id`, `subject_id`, `teacher_id`, `group_name`, `lessons_per_week`, `created_at`, `updated_at`) VALUES (220, '2026-2027', 18, 35, 3, 28, 'ENTIRE CLA', 5, '2026-03-26 12:19:24', '2026-03-26 12:19:24');

DROP TABLE IF EXISTS `teachers`;
CREATE TABLE `teachers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `teacher_id` TEXT NOT NULL,
  `user_id` BIGINT NOT NULL,
  `designation` TEXT,
  `employee_code` TEXT,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  UNIQUE KEY `teachers_employee_code_unique` (`employee_code`),
  UNIQUE KEY `teachers_user_id_unique` (`user_id`),
  UNIQUE KEY `teachers_teacher_id_unique` (`teacher_id`),
  CONSTRAINT `fk_teachers_user_id_users_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `teachers` (`id`, `teacher_id`, `user_id`, `designation`, `employee_code`, `created_at`, `updated_at`) VALUES (1, 'T-0001', 7, 'Teacher', NULL, '2026-03-25 21:31:24', '2026-03-25 21:31:24');
INSERT INTO `teachers` (`id`, `teacher_id`, `user_id`, `designation`, `employee_code`, `created_at`, `updated_at`) VALUES (2, 'T-0002', 8, 'Teacher', NULL, '2026-03-25 21:31:24', '2026-03-25 21:31:24');
INSERT INTO `teachers` (`id`, `teacher_id`, `user_id`, `designation`, `employee_code`, `created_at`, `updated_at`) VALUES (3, 'T-0003', 9, 'Teacher', NULL, '2026-03-25 21:31:25', '2026-03-25 21:31:25');
INSERT INTO `teachers` (`id`, `teacher_id`, `user_id`, `designation`, `employee_code`, `created_at`, `updated_at`) VALUES (4, 'T-0004', 10, 'Teacher', NULL, '2026-03-25 21:31:25', '2026-03-25 21:31:25');
INSERT INTO `teachers` (`id`, `teacher_id`, `user_id`, `designation`, `employee_code`, `created_at`, `updated_at`) VALUES (5, 'T-0005', 11, 'Teacher', NULL, '2026-03-25 21:31:26', '2026-03-25 21:31:26');
INSERT INTO `teachers` (`id`, `teacher_id`, `user_id`, `designation`, `employee_code`, `created_at`, `updated_at`) VALUES (6, 'T-0006', 12, 'Teacher', NULL, '2026-03-25 21:31:26', '2026-03-25 21:31:26');
INSERT INTO `teachers` (`id`, `teacher_id`, `user_id`, `designation`, `employee_code`, `created_at`, `updated_at`) VALUES (7, 'T-0007', 13, 'Teacher', NULL, '2026-03-25 21:31:27', '2026-03-25 21:31:27');
INSERT INTO `teachers` (`id`, `teacher_id`, `user_id`, `designation`, `employee_code`, `created_at`, `updated_at`) VALUES (8, 'T-0008', 14, 'Teacher', NULL, '2026-03-25 21:31:28', '2026-03-25 21:31:28');
INSERT INTO `teachers` (`id`, `teacher_id`, `user_id`, `designation`, `employee_code`, `created_at`, `updated_at`) VALUES (9, 'T-0009', 15, 'Teacher', NULL, '2026-03-25 21:31:28', '2026-03-25 21:31:28');
INSERT INTO `teachers` (`id`, `teacher_id`, `user_id`, `designation`, `employee_code`, `created_at`, `updated_at`) VALUES (10, 'T-0010', 16, 'Teacher', NULL, '2026-03-25 21:31:29', '2026-03-25 21:31:29');
INSERT INTO `teachers` (`id`, `teacher_id`, `user_id`, `designation`, `employee_code`, `created_at`, `updated_at`) VALUES (11, 'T-0011', 17, 'Teacher', NULL, '2026-03-25 21:31:29', '2026-03-25 21:31:29');
INSERT INTO `teachers` (`id`, `teacher_id`, `user_id`, `designation`, `employee_code`, `created_at`, `updated_at`) VALUES (12, 'T-0012', 18, 'Teacher', NULL, '2026-03-25 21:31:29', '2026-03-25 21:31:29');
INSERT INTO `teachers` (`id`, `teacher_id`, `user_id`, `designation`, `employee_code`, `created_at`, `updated_at`) VALUES (13, 'T-0013', 19, 'Teacher', NULL, '2026-03-25 21:31:30', '2026-03-25 21:31:30');
INSERT INTO `teachers` (`id`, `teacher_id`, `user_id`, `designation`, `employee_code`, `created_at`, `updated_at`) VALUES (14, 'T-0014', 20, 'Teacher', NULL, '2026-03-25 21:31:30', '2026-03-25 21:31:30');
INSERT INTO `teachers` (`id`, `teacher_id`, `user_id`, `designation`, `employee_code`, `created_at`, `updated_at`) VALUES (15, 'T-0015', 21, 'Teacher', NULL, '2026-03-25 21:31:31', '2026-03-25 21:31:31');
INSERT INTO `teachers` (`id`, `teacher_id`, `user_id`, `designation`, `employee_code`, `created_at`, `updated_at`) VALUES (16, 'T-0016', 22, 'Teacher', NULL, '2026-03-25 21:31:32', '2026-03-25 21:31:32');
INSERT INTO `teachers` (`id`, `teacher_id`, `user_id`, `designation`, `employee_code`, `created_at`, `updated_at`) VALUES (17, 'T-0017', 23, 'Teacher', NULL, '2026-03-25 21:31:33', '2026-03-25 21:31:33');
INSERT INTO `teachers` (`id`, `teacher_id`, `user_id`, `designation`, `employee_code`, `created_at`, `updated_at`) VALUES (18, 'T-0018', 24, 'Teacher', NULL, '2026-03-25 21:31:33', '2026-03-25 21:31:33');
INSERT INTO `teachers` (`id`, `teacher_id`, `user_id`, `designation`, `employee_code`, `created_at`, `updated_at`) VALUES (19, 'T-0019', 25, 'Teacher', NULL, '2026-03-25 21:31:33', '2026-03-25 21:31:33');
INSERT INTO `teachers` (`id`, `teacher_id`, `user_id`, `designation`, `employee_code`, `created_at`, `updated_at`) VALUES (20, 'T-0020', 26, 'Teacher', NULL, '2026-03-25 21:31:34', '2026-03-25 21:31:34');
INSERT INTO `teachers` (`id`, `teacher_id`, `user_id`, `designation`, `employee_code`, `created_at`, `updated_at`) VALUES (21, 'T-0021', 27, 'Teacher', NULL, '2026-03-25 21:31:34', '2026-03-25 21:31:34');
INSERT INTO `teachers` (`id`, `teacher_id`, `user_id`, `designation`, `employee_code`, `created_at`, `updated_at`) VALUES (22, 'T-0022', 28, 'Teacher', NULL, '2026-03-25 21:31:35', '2026-03-25 21:31:35');
INSERT INTO `teachers` (`id`, `teacher_id`, `user_id`, `designation`, `employee_code`, `created_at`, `updated_at`) VALUES (23, 'T-0023', 29, 'Teacher', NULL, '2026-03-25 21:31:35', '2026-03-25 21:31:35');
INSERT INTO `teachers` (`id`, `teacher_id`, `user_id`, `designation`, `employee_code`, `created_at`, `updated_at`) VALUES (24, 'T-0024', 30, 'Teacher', NULL, '2026-03-25 21:31:36', '2026-03-25 21:31:36');
INSERT INTO `teachers` (`id`, `teacher_id`, `user_id`, `designation`, `employee_code`, `created_at`, `updated_at`) VALUES (25, 'T-0025', 31, 'Teacher', NULL, '2026-03-25 21:31:36', '2026-03-25 21:31:36');
INSERT INTO `teachers` (`id`, `teacher_id`, `user_id`, `designation`, `employee_code`, `created_at`, `updated_at`) VALUES (26, 'T-0026', 32, 'Teacher', NULL, '2026-03-25 21:31:37', '2026-03-25 21:31:37');
INSERT INTO `teachers` (`id`, `teacher_id`, `user_id`, `designation`, `employee_code`, `created_at`, `updated_at`) VALUES (27, 'T-0027', 33, 'Teacher', NULL, '2026-03-25 21:31:37', '2026-03-25 21:31:37');
INSERT INTO `teachers` (`id`, `teacher_id`, `user_id`, `designation`, `employee_code`, `created_at`, `updated_at`) VALUES (28, 'T-0028', 34, 'Teacher', NULL, '2026-03-25 21:31:38', '2026-03-25 21:31:38');
INSERT INTO `teachers` (`id`, `teacher_id`, `user_id`, `designation`, `employee_code`, `created_at`, `updated_at`) VALUES (29, 'T-0029', 35, 'Teacher', NULL, '2026-03-25 21:31:38', '2026-03-25 21:31:38');
INSERT INTO `teachers` (`id`, `teacher_id`, `user_id`, `designation`, `employee_code`, `created_at`, `updated_at`) VALUES (30, 'T-0030', 36, 'Teacher', NULL, '2026-03-25 21:31:38', '2026-03-25 21:31:38');
INSERT INTO `teachers` (`id`, `teacher_id`, `user_id`, `designation`, `employee_code`, `created_at`, `updated_at`) VALUES (31, 'T-0031', 37, 'Teacher', NULL, '2026-03-25 21:31:39', '2026-03-25 21:31:39');
INSERT INTO `teachers` (`id`, `teacher_id`, `user_id`, `designation`, `employee_code`, `created_at`, `updated_at`) VALUES (32, 'T-0032', 38, 'Teacher', NULL, '2026-03-25 21:31:40', '2026-03-25 21:31:40');
INSERT INTO `teachers` (`id`, `teacher_id`, `user_id`, `designation`, `employee_code`, `created_at`, `updated_at`) VALUES (33, 'T-0033', 39, 'Teacher', NULL, '2026-03-25 21:31:40', '2026-03-25 21:31:40');
INSERT INTO `teachers` (`id`, `teacher_id`, `user_id`, `designation`, `employee_code`, `created_at`, `updated_at`) VALUES (34, 'T-0034', 40, 'Teacher', NULL, '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `teachers` (`id`, `teacher_id`, `user_id`, `designation`, `employee_code`, `created_at`, `updated_at`) VALUES (35, 'T-0035', 3, 'Teacher', NULL, '2026-03-25 21:35:39', '2026-03-25 21:35:39');
INSERT INTO `teachers` (`id`, `teacher_id`, `user_id`, `designation`, `employee_code`, `created_at`, `updated_at`) VALUES (36, 'T-0036', 41, 'Teacher', NULL, '2026-03-26 12:19:15', '2026-03-26 12:19:15');
INSERT INTO `teachers` (`id`, `teacher_id`, `user_id`, `designation`, `employee_code`, `created_at`, `updated_at`) VALUES (37, 'T-0037', 42, 'Teacher', NULL, '2026-03-26 12:19:17', '2026-03-26 12:19:17');
INSERT INTO `teachers` (`id`, `teacher_id`, `user_id`, `designation`, `employee_code`, `created_at`, `updated_at`) VALUES (38, 'T-0038', 43, 'Teacher', NULL, '2026-03-26 12:19:18', '2026-03-26 12:19:18');

DROP TABLE IF EXISTS `time_slots`;
CREATE TABLE `time_slots` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `day_of_week` TEXT NOT NULL,
  `slot_index` BIGINT NOT NULL,
  `start_time` TEXT NOT NULL,
  `end_time` TEXT NOT NULL,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  KEY `time_slots_slot_index_index` (`slot_index`),
  UNIQUE KEY `time_slots_day_of_week_slot_index_unique` (`day_of_week`, `slot_index`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `time_slots` (`id`, `day_of_week`, `slot_index`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES (1, 'mon', 0, '08:10:00', '08:20:00', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `time_slots` (`id`, `day_of_week`, `slot_index`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES (2, 'tue', 0, '08:10:00', '08:20:00', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `time_slots` (`id`, `day_of_week`, `slot_index`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES (3, 'wed', 0, '08:10:00', '08:20:00', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `time_slots` (`id`, `day_of_week`, `slot_index`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES (4, 'thu', 0, '08:10:00', '08:20:00', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `time_slots` (`id`, `day_of_week`, `slot_index`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES (5, 'fri', 0, '08:10:00', '08:20:00', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `time_slots` (`id`, `day_of_week`, `slot_index`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES (6, 'sat', 0, '08:10:00', '08:20:00', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `time_slots` (`id`, `day_of_week`, `slot_index`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES (7, 'mon', 1, '08:20:00', '09:00:00', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `time_slots` (`id`, `day_of_week`, `slot_index`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES (8, 'tue', 1, '08:20:00', '09:00:00', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `time_slots` (`id`, `day_of_week`, `slot_index`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES (9, 'wed', 1, '08:20:00', '09:00:00', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `time_slots` (`id`, `day_of_week`, `slot_index`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES (10, 'thu', 1, '08:20:00', '09:00:00', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `time_slots` (`id`, `day_of_week`, `slot_index`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES (11, 'fri', 1, '08:20:00', '09:00:00', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `time_slots` (`id`, `day_of_week`, `slot_index`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES (12, 'sat', 1, '08:20:00', '09:00:00', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `time_slots` (`id`, `day_of_week`, `slot_index`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES (13, 'mon', 2, '09:00:00', '09:40:00', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `time_slots` (`id`, `day_of_week`, `slot_index`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES (14, 'tue', 2, '09:00:00', '09:40:00', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `time_slots` (`id`, `day_of_week`, `slot_index`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES (15, 'wed', 2, '09:00:00', '09:40:00', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `time_slots` (`id`, `day_of_week`, `slot_index`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES (16, 'thu', 2, '09:00:00', '09:40:00', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `time_slots` (`id`, `day_of_week`, `slot_index`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES (17, 'fri', 2, '09:00:00', '09:40:00', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `time_slots` (`id`, `day_of_week`, `slot_index`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES (18, 'sat', 2, '09:00:00', '09:40:00', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `time_slots` (`id`, `day_of_week`, `slot_index`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES (19, 'mon', 3, '09:40:00', '10:20:00', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `time_slots` (`id`, `day_of_week`, `slot_index`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES (20, 'tue', 3, '09:40:00', '10:20:00', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `time_slots` (`id`, `day_of_week`, `slot_index`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES (21, 'wed', 3, '09:40:00', '10:20:00', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `time_slots` (`id`, `day_of_week`, `slot_index`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES (22, 'thu', 3, '09:40:00', '10:20:00', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `time_slots` (`id`, `day_of_week`, `slot_index`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES (23, 'fri', 3, '09:40:00', '10:20:00', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `time_slots` (`id`, `day_of_week`, `slot_index`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES (24, 'sat', 3, '09:40:00', '10:20:00', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `time_slots` (`id`, `day_of_week`, `slot_index`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES (25, 'mon', 4, '10:20:00', '11:00:00', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `time_slots` (`id`, `day_of_week`, `slot_index`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES (26, 'tue', 4, '10:20:00', '11:00:00', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `time_slots` (`id`, `day_of_week`, `slot_index`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES (27, 'wed', 4, '10:20:00', '11:00:00', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `time_slots` (`id`, `day_of_week`, `slot_index`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES (28, 'thu', 4, '10:20:00', '11:00:00', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `time_slots` (`id`, `day_of_week`, `slot_index`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES (29, 'fri', 4, '10:20:00', '11:00:00', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `time_slots` (`id`, `day_of_week`, `slot_index`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES (30, 'sat', 4, '10:20:00', '11:00:00', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `time_slots` (`id`, `day_of_week`, `slot_index`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES (31, 'mon', 5, '11:00:00', '11:40:00', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `time_slots` (`id`, `day_of_week`, `slot_index`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES (32, 'tue', 5, '11:00:00', '11:40:00', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `time_slots` (`id`, `day_of_week`, `slot_index`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES (33, 'wed', 5, '11:00:00', '11:40:00', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `time_slots` (`id`, `day_of_week`, `slot_index`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES (34, 'thu', 5, '11:00:00', '11:40:00', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `time_slots` (`id`, `day_of_week`, `slot_index`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES (35, 'fri', 5, '11:00:00', '11:40:00', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `time_slots` (`id`, `day_of_week`, `slot_index`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES (36, 'sat', 5, '11:00:00', '11:40:00', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `time_slots` (`id`, `day_of_week`, `slot_index`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES (37, 'mon', 6, '11:55:00', '12:35:00', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `time_slots` (`id`, `day_of_week`, `slot_index`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES (38, 'tue', 6, '11:55:00', '12:35:00', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `time_slots` (`id`, `day_of_week`, `slot_index`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES (39, 'wed', 6, '11:55:00', '12:35:00', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `time_slots` (`id`, `day_of_week`, `slot_index`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES (40, 'thu', 6, '11:55:00', '12:35:00', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `time_slots` (`id`, `day_of_week`, `slot_index`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES (41, 'fri', 6, '11:55:00', '12:35:00', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `time_slots` (`id`, `day_of_week`, `slot_index`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES (42, 'sat', 6, '11:55:00', '12:35:00', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `time_slots` (`id`, `day_of_week`, `slot_index`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES (43, 'mon', 7, '12:35:00', '13:15:00', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `time_slots` (`id`, `day_of_week`, `slot_index`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES (44, 'tue', 7, '12:35:00', '13:15:00', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `time_slots` (`id`, `day_of_week`, `slot_index`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES (45, 'wed', 7, '12:35:00', '13:15:00', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `time_slots` (`id`, `day_of_week`, `slot_index`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES (46, 'thu', 7, '12:35:00', '13:15:00', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `time_slots` (`id`, `day_of_week`, `slot_index`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES (47, 'fri', 7, '12:35:00', '13:15:00', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `time_slots` (`id`, `day_of_week`, `slot_index`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES (48, 'sat', 7, '12:35:00', '13:15:00', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `time_slots` (`id`, `day_of_week`, `slot_index`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES (49, 'mon', 8, '13:15:00', '13:55:00', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `time_slots` (`id`, `day_of_week`, `slot_index`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES (50, 'tue', 8, '13:15:00', '13:55:00', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `time_slots` (`id`, `day_of_week`, `slot_index`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES (51, 'wed', 8, '13:15:00', '13:55:00', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `time_slots` (`id`, `day_of_week`, `slot_index`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES (52, 'thu', 8, '13:15:00', '13:55:00', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `time_slots` (`id`, `day_of_week`, `slot_index`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES (53, 'fri', 8, '13:15:00', '13:55:00', '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `time_slots` (`id`, `day_of_week`, `slot_index`, `start_time`, `end_time`, `created_at`, `updated_at`) VALUES (54, 'sat', 8, '13:15:00', '13:55:00', '2026-03-25 21:31:41', '2026-03-25 21:31:41');

DROP TABLE IF EXISTS `timetable_constraints`;
CREATE TABLE `timetable_constraints` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `session` TEXT NOT NULL,
  `max_periods_per_day_teacher` BIGINT NOT NULL DEFAULT '6',
  `max_periods_per_week_teacher` BIGINT NOT NULL DEFAULT '28',
  `max_periods_per_day_class` BIGINT NOT NULL DEFAULT '7',
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  UNIQUE KEY `timetable_constraints_session_unique` (`session`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `timetable_entries`;
CREATE TABLE `timetable_entries` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `session` TEXT NOT NULL,
  `class_section_id` BIGINT NOT NULL,
  `day_of_week` TEXT NOT NULL,
  `slot_index` BIGINT NOT NULL,
  `subject_id` BIGINT NOT NULL,
  `teacher_id` BIGINT NOT NULL,
  `room_id` BIGINT NOT NULL,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY (`id`),
  KEY `timetable_entries_day_of_week_slot_index_index` (`day_of_week`, `slot_index`),
  KEY `timetable_entries_session_class_section_id_index` (`session`, `class_section_id`),
  KEY `timetable_entries_session_teacher_id_index` (`session`, `teacher_id`),
  UNIQUE KEY `timetable_entries_unique` (`session`, `class_section_id`, `day_of_week`, `slot_index`),
  CONSTRAINT `fk_timetable_entries_room_id_rooms_id` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
  CONSTRAINT `fk_timetable_entries_teacher_id_teachers_id` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
  CONSTRAINT `fk_timetable_entries_subject_id_subjects_id` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
  CONSTRAINT `fk_timetable_entries_class_section_id_class_sections_id` FOREIGN KEY (`class_section_id`) REFERENCES `class_sections` (`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1, '2025-2026', 1, 'thu', 3, 2, 33, 1, '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (2, '2025-2026', 1, 'thu', 4, 2, 33, 1, '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (3, '2025-2026', 1, 'mon', 3, 2, 33, 1, '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (4, '2025-2026', 1, 'tue', 1, 2, 33, 1, '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (5, '2025-2026', 1, 'wed', 5, 2, 33, 1, '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (6, '2025-2026', 1, 'fri', 6, 2, 33, 1, '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (7, '2025-2026', 1, 'sat', 5, 2, 33, 1, '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (8, '2025-2026', 1, 'mon', 1, 1, 1, 1, '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (9, '2025-2026', 1, 'wed', 3, 1, 1, 1, '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (10, '2025-2026', 1, 'sat', 2, 1, 1, 1, '2026-03-25 21:31:41', '2026-03-25 21:31:41');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (11, '2026-2027', 2, 'fri', 0, 21, 26, 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (12, '2026-2027', 2, 'fri', 1, 28, 29, 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (13, '2026-2027', 2, 'fri', 2, 18, 34, 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (14, '2026-2027', 2, 'fri', 3, 25, 16, 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (15, '2026-2027', 2, 'fri', 4, 2, 38, 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (16, '2026-2027', 2, 'fri', 5, 15, 23, 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (17, '2026-2027', 2, 'fri', 6, 3, 26, 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (18, '2026-2027', 2, 'fri', 7, 1, 1, 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (19, '2026-2027', 2, 'fri', 8, 4, 27, 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (20, '2026-2027', 2, 'mon', 0, 21, 26, 1, '2026-03-26 12:19:24', '2026-03-26 12:19:24');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (21, '2026-2027', 2, 'mon', 1, 4, 27, 1, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (22, '2026-2027', 2, 'mon', 2, 3, 26, 1, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (23, '2026-2027', 2, 'mon', 3, 28, 29, 1, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (24, '2026-2027', 2, 'mon', 4, 15, 23, 1, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (25, '2026-2027', 2, 'mon', 5, 26, 3, 1, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (26, '2026-2027', 2, 'mon', 6, 25, 16, 1, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (27, '2026-2027', 2, 'mon', 7, 2, 38, 1, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (28, '2026-2027', 2, 'mon', 8, 1, 1, 1, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (29, '2026-2027', 2, 'sat', 0, 21, 26, 1, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (30, '2026-2027', 2, 'sat', 1, 3, 26, 1, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (31, '2026-2027', 2, 'sat', 2, 3, 26, 1, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (32, '2026-2027', 2, 'sat', 3, 18, 34, 1, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (33, '2026-2027', 2, 'sat', 4, 1, 1, 1, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (34, '2026-2027', 2, 'sat', 5, 4, 27, 1, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (35, '2026-2027', 2, 'sat', 6, 2, 38, 1, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (36, '2026-2027', 2, 'sat', 7, 25, 16, 1, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (37, '2026-2027', 2, 'sat', 8, 15, 23, 1, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (38, '2026-2027', 2, 'thu', 0, 21, 26, 1, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (39, '2026-2027', 2, 'thu', 1, 3, 26, 1, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (40, '2026-2027', 2, 'thu', 2, 4, 27, 1, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (41, '2026-2027', 2, 'thu', 3, 25, 16, 1, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (42, '2026-2027', 2, 'thu', 4, 2, 38, 1, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (43, '2026-2027', 2, 'thu', 5, 2, 38, 1, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (44, '2026-2027', 2, 'thu', 6, 19, 24, 1, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (45, '2026-2027', 2, 'thu', 7, 28, 29, 1, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (46, '2026-2027', 2, 'thu', 8, 1, 1, 1, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (47, '2026-2027', 2, 'tue', 0, 21, 26, 1, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (48, '2026-2027', 2, 'tue', 1, 2, 38, 1, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (49, '2026-2027', 2, 'tue', 2, 14, 18, 1, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (50, '2026-2027', 2, 'tue', 3, 26, 3, 1, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (51, '2026-2027', 2, 'tue', 4, 15, 23, 1, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (52, '2026-2027', 2, 'tue', 5, 4, 27, 1, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (53, '2026-2027', 2, 'tue', 6, 3, 26, 1, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (54, '2026-2027', 2, 'tue', 7, 28, 29, 1, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (55, '2026-2027', 2, 'tue', 8, 1, 1, 1, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (56, '2026-2027', 2, 'wed', 0, 21, 26, 1, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (57, '2026-2027', 2, 'wed', 1, 3, 26, 1, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (58, '2026-2027', 2, 'wed', 2, 28, 29, 1, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (59, '2026-2027', 2, 'wed', 3, 4, 27, 1, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (60, '2026-2027', 2, 'wed', 4, 25, 16, 1, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (61, '2026-2027', 2, 'wed', 5, 15, 23, 1, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (62, '2026-2027', 2, 'wed', 6, 1, 1, 1, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (63, '2026-2027', 2, 'wed', 7, 1, 1, 1, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (64, '2026-2027', 2, 'wed', 8, 2, 38, 1, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (65, '2026-2027', 4, 'fri', 0, 21, 7, 2, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (66, '2026-2027', 4, 'fri', 1, 2, 13, 2, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (67, '2026-2027', 3, 'fri', 2, 8, 15, 2, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (68, '2026-2027', 5, 'fri', 2, 15, 32, 3, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (69, '2026-2027', 4, 'fri', 3, 27, 8, 2, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (70, '2026-2027', 4, 'fri', 4, 3, 6, 2, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (71, '2026-2027', 4, 'fri', 5, 7, 36, 3, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (72, '2026-2027', 4, 'fri', 6, 1, 30, 2, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (73, '2026-2027', 4, 'fri', 7, 25, 10, 2, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (74, '2026-2027', 4, 'fri', 8, 6, 7, 3, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (75, '2026-2027', 4, 'mon', 0, 21, 7, 2, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (76, '2026-2027', 4, 'mon', 1, 7, 36, 3, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (77, '2026-2027', 4, 'mon', 2, 7, 36, 3, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (78, '2026-2027', 4, 'mon', 3, 1, 30, 2, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (79, '2026-2027', 3, 'mon', 4, 8, 15, 3, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (80, '2026-2027', 5, 'mon', 4, 15, 32, 2, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (81, '2026-2027', 4, 'mon', 5, 2, 13, 2, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (82, '2026-2027', 4, 'mon', 6, 6, 7, 3, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (83, '2026-2027', 4, 'mon', 7, 3, 6, 2, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (84, '2026-2027', 4, 'mon', 8, 25, 10, 2, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (85, '2026-2027', 4, 'sat', 0, 21, 7, 2, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (86, '2026-2027', 4, 'sat', 1, 2, 13, 2, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (87, '2026-2027', 4, 'sat', 2, 2, 13, 2, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (88, '2026-2027', 4, 'sat', 3, 3, 6, 2, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (89, '2026-2027', 4, 'sat', 4, 1, 30, 2, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (90, '2026-2027', 4, 'sat', 5, 25, 10, 2, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (91, '2026-2027', 4, 'sat', 6, 7, 36, 3, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (92, '2026-2027', 4, 'sat', 7, 6, 7, 3, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (93, '2026-2027', 3, 'sat', 8, 8, 15, 3, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (94, '2026-2027', 5, 'sat', 8, 15, 32, 2, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (95, '2026-2027', 4, 'thu', 0, 21, 7, 2, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (96, '2026-2027', 4, 'thu', 1, 6, 7, 3, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (97, '2026-2027', 4, 'thu', 2, 6, 7, 3, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (98, '2026-2027', 4, 'thu', 3, 3, 6, 2, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (99, '2026-2027', 4, 'thu', 4, 27, 8, 2, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (100, '2026-2027', 4, 'thu', 5, 2, 13, 2, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (101, '2026-2027', 3, 'thu', 6, 8, 15, 3, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (102, '2026-2027', 5, 'thu', 6, 15, 32, 2, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (103, '2026-2027', 4, 'thu', 7, 7, 36, 3, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (104, '2026-2027', 4, 'thu', 8, 1, 30, 2, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (105, '2026-2027', 4, 'tue', 0, 21, 7, 2, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (106, '2026-2027', 4, 'tue', 1, 25, 10, 2, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (107, '2026-2027', 4, 'tue', 2, 6, 7, 3, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (108, '2026-2027', 4, 'tue', 3, 1, 30, 2, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (109, '2026-2027', 4, 'tue', 4, 2, 13, 2, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (110, '2026-2027', 3, 'tue', 5, 8, 15, 3, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (111, '2026-2027', 5, 'tue', 5, 15, 32, 2, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (112, '2026-2027', 4, 'tue', 6, 7, 36, 3, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (113, '2026-2027', 4, 'tue', 7, 27, 8, 2, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (114, '2026-2027', 4, 'tue', 8, 3, 6, 2, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (115, '2026-2027', 4, 'wed', 0, 21, 7, 2, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (116, '2026-2027', 4, 'wed', 1, 1, 30, 2, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (117, '2026-2027', 4, 'wed', 2, 25, 10, 2, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (118, '2026-2027', 4, 'wed', 3, 2, 13, 2, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (119, '2026-2027', 3, 'wed', 4, 8, 15, 3, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (120, '2026-2027', 5, 'wed', 4, 15, 32, 2, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (121, '2026-2027', 4, 'wed', 5, 27, 8, 2, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (122, '2026-2027', 4, 'wed', 6, 6, 7, 3, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (123, '2026-2027', 4, 'wed', 7, 3, 6, 2, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (124, '2026-2027', 4, 'wed', 8, 7, 36, 3, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (125, '2026-2027', 8, 'fri', 0, 21, 30, 3, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (126, '2026-2027', 8, 'fri', 1, 7, 36, 3, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (127, '2026-2027', 8, 'fri', 2, 7, 36, 4, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (128, '2026-2027', 6, 'fri', 3, 8, 21, 3, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (129, '2026-2027', 10, 'fri', 3, 2, 13, 4, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (130, '2026-2027', 6, 'fri', 4, 8, 21, 3, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (131, '2026-2027', 10, 'fri', 4, 2, 13, 4, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (132, '2026-2027', 8, 'fri', 5, 1, 30, 2, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (133, '2026-2027', 8, 'fri', 6, 25, 10, 3, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (134, '2026-2027', 7, 'fri', 7, 6, 7, 3, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (135, '2026-2027', 9, 'fri', 7, 15, 32, 4, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (136, '2026-2027', 8, 'fri', 8, 3, 6, 2, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (137, '2026-2027', 8, 'mon', 0, 21, 30, 3, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (138, '2026-2027', 8, 'mon', 1, 3, 6, 2, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (139, '2026-2027', 8, 'mon', 2, 3, 6, 2, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (140, '2026-2027', 8, 'mon', 3, 7, 36, 3, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (141, '2026-2027', 8, 'mon', 4, 29, 10, 4, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (142, '2026-2027', 7, 'mon', 5, 6, 7, 3, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (143, '2026-2027', 9, 'mon', 5, 15, 32, 4, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (144, '2026-2027', 8, 'mon', 6, 1, 30, 2, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (145, '2026-2027', 8, 'mon', 7, 1, 30, 3, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (146, '2026-2027', 6, 'mon', 8, 8, 21, 3, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (147, '2026-2027', 10, 'mon', 8, 2, 13, 4, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (148, '2026-2027', 8, 'sat', 0, 21, 30, 3, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (149, '2026-2027', 8, 'sat', 1, 7, 36, 3, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (150, '2026-2027', 8, 'sat', 2, 3, 6, 3, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (151, '2026-2027', 7, 'sat', 3, 6, 7, 3, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (152, '2026-2027', 9, 'sat', 3, 15, 32, 4, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (153, '2026-2027', 7, 'sat', 4, 6, 7, 3, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (154, '2026-2027', 9, 'sat', 4, 15, 32, 4, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (155, '2026-2027', 8, 'sat', 5, 1, 30, 3, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (156, '2026-2027', 6, 'sat', 6, 8, 21, 4, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (157, '2026-2027', 10, 'sat', 6, 2, 13, 2, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (158, '2026-2027', 6, 'sat', 7, 8, 21, 4, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (159, '2026-2027', 10, 'sat', 7, 2, 13, 2, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (160, '2026-2027', 8, 'sat', 8, 14, 18, 4, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (161, '2026-2027', 8, 'thu', 0, 21, 30, 3, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (162, '2026-2027', 6, 'thu', 1, 8, 21, 4, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (163, '2026-2027', 10, 'thu', 1, 2, 13, 2, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (164, '2026-2027', 8, 'thu', 2, 1, 30, 2, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (165, '2026-2027', 8, 'thu', 3, 1, 30, 3, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (166, '2026-2027', 8, 'thu', 4, 3, 6, 3, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (167, '2026-2027', 8, 'thu', 5, 3, 6, 3, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (168, '2026-2027', 8, 'thu', 6, 7, 36, 4, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (169, '2026-2027', 7, 'thu', 7, 6, 7, 4, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (170, '2026-2027', 9, 'thu', 7, 15, 32, 2, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (171, '2026-2027', 8, 'thu', 8, 25, 10, 3, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (172, '2026-2027', 8, 'tue', 0, 21, 30, 3, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (173, '2026-2027', 8, 'tue', 1, 7, 36, 3, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (174, '2026-2027', 8, 'tue', 2, 7, 36, 4, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (175, '2026-2027', 8, 'tue', 3, 25, 10, 3, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (176, '2026-2027', 8, 'tue', 4, 1, 30, 3, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (177, '2026-2027', 6, 'tue', 5, 8, 21, 4, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (178, '2026-2027', 10, 'tue', 5, 2, 13, 5, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (179, '2026-2027', 8, 'tue', 6, 29, 10, 2, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (180, '2026-2027', 8, 'tue', 7, 3, 6, 5, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (181, '2026-2027', 7, 'tue', 8, 6, 7, 3, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (182, '2026-2027', 9, 'tue', 8, 15, 32, 4, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (183, '2026-2027', 8, 'wed', 0, 21, 30, 5, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (184, '2026-2027', 7, 'wed', 1, 6, 7, 3, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (185, '2026-2027', 9, 'wed', 1, 15, 32, 4, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (186, '2026-2027', 7, 'wed', 2, 6, 7, 3, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (187, '2026-2027', 9, 'wed', 2, 15, 32, 4, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (188, '2026-2027', 8, 'wed', 3, 3, 6, 5, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (189, '2026-2027', 8, 'wed', 4, 7, 36, 4, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (190, '2026-2027', 6, 'wed', 5, 8, 21, 3, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (191, '2026-2027', 10, 'wed', 5, 2, 13, 5, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (192, '2026-2027', 8, 'wed', 6, 26, 37, 2, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (193, '2026-2027', 8, 'wed', 7, 25, 10, 5, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (194, '2026-2027', 8, 'wed', 8, 1, 30, 2, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (195, '2026-2027', 13, 'fri', 0, 21, 6, 5, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (196, '2026-2027', 13, 'fri', 1, 27, 8, 5, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (197, '2026-2027', 13, 'fri', 2, 1, 30, 5, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (198, '2026-2027', 12, 'fri', 3, 6, 7, 5, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (199, '2026-2027', 14, 'fri', 3, 15, 32, 6, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (200, '2026-2027', 12, 'fri', 4, 6, 7, 6, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (201, '2026-2027', 14, 'fri', 4, 15, 32, 5, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (202, '2026-2027', 11, 'fri', 5, 8, 21, 4, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (203, '2026-2027', 15, 'fri', 5, 2, 13, 5, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (204, '2026-2027', 13, 'fri', 6, 3, 6, 5, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (205, '2026-2027', 13, 'fri', 7, 7, 36, 6, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (206, '2026-2027', 13, 'fri', 8, 26, 37, 5, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (207, '2026-2027', 13, 'mon', 0, 21, 6, 5, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (208, '2026-2027', 13, 'mon', 1, 1, 30, 5, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (209, '2026-2027', 13, 'mon', 2, 1, 30, 5, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (210, '2026-2027', 11, 'mon', 3, 8, 21, 4, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (211, '2026-2027', 15, 'mon', 3, 2, 13, 5, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (212, '2026-2027', 13, 'mon', 4, 27, 8, 5, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (213, '2026-2027', 13, 'mon', 5, 7, 36, 6, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (214, '2026-2027', 13, 'mon', 6, 3, 6, 5, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (215, '2026-2027', 12, 'mon', 7, 6, 7, 4, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (216, '2026-2027', 14, 'mon', 7, 15, 32, 6, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (217, '2026-2027', 12, 'mon', 8, 6, 7, 6, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (218, '2026-2027', 14, 'mon', 8, 15, 32, 5, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (219, '2026-2027', 13, 'sat', 0, 21, 6, 5, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (220, '2026-2027', 13, 'sat', 1, 14, 18, 5, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (221, '2026-2027', 12, 'sat', 2, 6, 7, 4, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (222, '2026-2027', 14, 'sat', 2, 15, 32, 6, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (223, '2026-2027', 11, 'sat', 3, 8, 21, 6, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (224, '2026-2027', 15, 'sat', 3, 2, 13, 5, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (225, '2026-2027', 13, 'sat', 4, 7, 36, 6, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (226, '2026-2027', 13, 'sat', 5, 7, 36, 4, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (227, '2026-2027', 13, 'sat', 6, 3, 6, 5, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (228, '2026-2027', 13, 'sat', 7, 1, 30, 5, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (229, '2026-2027', 13, 'sat', 8, 27, 8, 5, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (230, '2026-2027', 13, 'thu', 0, 21, 6, 5, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (231, '2026-2027', 13, 'thu', 1, 7, 36, 6, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (232, '2026-2027', 13, 'thu', 2, 7, 36, 4, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (233, '2026-2027', 13, 'thu', 3, 27, 8, 5, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (234, '2026-2027', 13, 'thu', 4, 1, 30, 5, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (235, '2026-2027', 12, 'thu', 5, 6, 7, 4, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (236, '2026-2027', 14, 'thu', 5, 15, 32, 6, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (237, '2026-2027', 13, 'thu', 6, 3, 6, 5, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (238, '2026-2027', 11, 'thu', 7, 8, 21, 6, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (239, '2026-2027', 15, 'thu', 7, 2, 13, 5, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (240, '2026-2027', 11, 'thu', 8, 8, 21, 4, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (241, '2026-2027', 15, 'thu', 8, 2, 13, 5, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (242, '2026-2027', 13, 'tue', 0, 21, 6, 5, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (243, '2026-2027', 13, 'tue', 1, 27, 8, 5, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (244, '2026-2027', 13, 'tue', 2, 3, 6, 2, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (245, '2026-2027', 13, 'tue', 3, 3, 6, 5, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (246, '2026-2027', 12, 'tue', 4, 6, 7, 4, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (247, '2026-2027', 14, 'tue', 4, 15, 32, 6, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (248, '2026-2027', 13, 'tue', 5, 7, 36, 6, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (249, '2026-2027', 13, 'tue', 6, 1, 30, 5, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (250, '2026-2027', 11, 'tue', 7, 8, 21, 3, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (251, '2026-2027', 15, 'tue', 7, 2, 13, 4, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (252, '2026-2027', 11, 'tue', 8, 8, 21, 6, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (253, '2026-2027', 15, 'tue', 8, 2, 13, 5, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (254, '2026-2027', 13, 'wed', 0, 21, 6, 3, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (255, '2026-2027', 13, 'wed', 1, 7, 36, 6, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (256, '2026-2027', 13, 'wed', 2, 1, 30, 5, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (257, '2026-2027', 13, 'wed', 3, 1, 30, 3, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (258, '2026-2027', 13, 'wed', 4, 3, 6, 5, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (259, '2026-2027', 13, 'wed', 5, 3, 6, 4, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (260, '2026-2027', 13, 'wed', 6, 27, 8, 5, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (261, '2026-2027', 12, 'wed', 7, 6, 7, 3, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (262, '2026-2027', 14, 'wed', 7, 15, 32, 4, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (263, '2026-2027', 11, 'wed', 8, 8, 21, 4, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (264, '2026-2027', 15, 'wed', 8, 2, 13, 5, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (265, '2026-2027', 16, 'fri', 0, 21, 29, 4, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (266, '2026-2027', 16, 'fri', 1, 3, 26, 4, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (267, '2026-2027', 16, 'fri', 2, 15, 23, 6, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (268, '2026-2027', 16, 'fri', 3, 4, 27, 7, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (269, '2026-2027', 16, 'fri', 4, 14, 18, 7, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (270, '2026-2027', 16, 'fri', 5, 25, 26, 7, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (271, '2026-2027', 16, 'fri', 6, 28, 29, 7, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (272, '2026-2027', 16, 'fri', 7, 1, 24, 5, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (273, '2026-2027', 16, 'fri', 8, 2, 38, 7, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (274, '2026-2027', 16, 'mon', 0, 21, 29, 7, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (275, '2026-2027', 16, 'mon', 1, 25, 26, 7, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (276, '2026-2027', 16, 'mon', 2, 28, 29, 7, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (277, '2026-2027', 16, 'mon', 3, 19, 24, 7, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (278, '2026-2027', 16, 'mon', 4, 18, 34, 7, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (279, '2026-2027', 16, 'mon', 5, 3, 26, 5, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (280, '2026-2027', 16, 'mon', 6, 1, 24, 7, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (281, '2026-2027', 16, 'mon', 7, 4, 27, 5, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (282, '2026-2027', 16, 'mon', 8, 2, 38, 7, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (283, '2026-2027', 16, 'sat', 0, 21, 29, 7, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (284, '2026-2027', 16, 'sat', 1, 1, 24, 7, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (285, '2026-2027', 16, 'sat', 2, 4, 27, 5, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (286, '2026-2027', 16, 'sat', 3, 28, 29, 7, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (287, '2026-2027', 16, 'sat', 4, 15, 23, 5, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (288, '2026-2027', 16, 'sat', 5, 26, 3, 5, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (289, '2026-2027', 16, 'sat', 6, 3, 26, 7, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (290, '2026-2027', 16, 'sat', 7, 2, 38, 7, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (291, '2026-2027', 16, 'sat', 8, 2, 38, 7, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (292, '2026-2027', 16, 'thu', 0, 21, 29, 7, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (293, '2026-2027', 16, 'thu', 1, 28, 29, 5, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (294, '2026-2027', 16, 'thu', 2, 1, 24, 5, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (295, '2026-2027', 16, 'thu', 3, 4, 27, 7, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (296, '2026-2027', 16, 'thu', 4, 3, 26, 7, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (297, '2026-2027', 16, 'thu', 5, 15, 23, 5, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (298, '2026-2027', 16, 'thu', 6, 18, 34, 7, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (299, '2026-2027', 16, 'thu', 7, 25, 26, 7, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (300, '2026-2027', 16, 'thu', 8, 2, 38, 7, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (301, '2026-2027', 16, 'tue', 0, 21, 29, 7, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (302, '2026-2027', 16, 'tue', 1, 25, 26, 7, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (303, '2026-2027', 16, 'tue', 2, 1, 24, 5, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (304, '2026-2027', 16, 'tue', 3, 1, 24, 7, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (305, '2026-2027', 16, 'tue', 4, 3, 26, 5, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (306, '2026-2027', 16, 'tue', 5, 26, 3, 7, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (307, '2026-2027', 16, 'tue', 6, 15, 23, 4, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (308, '2026-2027', 16, 'tue', 7, 4, 27, 7, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (309, '2026-2027', 16, 'tue', 8, 2, 38, 7, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (310, '2026-2027', 16, 'wed', 0, 21, 29, 7, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (311, '2026-2027', 16, 'wed', 1, 2, 38, 5, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (312, '2026-2027', 16, 'wed', 2, 1, 24, 7, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (313, '2026-2027', 16, 'wed', 3, 15, 23, 4, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (314, '2026-2027', 16, 'wed', 4, 4, 27, 7, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (315, '2026-2027', 16, 'wed', 5, 28, 29, 7, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (316, '2026-2027', 16, 'wed', 6, 25, 26, 7, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (317, '2026-2027', 16, 'wed', 7, 3, 26, 7, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (318, '2026-2027', 16, 'wed', 8, 3, 26, 7, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (319, '2026-2027', 17, 'fri', 0, 21, 23, 7, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (320, '2026-2027', 17, 'fri', 1, 25, 16, 7, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (321, '2026-2027', 17, 'fri', 2, 1, 25, 7, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (322, '2026-2027', 17, 'fri', 3, 1, 25, 8, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (323, '2026-2027', 17, 'fri', 4, 28, 8, 8, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (324, '2026-2027', 17, 'fri', 5, 26, 3, 8, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (325, '2026-2027', 17, 'fri', 6, 2, 22, 8, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (326, '2026-2027', 17, 'fri', 7, 4, 27, 7, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (327, '2026-2027', 17, 'fri', 8, 3, 26, 8, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (328, '2026-2027', 17, 'mon', 0, 21, 23, 8, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (329, '2026-2027', 17, 'mon', 1, 25, 16, 8, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (330, '2026-2027', 17, 'mon', 2, 20, 4, 8, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (331, '2026-2027', 17, 'mon', 3, 2, 22, 8, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (332, '2026-2027', 17, 'mon', 4, 3, 26, 8, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (333, '2026-2027', 17, 'mon', 5, 28, 8, 7, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (334, '2026-2027', 17, 'mon', 6, 15, 23, 4, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (335, '2026-2027', 17, 'mon', 7, 1, 25, 7, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (336, '2026-2027', 17, 'mon', 8, 4, 27, 8, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (337, '2026-2027', 17, 'sat', 0, 21, 23, 8, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (338, '2026-2027', 17, 'sat', 1, 25, 16, 8, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (339, '2026-2027', 17, 'sat', 2, 2, 22, 7, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (340, '2026-2027', 17, 'sat', 3, 19, 24, 8, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (341, '2026-2027', 17, 'sat', 4, 3, 26, 7, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (342, '2026-2027', 17, 'sat', 5, 1, 25, 7, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (343, '2026-2027', 17, 'sat', 6, 4, 27, 8, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (344, '2026-2027', 17, 'sat', 7, 15, 23, 6, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (345, '2026-2027', 17, 'sat', 8, 26, 3, 8, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (346, '2026-2027', 17, 'thu', 0, 21, 23, 8, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (347, '2026-2027', 17, 'thu', 1, 28, 8, 7, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (348, '2026-2027', 17, 'thu', 2, 1, 25, 7, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (349, '2026-2027', 17, 'thu', 3, 14, 18, 8, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (350, '2026-2027', 17, 'thu', 4, 2, 22, 8, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (351, '2026-2027', 17, 'thu', 5, 25, 16, 7, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (352, '2026-2027', 17, 'thu', 6, 3, 26, 8, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (353, '2026-2027', 17, 'thu', 7, 15, 23, 8, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (354, '2026-2027', 17, 'thu', 8, 4, 27, 8, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (355, '2026-2027', 17, 'tue', 0, 21, 23, 8, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (356, '2026-2027', 17, 'tue', 1, 4, 27, 8, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (357, '2026-2027', 17, 'tue', 2, 1, 25, 7, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (358, '2026-2027', 17, 'tue', 3, 3, 26, 8, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (359, '2026-2027', 17, 'tue', 4, 2, 22, 7, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (360, '2026-2027', 17, 'tue', 5, 2, 22, 8, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (361, '2026-2027', 17, 'tue', 6, 25, 16, 7, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (362, '2026-2027', 17, 'tue', 7, 15, 23, 6, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (363, '2026-2027', 17, 'tue', 8, 28, 8, 8, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (364, '2026-2027', 17, 'wed', 0, 21, 23, 8, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (365, '2026-2027', 17, 'wed', 1, 15, 23, 7, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (366, '2026-2027', 17, 'wed', 2, 3, 26, 8, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (367, '2026-2027', 17, 'wed', 3, 3, 26, 7, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (368, '2026-2027', 17, 'wed', 4, 2, 22, 8, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (369, '2026-2027', 17, 'wed', 5, 1, 25, 8, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (370, '2026-2027', 17, 'wed', 6, 20, 4, 8, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (371, '2026-2027', 17, 'wed', 7, 4, 27, 8, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (372, '2026-2027', 17, 'wed', 8, 28, 8, 8, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (373, '2026-2027', 18, 'fri', 0, 21, 25, 8, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (374, '2026-2027', 18, 'fri', 1, 2, 22, 8, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (375, '2026-2027', 18, 'fri', 2, 25, 16, 8, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (376, '2026-2027', 18, 'fri', 3, 3, 26, 9, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (377, '2026-2027', 18, 'fri', 4, 3, 26, 9, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (378, '2026-2027', 18, 'fri', 5, 4, 27, 9, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (379, '2026-2027', 18, 'fri', 6, 1, 25, 9, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (380, '2026-2027', 18, 'fri', 7, 1, 25, 8, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (381, '2026-2027', 18, 'fri', 8, 28, 8, 9, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (382, '2026-2027', 18, 'mon', 0, 21, 25, 9, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (383, '2026-2027', 18, 'mon', 1, 1, 25, 9, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (384, '2026-2027', 18, 'mon', 2, 4, 27, 9, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (385, '2026-2027', 18, 'mon', 3, 25, 16, 9, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (386, '2026-2027', 18, 'mon', 4, 2, 22, 9, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (387, '2026-2027', 18, 'mon', 5, 15, 23, 8, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (388, '2026-2027', 18, 'mon', 6, 3, 26, 8, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (389, '2026-2027', 18, 'mon', 7, 14, 18, 8, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (390, '2026-2027', 18, 'mon', 8, 28, 8, 9, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (391, '2026-2027', 18, 'sat', 0, 21, 25, 9, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (392, '2026-2027', 18, 'sat', 1, 2, 22, 9, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (393, '2026-2027', 18, 'sat', 2, 15, 23, 8, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (394, '2026-2027', 18, 'sat', 3, 4, 27, 9, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (395, '2026-2027', 18, 'sat', 4, 19, 24, 8, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (396, '2026-2027', 18, 'sat', 5, 3, 26, 8, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (397, '2026-2027', 18, 'sat', 6, 25, 16, 9, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (398, '2026-2027', 18, 'sat', 7, 28, 8, 8, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (399, '2026-2027', 18, 'sat', 8, 1, 25, 9, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (400, '2026-2027', 18, 'thu', 0, 21, 25, 9, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (401, '2026-2027', 18, 'thu', 1, 20, 4, 8, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (402, '2026-2027', 18, 'thu', 2, 15, 23, 6, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (403, '2026-2027', 18, 'thu', 3, 26, 3, 9, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (404, '2026-2027', 18, 'thu', 4, 1, 25, 9, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (405, '2026-2027', 18, 'thu', 5, 3, 26, 8, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (406, '2026-2027', 18, 'thu', 6, 4, 27, 9, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (407, '2026-2027', 18, 'thu', 7, 2, 22, 9, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (408, '2026-2027', 18, 'thu', 8, 2, 22, 9, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (409, '2026-2027', 18, 'tue', 0, 21, 25, 9, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (410, '2026-2027', 18, 'tue', 1, 1, 25, 9, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (411, '2026-2027', 18, 'tue', 2, 2, 22, 8, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (412, '2026-2027', 18, 'tue', 3, 4, 27, 9, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (413, '2026-2027', 18, 'tue', 4, 25, 16, 8, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (414, '2026-2027', 18, 'tue', 5, 15, 23, 9, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (415, '2026-2027', 18, 'tue', 6, 28, 8, 8, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (416, '2026-2027', 18, 'tue', 7, 26, 3, 8, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (417, '2026-2027', 18, 'tue', 8, 3, 26, 9, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (418, '2026-2027', 18, 'wed', 0, 21, 25, 9, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (419, '2026-2027', 18, 'wed', 1, 2, 22, 8, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (420, '2026-2027', 18, 'wed', 2, 4, 27, 9, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (421, '2026-2027', 18, 'wed', 3, 28, 8, 8, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (422, '2026-2027', 18, 'wed', 4, 20, 4, 9, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (423, '2026-2027', 18, 'wed', 5, 3, 26, 9, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (424, '2026-2027', 18, 'wed', 6, 1, 25, 9, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (425, '2026-2027', 18, 'wed', 7, 15, 23, 6, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (426, '2026-2027', 18, 'wed', 8, 25, 16, 9, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (427, '2026-2027', 19, 'fri', 0, 21, 22, 9, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (428, '2026-2027', 19, 'fri', 1, 3, 28, 9, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (429, '2026-2027', 19, 'fri', 2, 20, 4, 9, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (430, '2026-2027', 19, 'fri', 3, 25, 17, 10, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (431, '2026-2027', 19, 'fri', 4, 4, 27, 10, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (432, '2026-2027', 19, 'fri', 5, 1, 25, 10, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (433, '2026-2027', 19, 'fri', 6, 15, 23, 4, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (434, '2026-2027', 19, 'fri', 7, 2, 22, 9, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (435, '2026-2027', 19, 'fri', 8, 2, 22, 10, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (436, '2026-2027', 19, 'mon', 0, 21, 22, 10, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (437, '2026-2027', 19, 'mon', 1, 14, 18, 10, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (438, '2026-2027', 19, 'mon', 2, 28, 8, 10, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (439, '2026-2027', 19, 'mon', 3, 3, 28, 10, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (440, '2026-2027', 19, 'mon', 4, 1, 25, 10, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (441, '2026-2027', 19, 'mon', 5, 4, 27, 9, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (442, '2026-2027', 19, 'mon', 6, 26, 37, 9, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (443, '2026-2027', 19, 'mon', 7, 25, 17, 9, '2026-03-26 12:19:25', '2026-03-26 12:19:25');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (444, '2026-2027', 19, 'mon', 8, 2, 22, 10, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (445, '2026-2027', 19, 'sat', 0, 21, 22, 10, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (446, '2026-2027', 19, 'sat', 1, 1, 25, 10, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (447, '2026-2027', 19, 'sat', 2, 28, 8, 9, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (448, '2026-2027', 19, 'sat', 3, 15, 23, 10, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (449, '2026-2027', 19, 'sat', 4, 3, 28, 9, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (450, '2026-2027', 19, 'sat', 5, 3, 28, 9, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (451, '2026-2027', 19, 'sat', 6, 2, 22, 10, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (452, '2026-2027', 19, 'sat', 7, 25, 17, 9, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (453, '2026-2027', 19, 'sat', 8, 4, 27, 10, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (454, '2026-2027', 19, 'thu', 0, 21, 22, 10, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (455, '2026-2027', 19, 'thu', 1, 1, 25, 9, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (456, '2026-2027', 19, 'thu', 2, 28, 8, 8, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (457, '2026-2027', 19, 'thu', 3, 19, 24, 10, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (458, '2026-2027', 19, 'thu', 4, 15, 23, 4, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (459, '2026-2027', 19, 'thu', 5, 4, 27, 9, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (460, '2026-2027', 19, 'thu', 6, 2, 22, 10, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (461, '2026-2027', 19, 'thu', 7, 3, 28, 10, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (462, '2026-2027', 19, 'thu', 8, 20, 4, 10, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (463, '2026-2027', 19, 'tue', 0, 21, 22, 10, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (464, '2026-2027', 19, 'tue', 1, 25, 17, 10, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (465, '2026-2027', 19, 'tue', 2, 15, 23, 6, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (466, '2026-2027', 19, 'tue', 3, 2, 22, 10, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (467, '2026-2027', 19, 'tue', 4, 28, 8, 9, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (468, '2026-2027', 19, 'tue', 5, 3, 28, 10, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (469, '2026-2027', 19, 'tue', 6, 4, 27, 9, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (470, '2026-2027', 19, 'tue', 7, 1, 25, 9, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (471, '2026-2027', 19, 'tue', 8, 1, 25, 10, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (472, '2026-2027', 19, 'wed', 0, 21, 22, 10, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (473, '2026-2027', 19, 'wed', 1, 28, 8, 9, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (474, '2026-2027', 19, 'wed', 2, 2, 22, 10, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (475, '2026-2027', 19, 'wed', 3, 26, 37, 9, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (476, '2026-2027', 19, 'wed', 4, 15, 23, 6, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (477, '2026-2027', 19, 'wed', 5, 3, 28, 10, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (478, '2026-2027', 19, 'wed', 6, 25, 17, 10, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (479, '2026-2027', 19, 'wed', 7, 1, 25, 9, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (480, '2026-2027', 19, 'wed', 8, 4, 27, 10, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (481, '2026-2027', 20, 'fri', 0, 21, 27, 10, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (482, '2026-2027', 20, 'fri', 1, 4, 27, 10, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (483, '2026-2027', 20, 'fri', 2, 28, 8, 10, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (484, '2026-2027', 20, 'fri', 3, 3, 28, 11, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (485, '2026-2027', 20, 'fri', 4, 15, 23, 11, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (486, '2026-2027', 20, 'fri', 5, 2, 22, 11, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (487, '2026-2027', 20, 'fri', 6, 25, 17, 10, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (488, '2026-2027', 20, 'fri', 7, 20, 4, 10, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (489, '2026-2027', 20, 'fri', 8, 1, 25, 11, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (490, '2026-2027', 20, 'mon', 0, 21, 27, 11, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (491, '2026-2027', 20, 'mon', 1, 28, 8, 11, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (492, '2026-2027', 20, 'mon', 2, 15, 23, 4, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (493, '2026-2027', 20, 'mon', 3, 4, 27, 11, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (494, '2026-2027', 20, 'mon', 4, 3, 28, 11, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (495, '2026-2027', 20, 'mon', 5, 2, 22, 10, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (496, '2026-2027', 20, 'mon', 6, 1, 25, 10, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (497, '2026-2027', 20, 'mon', 7, 26, 37, 10, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (498, '2026-2027', 20, 'mon', 8, 25, 17, 11, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (499, '2026-2027', 20, 'sat', 0, 21, 27, 11, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (500, '2026-2027', 20, 'sat', 1, 3, 28, 11, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (501, '2026-2027', 20, 'sat', 2, 25, 17, 10, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (502, '2026-2027', 20, 'sat', 3, 20, 4, 11, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (503, '2026-2027', 20, 'sat', 4, 1, 25, 10, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (504, '2026-2027', 20, 'sat', 5, 28, 8, 10, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (505, '2026-2027', 20, 'sat', 6, 15, 23, 6, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (506, '2026-2027', 20, 'sat', 7, 4, 27, 10, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (507, '2026-2027', 20, 'sat', 8, 2, 22, 11, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (508, '2026-2027', 20, 'thu', 0, 21, 27, 11, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (509, '2026-2027', 20, 'thu', 1, 15, 23, 10, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (510, '2026-2027', 20, 'thu', 2, 2, 22, 9, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (511, '2026-2027', 20, 'thu', 3, 3, 28, 11, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (512, '2026-2027', 20, 'thu', 4, 25, 17, 10, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (513, '2026-2027', 20, 'thu', 5, 1, 25, 10, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (514, '2026-2027', 20, 'thu', 6, 26, 37, 11, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (515, '2026-2027', 20, 'thu', 7, 4, 27, 11, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (516, '2026-2027', 20, 'thu', 8, 28, 8, 11, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (517, '2026-2027', 20, 'tue', 0, 21, 27, 11, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (518, '2026-2027', 20, 'tue', 1, 15, 23, 4, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (519, '2026-2027', 20, 'tue', 2, 4, 27, 9, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (520, '2026-2027', 20, 'tue', 3, 1, 25, 11, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (521, '2026-2027', 20, 'tue', 4, 25, 17, 10, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (522, '2026-2027', 20, 'tue', 5, 14, 18, 11, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (523, '2026-2027', 20, 'tue', 6, 3, 28, 10, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (524, '2026-2027', 20, 'tue', 7, 3, 28, 10, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (525, '2026-2027', 20, 'tue', 8, 2, 22, 11, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (526, '2026-2027', 20, 'wed', 0, 21, 27, 11, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (527, '2026-2027', 20, 'wed', 1, 1, 25, 10, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (528, '2026-2027', 20, 'wed', 2, 1, 25, 11, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (529, '2026-2027', 20, 'wed', 3, 3, 28, 10, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (530, '2026-2027', 20, 'wed', 4, 28, 8, 10, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (531, '2026-2027', 20, 'wed', 5, 19, 24, 11, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (532, '2026-2027', 20, 'wed', 6, 4, 27, 11, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (533, '2026-2027', 20, 'wed', 7, 2, 22, 10, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (534, '2026-2027', 20, 'wed', 8, 2, 22, 11, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (535, '2026-2027', 21, 'fri', 0, 21, 15, 11, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (536, '2026-2027', 21, 'fri', 1, 1, 25, 11, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (537, '2026-2027', 21, 'fri', 2, 2, 22, 11, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (538, '2026-2027', 21, 'fri', 3, 2, 22, 12, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (539, '2026-2027', 21, 'fri', 4, 15, 5, 12, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (540, '2026-2027', 21, 'fri', 5, 26, 37, 12, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (541, '2026-2027', 21, 'fri', 6, 3, 28, 11, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (542, '2026-2027', 21, 'fri', 7, 28, 29, 11, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (543, '2026-2027', 21, 'fri', 8, 4, 15, 12, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (544, '2026-2027', 21, 'mon', 0, 21, 15, 12, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (545, '2026-2027', 21, 'mon', 1, 2, 22, 12, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (546, '2026-2027', 21, 'mon', 2, 1, 25, 11, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (547, '2026-2027', 21, 'mon', 3, 20, 4, 12, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (548, '2026-2027', 21, 'mon', 4, 25, 17, 12, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (549, '2026-2027', 21, 'mon', 5, 4, 15, 11, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (550, '2026-2027', 21, 'mon', 6, 3, 28, 11, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (551, '2026-2027', 21, 'mon', 7, 3, 28, 11, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (552, '2026-2027', 21, 'mon', 8, 28, 29, 12, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (553, '2026-2027', 21, 'sat', 0, 21, 15, 12, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (554, '2026-2027', 21, 'sat', 1, 20, 4, 12, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (555, '2026-2027', 21, 'sat', 2, 4, 15, 11, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (556, '2026-2027', 21, 'sat', 3, 3, 28, 12, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (557, '2026-2027', 21, 'sat', 4, 2, 22, 11, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (558, '2026-2027', 21, 'sat', 5, 25, 17, 11, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (559, '2026-2027', 21, 'sat', 6, 1, 25, 11, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (560, '2026-2027', 21, 'sat', 7, 15, 5, 11, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (561, '2026-2027', 21, 'sat', 8, 28, 29, 12, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (562, '2026-2027', 21, 'thu', 0, 21, 15, 12, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (563, '2026-2027', 21, 'thu', 1, 15, 5, 11, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (564, '2026-2027', 21, 'thu', 2, 3, 28, 10, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (565, '2026-2027', 21, 'thu', 3, 2, 22, 12, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (566, '2026-2027', 21, 'thu', 4, 19, 24, 11, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (567, '2026-2027', 21, 'thu', 5, 25, 17, 11, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (568, '2026-2027', 21, 'thu', 6, 1, 25, 12, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (569, '2026-2027', 21, 'thu', 7, 1, 25, 12, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (570, '2026-2027', 21, 'thu', 8, 4, 15, 12, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (571, '2026-2027', 21, 'tue', 0, 21, 15, 12, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (572, '2026-2027', 21, 'tue', 1, 4, 15, 11, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (573, '2026-2027', 21, 'tue', 2, 28, 29, 10, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (574, '2026-2027', 21, 'tue', 3, 14, 18, 12, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (575, '2026-2027', 21, 'tue', 4, 3, 28, 11, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (576, '2026-2027', 21, 'tue', 5, 15, 5, 12, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (577, '2026-2027', 21, 'tue', 6, 1, 25, 11, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (578, '2026-2027', 21, 'tue', 7, 2, 22, 11, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (579, '2026-2027', 21, 'tue', 8, 25, 17, 12, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (580, '2026-2027', 21, 'wed', 0, 21, 15, 12, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (581, '2026-2027', 21, 'wed', 1, 15, 5, 11, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (582, '2026-2027', 21, 'wed', 2, 4, 15, 12, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (583, '2026-2027', 21, 'wed', 3, 25, 17, 11, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (584, '2026-2027', 21, 'wed', 4, 1, 25, 11, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (585, '2026-2027', 21, 'wed', 5, 26, 37, 12, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (586, '2026-2027', 21, 'wed', 6, 2, 22, 12, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (587, '2026-2027', 21, 'wed', 7, 28, 29, 11, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (588, '2026-2027', 21, 'wed', 8, 3, 28, 12, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (589, '2026-2027', 22, 'fri', 0, 21, 21, 12, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (590, '2026-2027', 22, 'fri', 1, 15, 5, 6, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (591, '2026-2027', 22, 'fri', 2, 25, 17, 12, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (592, '2026-2027', 22, 'fri', 3, 1, 19, 13, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (593, '2026-2027', 22, 'fri', 4, 2, 14, 13, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (594, '2026-2027', 22, 'fri', 5, 3, 20, 13, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (595, '2026-2027', 22, 'fri', 6, 24, 9, 12, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (596, '2026-2027', 22, 'fri', 7, 22, 17, 12, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (597, '2026-2027', 22, 'fri', 8, 4, 21, 13, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (598, '2026-2027', 22, 'mon', 0, 21, 21, 13, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (599, '2026-2027', 22, 'mon', 1, 24, 9, 13, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (600, '2026-2027', 22, 'mon', 2, 25, 17, 12, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (601, '2026-2027', 22, 'mon', 3, 2, 14, 13, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (602, '2026-2027', 22, 'mon', 4, 2, 14, 13, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (603, '2026-2027', 22, 'mon', 5, 15, 5, 12, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (604, '2026-2027', 22, 'mon', 6, 3, 20, 12, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (605, '2026-2027', 22, 'mon', 7, 4, 21, 12, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (606, '2026-2027', 22, 'mon', 8, 1, 19, 13, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (607, '2026-2027', 22, 'sat', 0, 21, 21, 13, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (608, '2026-2027', 22, 'sat', 1, 24, 9, 13, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (609, '2026-2027', 22, 'sat', 2, 1, 19, 12, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (610, '2026-2027', 22, 'sat', 3, 1, 19, 13, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (611, '2026-2027', 22, 'sat', 4, 25, 17, 12, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (612, '2026-2027', 22, 'sat', 5, 4, 21, 12, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (613, '2026-2027', 22, 'sat', 6, 3, 20, 12, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (614, '2026-2027', 22, 'sat', 7, 20, 4, 12, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (615, '2026-2027', 22, 'sat', 8, 2, 14, 13, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (616, '2026-2027', 22, 'thu', 0, 21, 21, 13, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (617, '2026-2027', 22, 'thu', 1, 1, 19, 12, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (618, '2026-2027', 22, 'thu', 2, 22, 17, 11, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (619, '2026-2027', 22, 'thu', 3, 2, 14, 13, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (620, '2026-2027', 22, 'thu', 4, 15, 5, 6, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (621, '2026-2027', 22, 'thu', 5, 3, 20, 12, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (622, '2026-2027', 22, 'thu', 6, 4, 21, 13, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (623, '2026-2027', 22, 'thu', 7, 24, 9, 13, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (624, '2026-2027', 22, 'thu', 8, 26, 37, 13, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (625, '2026-2027', 22, 'tue', 0, 21, 21, 13, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (626, '2026-2027', 22, 'tue', 1, 24, 9, 12, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (627, '2026-2027', 22, 'tue', 2, 4, 21, 11, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (628, '2026-2027', 22, 'tue', 3, 20, 4, 13, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (629, '2026-2027', 22, 'tue', 4, 2, 14, 12, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (630, '2026-2027', 22, 'tue', 5, 22, 17, 13, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (631, '2026-2027', 22, 'tue', 6, 1, 19, 12, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (632, '2026-2027', 22, 'tue', 7, 3, 20, 12, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (633, '2026-2027', 22, 'tue', 8, 15, 5, 13, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (634, '2026-2027', 22, 'wed', 0, 21, 21, 13, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (635, '2026-2027', 22, 'wed', 1, 25, 17, 12, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (636, '2026-2027', 22, 'wed', 2, 15, 5, 6, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (637, '2026-2027', 22, 'wed', 3, 3, 20, 12, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (638, '2026-2027', 22, 'wed', 4, 24, 9, 12, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (639, '2026-2027', 22, 'wed', 5, 22, 17, 13, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (640, '2026-2027', 22, 'wed', 6, 1, 19, 13, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (641, '2026-2027', 22, 'wed', 7, 4, 21, 12, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (642, '2026-2027', 22, 'wed', 8, 2, 14, 13, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (643, '2026-2027', 23, 'fri', 0, 21, 14, 13, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (644, '2026-2027', 23, 'fri', 1, 4, 21, 12, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (645, '2026-2027', 23, 'fri', 2, 24, 9, 13, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (646, '2026-2027', 23, 'fri', 3, 2, 14, 14, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (647, '2026-2027', 23, 'fri', 4, 1, 19, 14, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (648, '2026-2027', 23, 'fri', 5, 25, 17, 14, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (649, '2026-2027', 23, 'fri', 6, 26, 37, 13, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (650, '2026-2027', 23, 'fri', 7, 3, 20, 13, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (651, '2026-2027', 23, 'fri', 8, 22, 17, 14, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (652, '2026-2027', 23, 'mon', 0, 21, 14, 14, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (653, '2026-2027', 23, 'mon', 1, 3, 20, 14, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (654, '2026-2027', 23, 'mon', 2, 2, 14, 13, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (655, '2026-2027', 23, 'mon', 3, 25, 17, 14, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (656, '2026-2027', 23, 'mon', 4, 4, 21, 14, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (657, '2026-2027', 23, 'mon', 5, 22, 17, 13, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (658, '2026-2027', 23, 'mon', 6, 15, 5, 6, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (659, '2026-2027', 23, 'mon', 7, 1, 19, 13, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (660, '2026-2027', 23, 'mon', 8, 24, 9, 14, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (661, '2026-2027', 23, 'sat', 0, 21, 14, 14, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (662, '2026-2027', 23, 'sat', 1, 4, 21, 14, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (663, '2026-2027', 23, 'sat', 2, 24, 9, 13, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (664, '2026-2027', 23, 'sat', 3, 22, 17, 14, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (665, '2026-2027', 23, 'sat', 4, 2, 14, 13, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (666, '2026-2027', 23, 'sat', 5, 2, 14, 13, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (667, '2026-2027', 23, 'sat', 6, 15, 5, 13, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (668, '2026-2027', 23, 'sat', 7, 1, 19, 13, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (669, '2026-2027', 23, 'sat', 8, 3, 20, 14, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (670, '2026-2027', 23, 'thu', 0, 21, 14, 14, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (671, '2026-2027', 23, 'thu', 1, 2, 14, 13, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (672, '2026-2027', 23, 'thu', 2, 4, 21, 12, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (673, '2026-2027', 23, 'thu', 3, 20, 4, 14, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (674, '2026-2027', 23, 'thu', 4, 3, 20, 12, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (675, '2026-2027', 23, 'thu', 5, 15, 5, 13, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (676, '2026-2027', 23, 'thu', 6, 1, 19, 14, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (677, '2026-2027', 23, 'thu', 7, 1, 19, 14, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (678, '2026-2027', 23, 'thu', 8, 24, 9, 14, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (679, '2026-2027', 23, 'tue', 0, 21, 14, 14, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (680, '2026-2027', 23, 'tue', 1, 3, 20, 13, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (681, '2026-2027', 23, 'tue', 2, 25, 17, 12, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (682, '2026-2027', 23, 'tue', 3, 1, 19, 14, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (683, '2026-2027', 23, 'tue', 4, 4, 21, 13, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (684, '2026-2027', 23, 'tue', 5, 2, 14, 14, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (685, '2026-2027', 23, 'tue', 6, 24, 9, 13, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (686, '2026-2027', 23, 'tue', 7, 15, 5, 13, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (687, '2026-2027', 23, 'tue', 8, 20, 4, 14, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (688, '2026-2027', 23, 'wed', 0, 21, 14, 14, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (689, '2026-2027', 23, 'wed', 1, 3, 20, 13, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (690, '2026-2027', 23, 'wed', 2, 22, 17, 13, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (691, '2026-2027', 23, 'wed', 3, 24, 9, 13, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (692, '2026-2027', 23, 'wed', 4, 4, 21, 13, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (693, '2026-2027', 23, 'wed', 5, 2, 14, 14, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (694, '2026-2027', 23, 'wed', 6, 15, 5, 4, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (695, '2026-2027', 23, 'wed', 7, 1, 19, 13, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (696, '2026-2027', 23, 'wed', 8, 25, 17, 14, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (697, '2026-2027', 24, 'fri', 0, 21, 9, 14, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (698, '2026-2027', 24, 'fri', 1, 3, 20, 13, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (699, '2026-2027', 24, 'fri', 2, 15, 5, 14, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (700, '2026-2027', 24, 'fri', 3, 25, 10, 15, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (701, '2026-2027', 24, 'fri', 4, 4, 15, 15, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (702, '2026-2027', 24, 'fri', 5, 1, 19, 15, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (703, '2026-2027', 24, 'fri', 6, 2, 14, 14, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (704, '2026-2027', 24, 'fri', 7, 24, 9, 14, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (705, '2026-2027', 24, 'fri', 8, 22, 10, 15, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (706, '2026-2027', 24, 'mon', 0, 21, 9, 15, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (707, '2026-2027', 24, 'mon', 1, 15, 5, 4, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (708, '2026-2027', 24, 'mon', 2, 24, 9, 14, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (709, '2026-2027', 24, 'mon', 3, 22, 10, 15, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (710, '2026-2027', 24, 'mon', 4, 1, 19, 15, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (711, '2026-2027', 24, 'mon', 5, 3, 20, 14, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (712, '2026-2027', 24, 'mon', 6, 4, 15, 13, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (713, '2026-2027', 24, 'mon', 7, 25, 10, 14, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (714, '2026-2027', 24, 'mon', 8, 2, 14, 15, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (715, '2026-2027', 24, 'sat', 0, 21, 9, 15, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (716, '2026-2027', 24, 'sat', 1, 15, 5, 4, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (717, '2026-2027', 24, 'sat', 2, 22, 10, 14, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (718, '2026-2027', 24, 'sat', 3, 2, 14, 15, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (719, '2026-2027', 24, 'sat', 4, 24, 9, 14, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (720, '2026-2027', 24, 'sat', 5, 4, 15, 14, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (721, '2026-2027', 24, 'sat', 6, 25, 10, 14, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (722, '2026-2027', 24, 'sat', 7, 3, 20, 14, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (723, '2026-2027', 24, 'sat', 8, 1, 19, 15, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (724, '2026-2027', 24, 'thu', 0, 21, 9, 15, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (725, '2026-2027', 24, 'thu', 1, 22, 10, 14, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (726, '2026-2027', 24, 'thu', 2, 3, 20, 13, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (727, '2026-2027', 24, 'thu', 3, 24, 9, 15, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (728, '2026-2027', 24, 'thu', 4, 4, 15, 13, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (729, '2026-2027', 24, 'thu', 5, 1, 19, 14, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (730, '2026-2027', 24, 'thu', 6, 15, 5, 6, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (731, '2026-2027', 24, 'thu', 7, 26, 37, 15, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (732, '2026-2027', 24, 'thu', 8, 2, 14, 15, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (733, '2026-2027', 24, 'tue', 0, 21, 9, 15, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (734, '2026-2027', 24, 'tue', 1, 1, 19, 14, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (735, '2026-2027', 24, 'tue', 2, 1, 19, 13, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (736, '2026-2027', 24, 'tue', 3, 4, 15, 15, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (737, '2026-2027', 24, 'tue', 4, 3, 20, 14, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (738, '2026-2027', 24, 'tue', 5, 3, 20, 15, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (739, '2026-2027', 24, 'tue', 6, 2, 14, 14, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (740, '2026-2027', 24, 'tue', 7, 25, 10, 14, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (741, '2026-2027', 24, 'tue', 8, 24, 9, 15, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (742, '2026-2027', 24, 'wed', 0, 21, 9, 15, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (743, '2026-2027', 24, 'wed', 1, 2, 14, 14, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (744, '2026-2027', 24, 'wed', 2, 2, 14, 14, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (745, '2026-2027', 24, 'wed', 3, 22, 10, 14, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (746, '2026-2027', 24, 'wed', 4, 15, 5, 14, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (747, '2026-2027', 24, 'wed', 5, 24, 9, 15, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (748, '2026-2027', 24, 'wed', 6, 3, 20, 14, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (749, '2026-2027', 24, 'wed', 7, 4, 15, 14, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (750, '2026-2027', 24, 'wed', 8, 1, 19, 15, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (751, '2026-2027', 25, 'fri', 0, 21, 19, 15, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (752, '2026-2027', 25, 'fri', 1, 25, 10, 14, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (753, '2026-2027', 25, 'fri', 2, 2, 14, 15, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (754, '2026-2027', 25, 'fri', 3, 24, 9, 16, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (755, '2026-2027', 25, 'fri', 4, 22, 10, 16, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (756, '2026-2027', 25, 'fri', 5, 4, 15, 16, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (757, '2026-2027', 25, 'fri', 6, 3, 20, 15, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (758, '2026-2027', 25, 'fri', 7, 1, 19, 15, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (759, '2026-2027', 25, 'fri', 8, 1, 19, 16, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (760, '2026-2027', 25, 'mon', 0, 21, 19, 16, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (761, '2026-2027', 25, 'mon', 1, 25, 10, 15, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (762, '2026-2027', 25, 'mon', 2, 1, 19, 15, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (763, '2026-2027', 25, 'mon', 3, 3, 20, 16, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (764, '2026-2027', 25, 'mon', 4, 26, 37, 16, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (765, '2026-2027', 25, 'mon', 5, 2, 14, 15, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (766, '2026-2027', 25, 'mon', 6, 24, 9, 14, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (767, '2026-2027', 25, 'mon', 7, 15, 5, 15, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (768, '2026-2027', 25, 'mon', 8, 4, 15, 16, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (769, '2026-2027', 25, 'sat', 0, 21, 19, 16, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (770, '2026-2027', 25, 'sat', 1, 2, 14, 15, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (771, '2026-2027', 25, 'sat', 2, 15, 5, 15, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (772, '2026-2027', 25, 'sat', 3, 3, 20, 16, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (773, '2026-2027', 25, 'sat', 4, 3, 20, 15, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (774, '2026-2027', 25, 'sat', 5, 24, 9, 15, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (775, '2026-2027', 25, 'sat', 6, 1, 19, 15, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (776, '2026-2027', 25, 'sat', 7, 4, 15, 15, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (777, '2026-2027', 25, 'sat', 8, 22, 10, 16, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (778, '2026-2027', 25, 'thu', 0, 21, 19, 16, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (779, '2026-2027', 25, 'thu', 1, 24, 9, 15, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (780, '2026-2027', 25, 'thu', 2, 22, 10, 14, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (781, '2026-2027', 25, 'thu', 3, 15, 5, 4, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (782, '2026-2027', 25, 'thu', 4, 2, 14, 14, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (783, '2026-2027', 25, 'thu', 5, 4, 15, 15, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (784, '2026-2027', 25, 'thu', 6, 3, 20, 15, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (785, '2026-2027', 25, 'thu', 7, 25, 10, 16, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (786, '2026-2027', 25, 'thu', 8, 1, 19, 16, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (787, '2026-2027', 25, 'tue', 0, 21, 19, 16, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (788, '2026-2027', 25, 'tue', 1, 2, 14, 15, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (789, '2026-2027', 25, 'tue', 2, 2, 14, 14, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (790, '2026-2027', 25, 'tue', 3, 15, 5, 4, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (791, '2026-2027', 25, 'tue', 4, 22, 10, 15, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (792, '2026-2027', 25, 'tue', 5, 24, 9, 16, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (793, '2026-2027', 25, 'tue', 6, 3, 20, 15, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (794, '2026-2027', 25, 'tue', 7, 1, 19, 15, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (795, '2026-2027', 25, 'tue', 8, 4, 15, 16, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (796, '2026-2027', 25, 'wed', 0, 21, 19, 16, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (797, '2026-2027', 25, 'wed', 1, 24, 9, 15, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (798, '2026-2027', 25, 'wed', 2, 1, 19, 15, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (799, '2026-2027', 25, 'wed', 3, 2, 14, 15, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (800, '2026-2027', 25, 'wed', 4, 3, 20, 15, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (801, '2026-2027', 25, 'wed', 5, 4, 15, 16, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (802, '2026-2027', 25, 'wed', 6, 22, 10, 15, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (803, '2026-2027', 25, 'wed', 7, 15, 5, 15, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (804, '2026-2027', 25, 'wed', 8, 25, 10, 16, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (805, '2026-2027', 26, 'fri', 0, 21, 20, 16, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (806, '2026-2027', 26, 'fri', 1, 1, 19, 15, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (807, '2026-2027', 26, 'fri', 2, 3, 20, 16, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (808, '2026-2027', 26, 'fri', 3, 3, 20, 17, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (809, '2026-2027', 26, 'fri', 4, 24, 9, 17, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (810, '2026-2027', 26, 'fri', 5, 25, 11, 17, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (811, '2026-2027', 26, 'fri', 6, 4, 21, 16, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (812, '2026-2027', 26, 'fri', 7, 15, 5, 16, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (813, '2026-2027', 26, 'fri', 8, 2, 14, 17, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (814, '2026-2027', 26, 'mon', 0, 21, 20, 17, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (815, '2026-2027', 26, 'mon', 1, 25, 11, 16, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (816, '2026-2027', 26, 'mon', 2, 15, 5, 6, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (817, '2026-2027', 26, 'mon', 3, 14, 18, 17, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (818, '2026-2027', 26, 'mon', 4, 24, 9, 17, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (819, '2026-2027', 26, 'mon', 5, 1, 19, 16, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (820, '2026-2027', 26, 'mon', 6, 4, 21, 15, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (821, '2026-2027', 26, 'mon', 7, 2, 14, 16, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (822, '2026-2027', 26, 'mon', 8, 3, 20, 17, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (823, '2026-2027', 26, 'sat', 0, 21, 20, 17, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (824, '2026-2027', 26, 'sat', 1, 3, 20, 16, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (825, '2026-2027', 26, 'sat', 2, 4, 21, 16, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (826, '2026-2027', 26, 'sat', 3, 15, 5, 17, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (827, '2026-2027', 26, 'sat', 4, 26, 37, 16, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (828, '2026-2027', 26, 'sat', 5, 1, 19, 16, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (829, '2026-2027', 26, 'sat', 6, 24, 9, 16, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (830, '2026-2027', 26, 'sat', 7, 2, 14, 16, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (831, '2026-2027', 26, 'sat', 8, 25, 11, 17, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (832, '2026-2027', 26, 'thu', 0, 21, 20, 17, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (833, '2026-2027', 26, 'thu', 1, 3, 20, 16, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (834, '2026-2027', 26, 'thu', 2, 24, 9, 15, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (835, '2026-2027', 26, 'thu', 3, 1, 19, 16, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (836, '2026-2027', 26, 'thu', 4, 25, 11, 15, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (837, '2026-2027', 26, 'thu', 5, 4, 21, 16, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (838, '2026-2027', 26, 'thu', 6, 2, 14, 16, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (839, '2026-2027', 26, 'thu', 7, 2, 14, 17, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (840, '2026-2027', 26, 'thu', 8, 15, 5, 6, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (841, '2026-2027', 26, 'tue', 0, 21, 20, 17, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (842, '2026-2027', 26, 'tue', 1, 4, 21, 16, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (843, '2026-2027', 26, 'tue', 2, 3, 20, 15, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (844, '2026-2027', 26, 'tue', 3, 24, 9, 16, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (845, '2026-2027', 26, 'tue', 4, 15, 5, 16, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (846, '2026-2027', 26, 'tue', 5, 1, 19, 17, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (847, '2026-2027', 26, 'tue', 6, 25, 11, 16, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (848, '2026-2027', 26, 'tue', 7, 2, 14, 16, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (849, '2026-2027', 26, 'tue', 8, 26, 37, 17, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (850, '2026-2027', 26, 'wed', 0, 21, 20, 17, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (851, '2026-2027', 26, 'wed', 1, 4, 21, 16, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (852, '2026-2027', 26, 'wed', 2, 4, 21, 16, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (853, '2026-2027', 26, 'wed', 3, 1, 19, 16, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (854, '2026-2027', 26, 'wed', 4, 1, 19, 16, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (855, '2026-2027', 26, 'wed', 5, 3, 20, 17, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (856, '2026-2027', 26, 'wed', 6, 2, 14, 16, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (857, '2026-2027', 26, 'wed', 7, 24, 9, 16, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (858, '2026-2027', 26, 'wed', 8, 15, 5, 6, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (859, '2026-2027', 28, 'fri', 0, 21, 36, 17, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (860, '2026-2027', 27, 'fri', 1, 8, 15, 16, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (861, '2026-2027', 29, 'fri', 1, 15, 32, 17, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (862, '2026-2027', 28, 'fri', 2, 3, 6, 17, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (863, '2026-2027', 28, 'fri', 3, 1, 30, 18, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (864, '2026-2027', 28, 'fri', 4, 26, 37, 18, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (865, '2026-2027', 28, 'fri', 5, 6, 7, 6, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (866, '2026-2027', 28, 'fri', 6, 7, 36, 6, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (867, '2026-2027', 28, 'fri', 7, 25, 11, 17, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (868, '2026-2027', 28, 'fri', 8, 2, 13, 18, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (869, '2026-2027', 28, 'mon', 0, 21, 36, 18, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (870, '2026-2027', 27, 'mon', 1, 8, 15, 6, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (871, '2026-2027', 29, 'mon', 1, 15, 32, 17, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (872, '2026-2027', 28, 'mon', 2, 2, 13, 16, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (873, '2026-2027', 28, 'mon', 3, 6, 7, 6, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (874, '2026-2027', 28, 'mon', 4, 3, 6, 18, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (875, '2026-2027', 28, 'mon', 5, 1, 30, 17, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (876, '2026-2027', 28, 'mon', 6, 25, 11, 16, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (877, '2026-2027', 28, 'mon', 7, 7, 36, 17, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (878, '2026-2027', 28, 'mon', 8, 7, 36, 18, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (879, '2026-2027', 28, 'sat', 0, 21, 36, 18, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (880, '2026-2027', 27, 'sat', 1, 8, 15, 6, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (881, '2026-2027', 29, 'sat', 1, 15, 32, 17, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (882, '2026-2027', 28, 'sat', 2, 7, 36, 17, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (883, '2026-2027', 28, 'sat', 3, 1, 30, 18, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (884, '2026-2027', 28, 'sat', 4, 14, 18, 17, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (885, '2026-2027', 28, 'sat', 5, 25, 11, 17, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (886, '2026-2027', 28, 'sat', 6, 6, 7, 17, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (887, '2026-2027', 28, 'sat', 7, 3, 6, 17, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (888, '2026-2027', 28, 'sat', 8, 2, 13, 18, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (889, '2026-2027', 28, 'thu', 0, 21, 36, 18, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (890, '2026-2027', 28, 'thu', 1, 3, 6, 17, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (891, '2026-2027', 28, 'thu', 2, 2, 13, 16, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (892, '2026-2027', 27, 'thu', 3, 8, 15, 6, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (893, '2026-2027', 29, 'thu', 3, 15, 32, 17, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (894, '2026-2027', 28, 'thu', 4, 6, 7, 16, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (895, '2026-2027', 28, 'thu', 5, 1, 30, 17, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (896, '2026-2027', 28, 'thu', 6, 25, 11, 17, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (897, '2026-2027', 28, 'thu', 7, 25, 11, 18, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (898, '2026-2027', 28, 'thu', 8, 7, 36, 17, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (899, '2026-2027', 28, 'tue', 0, 21, 36, 18, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (900, '2026-2027', 28, 'tue', 1, 2, 13, 17, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (901, '2026-2027', 27, 'tue', 2, 8, 15, 16, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (902, '2026-2027', 29, 'tue', 2, 15, 32, 17, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (903, '2026-2027', 28, 'tue', 3, 7, 36, 6, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (904, '2026-2027', 28, 'tue', 4, 3, 6, 17, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (905, '2026-2027', 28, 'tue', 5, 25, 11, 18, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (906, '2026-2027', 28, 'tue', 6, 6, 7, 6, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (907, '2026-2027', 28, 'tue', 7, 6, 7, 17, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (908, '2026-2027', 28, 'tue', 8, 1, 30, 18, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (909, '2026-2027', 28, 'wed', 0, 21, 36, 18, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (910, '2026-2027', 28, 'wed', 1, 3, 6, 17, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (911, '2026-2027', 28, 'wed', 2, 7, 36, 17, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (912, '2026-2027', 28, 'wed', 3, 25, 11, 17, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (913, '2026-2027', 28, 'wed', 4, 6, 7, 17, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (914, '2026-2027', 28, 'wed', 5, 1, 30, 18, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (915, '2026-2027', 28, 'wed', 6, 2, 13, 17, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (916, '2026-2027', 28, 'wed', 7, 2, 13, 17, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (917, '2026-2027', 27, 'wed', 8, 8, 15, 17, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (918, '2026-2027', 29, 'wed', 8, 15, 32, 18, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (919, '2026-2027', 31, 'fri', 0, 21, 13, 18, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (920, '2026-2027', 31, 'fri', 1, 1, 30, 18, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (921, '2026-2027', 31, 'fri', 2, 6, 7, 18, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (922, '2026-2027', 31, 'fri', 3, 3, 6, 19, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (923, '2026-2027', 31, 'fri', 4, 25, 11, 19, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (924, '2026-2027', 31, 'fri', 5, 14, 18, 18, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (925, '2026-2027', 30, 'fri', 6, 8, 15, 17, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (926, '2026-2027', 32, 'fri', 6, 15, 32, 18, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (927, '2026-2027', 31, 'fri', 7, 2, 13, 18, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (928, '2026-2027', 31, 'fri', 8, 7, 36, 4, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (929, '2026-2027', 31, 'mon', 0, 21, 13, 19, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (930, '2026-2027', 31, 'mon', 1, 6, 7, 18, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (931, '2026-2027', 31, 'mon', 2, 6, 7, 17, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (932, '2026-2027', 30, 'mon', 3, 8, 15, 18, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (933, '2026-2027', 32, 'mon', 3, 15, 32, 19, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (934, '2026-2027', 31, 'mon', 4, 25, 11, 19, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (935, '2026-2027', 31, 'mon', 5, 3, 6, 18, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (936, '2026-2027', 31, 'mon', 6, 7, 36, 17, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (937, '2026-2027', 31, 'mon', 7, 2, 13, 18, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (938, '2026-2027', 31, 'mon', 8, 1, 30, 19, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (939, '2026-2027', 31, 'sat', 0, 21, 13, 19, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (940, '2026-2027', 31, 'sat', 1, 1, 30, 18, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (941, '2026-2027', 31, 'sat', 2, 25, 11, 18, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (942, '2026-2027', 31, 'sat', 3, 26, 37, 19, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (943, '2026-2027', 31, 'sat', 4, 3, 6, 18, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (944, '2026-2027', 31, 'sat', 5, 2, 13, 18, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (945, '2026-2027', 30, 'sat', 6, 8, 15, 18, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (946, '2026-2027', 32, 'sat', 6, 15, 32, 19, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (947, '2026-2027', 31, 'sat', 7, 7, 36, 18, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (948, '2026-2027', 31, 'sat', 8, 6, 7, 6, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (949, '2026-2027', 31, 'thu', 0, 21, 13, 19, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (950, '2026-2027', 30, 'thu', 1, 8, 15, 18, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (951, '2026-2027', 32, 'thu', 1, 15, 32, 19, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (952, '2026-2027', 31, 'thu', 2, 25, 11, 17, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (953, '2026-2027', 31, 'thu', 3, 25, 11, 18, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (954, '2026-2027', 31, 'thu', 4, 2, 13, 17, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (955, '2026-2027', 31, 'thu', 5, 7, 36, 18, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (956, '2026-2027', 31, 'thu', 6, 6, 7, 18, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (957, '2026-2027', 31, 'thu', 7, 1, 30, 19, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (958, '2026-2027', 31, 'thu', 8, 3, 6, 18, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (959, '2026-2027', 31, 'tue', 0, 21, 13, 19, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (960, '2026-2027', 31, 'tue', 1, 1, 30, 18, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (961, '2026-2027', 31, 'tue', 2, 2, 13, 18, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (962, '2026-2027', 31, 'tue', 3, 6, 7, 17, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (963, '2026-2027', 31, 'tue', 4, 25, 11, 18, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (964, '2026-2027', 31, 'tue', 5, 3, 6, 19, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (965, '2026-2027', 30, 'tue', 6, 8, 15, 17, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (966, '2026-2027', 32, 'tue', 6, 15, 32, 18, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (967, '2026-2027', 31, 'tue', 7, 7, 36, 18, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (968, '2026-2027', 31, 'tue', 8, 7, 36, 19, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (969, '2026-2027', 31, 'wed', 0, 21, 13, 19, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (970, '2026-2027', 31, 'wed', 1, 2, 13, 18, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (971, '2026-2027', 31, 'wed', 2, 2, 13, 18, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (972, '2026-2027', 30, 'wed', 3, 8, 15, 6, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (973, '2026-2027', 32, 'wed', 3, 15, 32, 18, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (974, '2026-2027', 31, 'wed', 4, 25, 11, 18, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (975, '2026-2027', 31, 'wed', 5, 6, 7, 6, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (976, '2026-2027', 31, 'wed', 6, 1, 30, 18, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (977, '2026-2027', 31, 'wed', 7, 7, 36, 18, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (978, '2026-2027', 31, 'wed', 8, 3, 6, 19, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (979, '2026-2027', 33, 'fri', 0, 21, 1, 19, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (980, '2026-2027', 33, 'fri', 1, 3, 31, 19, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (981, '2026-2027', 33, 'fri', 2, 3, 31, 19, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (982, '2026-2027', 33, 'fri', 3, 1, 1, 20, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (983, '2026-2027', 33, 'fri', 4, 28, 29, 20, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (984, '2026-2027', 33, 'fri', 5, 2, 38, 19, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (985, '2026-2027', 33, 'fri', 6, 25, 16, 19, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (986, '2026-2027', 33, 'mon', 0, 21, 1, 20, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (987, '2026-2027', 33, 'mon', 1, 1, 1, 19, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (988, '2026-2027', 33, 'mon', 2, 19, 31, 18, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (989, '2026-2027', 33, 'mon', 3, 3, 31, 20, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (990, '2026-2027', 33, 'mon', 4, 25, 16, 20, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (991, '2026-2027', 33, 'mon', 5, 2, 38, 19, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (992, '2026-2027', 33, 'mon', 6, 28, 29, 18, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (993, '2026-2027', 33, 'sat', 0, 21, 1, 20, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (994, '2026-2027', 33, 'sat', 1, 1, 1, 19, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (995, '2026-2027', 33, 'sat', 2, 25, 16, 19, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (996, '2026-2027', 33, 'sat', 3, 3, 31, 20, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (997, '2026-2027', 33, 'sat', 4, 2, 38, 19, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (998, '2026-2027', 33, 'sat', 5, 18, 34, 19, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (999, '2026-2027', 33, 'sat', 6, 26, 3, 20, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1000, '2026-2027', 33, 'thu', 0, 21, 1, 20, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1001, '2026-2027', 33, 'thu', 1, 3, 31, 20, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1002, '2026-2027', 33, 'thu', 2, 2, 38, 18, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1003, '2026-2027', 33, 'thu', 3, 1, 1, 19, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1004, '2026-2027', 33, 'thu', 4, 28, 29, 18, '2026-03-26 12:19:26', '2026-03-26 12:19:26');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1005, '2026-2027', 33, 'thu', 5, 26, 3, 19, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1006, '2026-2027', 33, 'thu', 6, 25, 16, 19, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1007, '2026-2027', 33, 'tue', 0, 21, 1, 20, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1008, '2026-2027', 33, 'tue', 1, 1, 1, 19, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1009, '2026-2027', 33, 'tue', 2, 1, 1, 19, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1010, '2026-2027', 33, 'tue', 3, 3, 31, 18, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1011, '2026-2027', 33, 'tue', 4, 18, 34, 19, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1012, '2026-2027', 33, 'tue', 5, 28, 29, 20, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1013, '2026-2027', 33, 'tue', 6, 2, 38, 19, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1014, '2026-2027', 33, 'wed', 0, 21, 1, 20, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1015, '2026-2027', 33, 'wed', 1, 3, 31, 19, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1016, '2026-2027', 33, 'wed', 2, 25, 16, 19, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1017, '2026-2027', 33, 'wed', 3, 2, 38, 19, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1018, '2026-2027', 33, 'wed', 4, 2, 38, 19, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1019, '2026-2027', 33, 'wed', 5, 1, 1, 19, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1020, '2026-2027', 33, 'wed', 6, 28, 29, 19, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1021, '2026-2027', 34, 'fri', 0, 21, 38, 20, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1022, '2026-2027', 34, 'fri', 1, 1, 1, 20, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1023, '2026-2027', 34, 'fri', 2, 28, 29, 20, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1024, '2026-2027', 34, 'fri', 3, 2, 38, 21, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1025, '2026-2027', 34, 'fri', 4, 3, 31, 21, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1026, '2026-2027', 34, 'fri', 5, 3, 31, 20, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1027, '2026-2027', 34, 'fri', 6, 25, 1, 20, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1028, '2026-2027', 34, 'mon', 0, 21, 38, 21, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1029, '2026-2027', 34, 'mon', 1, 2, 38, 20, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1030, '2026-2027', 34, 'mon', 2, 18, 34, 19, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1031, '2026-2027', 34, 'mon', 3, 25, 1, 21, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1032, '2026-2027', 34, 'mon', 4, 1, 1, 21, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1033, '2026-2027', 34, 'mon', 5, 1, 1, 20, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1034, '2026-2027', 34, 'mon', 6, 3, 31, 19, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1035, '2026-2027', 34, 'sat', 0, 21, 38, 21, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1036, '2026-2027', 34, 'sat', 1, 19, 31, 20, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1037, '2026-2027', 34, 'sat', 2, 28, 29, 20, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1038, '2026-2027', 34, 'sat', 3, 2, 38, 21, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1039, '2026-2027', 34, 'sat', 4, 3, 31, 20, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1040, '2026-2027', 34, 'sat', 5, 1, 1, 20, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1041, '2026-2027', 34, 'sat', 6, 25, 1, 21, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1042, '2026-2027', 34, 'thu', 0, 21, 38, 21, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1043, '2026-2027', 34, 'thu', 1, 1, 1, 21, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1044, '2026-2027', 34, 'thu', 2, 25, 1, 19, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1045, '2026-2027', 34, 'thu', 3, 3, 31, 20, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1046, '2026-2027', 34, 'thu', 4, 26, 3, 19, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1047, '2026-2027', 34, 'thu', 5, 28, 29, 20, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1048, '2026-2027', 34, 'thu', 6, 2, 38, 20, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1049, '2026-2027', 34, 'tue', 0, 21, 38, 21, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1050, '2026-2027', 34, 'tue', 1, 3, 31, 20, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1051, '2026-2027', 34, 'tue', 2, 2, 38, 20, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1052, '2026-2027', 34, 'tue', 3, 2, 38, 19, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1053, '2026-2027', 34, 'tue', 4, 1, 1, 20, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1054, '2026-2027', 34, 'tue', 5, 25, 1, 21, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1055, '2026-2027', 34, 'tue', 6, 28, 29, 20, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1056, '2026-2027', 34, 'wed', 0, 21, 38, 21, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1057, '2026-2027', 34, 'wed', 1, 1, 1, 20, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1058, '2026-2027', 34, 'wed', 2, 18, 34, 20, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1059, '2026-2027', 34, 'wed', 3, 28, 29, 20, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1060, '2026-2027', 34, 'wed', 4, 3, 31, 20, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1061, '2026-2027', 34, 'wed', 5, 26, 3, 20, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1062, '2026-2027', 34, 'wed', 6, 2, 38, 20, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1063, '2026-2027', 35, 'fri', 0, 21, 28, 21, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1064, '2026-2027', 35, 'fri', 1, 18, 34, 21, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1065, '2026-2027', 35, 'fri', 2, 1, 1, 21, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1066, '2026-2027', 35, 'fri', 3, 26, 3, 22, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1067, '2026-2027', 35, 'fri', 4, 3, 28, 22, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1068, '2026-2027', 35, 'fri', 5, 28, 29, 21, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1069, '2026-2027', 35, 'fri', 6, 2, 38, 21, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1070, '2026-2027', 35, 'mon', 0, 21, 28, 22, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1071, '2026-2027', 35, 'mon', 1, 3, 28, 21, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1072, '2026-2027', 35, 'mon', 2, 25, 16, 20, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1073, '2026-2027', 35, 'mon', 3, 2, 38, 22, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1074, '2026-2027', 35, 'mon', 4, 2, 38, 22, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1075, '2026-2027', 35, 'mon', 5, 28, 29, 21, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1076, '2026-2027', 35, 'mon', 6, 1, 1, 20, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1077, '2026-2027', 35, 'sat', 0, 21, 28, 22, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1078, '2026-2027', 35, 'sat', 1, 28, 29, 21, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1079, '2026-2027', 35, 'sat', 2, 2, 38, 21, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1080, '2026-2027', 35, 'sat', 3, 1, 1, 22, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1081, '2026-2027', 35, 'sat', 4, 18, 34, 21, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1082, '2026-2027', 35, 'sat', 5, 25, 16, 21, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1083, '2026-2027', 35, 'sat', 6, 3, 28, 22, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1084, '2026-2027', 35, 'thu', 0, 21, 28, 22, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1085, '2026-2027', 35, 'thu', 1, 2, 38, 22, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1086, '2026-2027', 35, 'thu', 2, 25, 16, 20, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1087, '2026-2027', 35, 'thu', 3, 28, 29, 21, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1088, '2026-2027', 35, 'thu', 4, 3, 28, 20, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1089, '2026-2027', 35, 'thu', 5, 3, 28, 21, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1090, '2026-2027', 35, 'thu', 6, 1, 1, 21, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1091, '2026-2027', 35, 'tue', 0, 21, 28, 22, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1092, '2026-2027', 35, 'tue', 1, 3, 28, 21, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1093, '2026-2027', 35, 'tue', 2, 25, 16, 21, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1094, '2026-2027', 35, 'tue', 3, 28, 29, 20, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1095, '2026-2027', 35, 'tue', 4, 19, 31, 21, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1096, '2026-2027', 35, 'tue', 5, 2, 38, 22, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1097, '2026-2027', 35, 'tue', 6, 1, 1, 21, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1098, '2026-2027', 35, 'wed', 0, 21, 28, 22, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1099, '2026-2027', 35, 'wed', 1, 3, 28, 21, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1100, '2026-2027', 35, 'wed', 2, 1, 1, 21, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1101, '2026-2027', 35, 'wed', 3, 1, 1, 21, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1102, '2026-2027', 35, 'wed', 4, 26, 3, 21, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1103, '2026-2027', 35, 'wed', 5, 2, 38, 21, '2026-03-26 12:19:27', '2026-03-26 12:19:27');
INSERT INTO `timetable_entries` (`id`, `session`, `class_section_id`, `day_of_week`, `slot_index`, `subject_id`, `teacher_id`, `room_id`, `created_at`, `updated_at`) VALUES (1104, '2026-2027', 35, 'wed', 6, 25, 16, 21, '2026-03-26 12:19:27', '2026-03-26 12:19:27');

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` TEXT NOT NULL,
  `email` TEXT NOT NULL,
  `email_verified_at` DATETIME,
  `password` TEXT NOT NULL,
  `remember_token` TEXT,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  `status` TEXT NOT NULL DEFAULT 'active',
  `must_change_password` BIGINT NOT NULL DEFAULT '0',
  `password_changed_at` DATETIME,
  PRIMARY KEY (`id`),
  KEY `users_status_email_index` (`status`, `email`),
  KEY `users_status_name_index` (`status`, `name`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `status`, `must_change_password`, `password_changed_at`) VALUES (1, 'System Admin', 'admin@pmsc.edu.pk', '2026-03-25 21:18:34', '$2y$12$siDjw43mc3m9fcBpD/E9FeHEcfbp/SW6sy6IjSefUWDKpl0UrI9q2', NULL, '2026-03-25 21:18:34', '2026-03-25 21:18:34', 'active', 0, '2026-03-25 21:18:34');
INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `status`, `must_change_password`, `password_changed_at`) VALUES (2, 'School Principal', 'principal@pmsc.edu.pk', '2026-03-25 21:18:35', '$2y$12$OpSRY8/m.QCSYusyCSkOceNM9eDQ7OOOjJiEfM9oaC8OoBuuydida', NULL, '2026-03-25 21:18:35', '2026-03-25 21:18:35', 'active', 0, '2026-03-25 21:18:35');
INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `status`, `must_change_password`, `password_changed_at`) VALUES (3, 'Class Teacher', 'class-teacher@kort.edu.pk', '2026-03-25 21:18:36', '$2y$12$JDdm/tj39A9DmJEt/Pn65eNpnxU2ypWPnrXFpLDjOevOnttofQ1SW', NULL, '2026-03-25 21:18:36', '2026-03-26 14:52:00', 'active', 0, '2026-03-26 14:52:00');
INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `status`, `must_change_password`, `password_changed_at`) VALUES (4, 'School Doctor', 'doctor@pmsc.edu.pk', '2026-03-25 21:18:37', '$2y$12$j/BKVNd2Rc7Xo8EmYcTI5.orQXUfWJviVdVLxn5DWQv4gxo32TB.u', NULL, '2026-03-25 21:18:37', '2026-03-25 21:18:37', 'active', 0, '2026-03-25 21:18:37');
INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `status`, `must_change_password`, `password_changed_at`) VALUES (5, 'Student User', 'student@pmsc.edu.pk', '2026-03-25 21:18:38', '$2y$12$6YMN/rWzhafhonRkwRB6YesWlf/Era4xGBZdAV3i5ajjjAQmTdfFy', NULL, '2026-03-25 21:18:38', '2026-03-25 21:18:38', 'active', 0, '2026-03-25 21:18:38');
INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `status`, `must_change_password`, `password_changed_at`) VALUES (6, 'School Accountant', 'accountant@pmsc.edu.pk', '2026-03-25 21:18:39', '$2y$12$MJTiTJIraNtOH6ZrcoJS7.ktjL0vRnydjOq6bvhVAN2Jn/FSZtomm', NULL, '2026-03-25 21:18:39', '2026-03-25 21:18:39', 'active', 0, '2026-03-25 21:18:39');
INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `status`, `must_change_password`, `password_changed_at`) VALUES (7, 'Faryal', 'faryal@kort.edu.pk', NULL, '$2y$12$nSSyJZtG16mjhcWLvTzbiuL74AmDbFrJkLdW8/8yJ3X2/69hc8M1K', NULL, '2026-03-25 21:31:24', '2026-03-26 14:51:27', 'active', 0, '2026-03-26 14:51:27');
INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `status`, `must_change_password`, `password_changed_at`) VALUES (8, 'Naveed Ashraf', 'naveed-ashraf@kort.edu.pk', NULL, '$2y$12$FhUPKQnfGbl8QVMN/LhubO4dOQjwoW8H2Qt3rctOdKedzuR69RKOK', NULL, '2026-03-25 21:31:24', '2026-03-26 14:51:28', 'active', 0, '2026-03-26 14:51:28');
INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `status`, `must_change_password`, `password_changed_at`) VALUES (9, 'Kiran Fatima', 'kiran-fatima@kort.edu.pk', NULL, '$2y$12$lJw4PEzyLuPKzWJxhfXsOeQl0QlJMOx6DF3BGi8ChxaYRRIkcohEa', NULL, '2026-03-25 21:31:25', '2026-03-26 14:51:29', 'active', 0, '2026-03-26 14:51:29');
INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `status`, `must_change_password`, `password_changed_at`) VALUES (10, 'M.junaid', 'mjunaid@kort.edu.pk', NULL, '$2y$12$exxTTvVUAULi6R71lSlRpuyaFw5yaGl2BtPQJutAC41Yg/d4qnOK2', NULL, '2026-03-25 21:31:25', '2026-03-26 14:51:30', 'active', 0, '2026-03-26 14:51:30');
INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `status`, `must_change_password`, `password_changed_at`) VALUES (11, 'Hifza', 'hifza@kort.edu.pk', NULL, '$2y$12$2y7Uuk/0IfZAEMu1yQx4a.u8B.LNBjnujwYKtbsICLY1aqVf.QVI2', NULL, '2026-03-25 21:31:26', '2026-03-26 14:51:31', 'active', 0, '2026-03-26 14:51:31');
INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `status`, `must_change_password`, `password_changed_at`) VALUES (12, 'Aliya Zubair', 'aliya-zubair@kort.edu.pk', NULL, '$2y$12$YiHdvK2l/5MKj6fbB48Om.2DNFpN0b0k17x.9jgqG7jVMwDP.2L9O', NULL, '2026-03-25 21:31:26', '2026-03-26 14:51:32', 'active', 0, '2026-03-26 14:51:32');
INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `status`, `must_change_password`, `password_changed_at`) VALUES (13, 'Lubna', 'lubna@kort.edu.pk', NULL, '$2y$12$uo6m1XI2ZdpX92uGUrtuU.2Z9uF0o4yAZVkqnUKoau2FCkERHYXLe', NULL, '2026-03-25 21:31:27', '2026-03-26 14:51:33', 'active', 0, '2026-03-26 14:51:33');
INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `status`, `must_change_password`, `password_changed_at`) VALUES (14, 'Sajida Abid', 'sajida-abid@kort.edu.pk', NULL, '$2y$12$5cT8tXEJ8mBy9W0m1/ROruW3IH/c/y7z3sGTqa8/ABREfw9Hek3Pa', NULL, '2026-03-25 21:31:28', '2026-03-26 14:51:34', 'active', 0, '2026-03-26 14:51:34');
INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `status`, `must_change_password`, `password_changed_at`) VALUES (15, 'Ibrar Ch', 'ibrar-ch@kort.edu.pk', NULL, '$2y$12$GajpljAzGSnihZOnWHJTeOxwA.Kz6Ag/aCjJHCN8xIYf1C7mG5sF2', NULL, '2026-03-25 21:31:28', '2026-03-26 14:51:35', 'active', 0, '2026-03-26 14:51:35');
INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `status`, `must_change_password`, `password_changed_at`) VALUES (16, 'Tahir Mehmood', 'tahir-mehmood@kort.edu.pk', NULL, '$2y$12$IoA/pfj3tIoGlMWXOg2.me31Bj75opRwS9dyOvkcDQTPIa6oZ7ZEK', NULL, '2026-03-25 21:31:29', '2026-03-26 14:51:36', 'active', 0, '2026-03-26 14:51:36');
INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `status`, `must_change_password`, `password_changed_at`) VALUES (17, 'M.shahid', 'mshahid@kort.edu.pk', NULL, '$2y$12$POEXWo2xhV0d/bUTztPeFOuHqtjgdrfnq4LDZ3yh6gapfw9svo27q', NULL, '2026-03-25 21:31:29', '2026-03-26 14:51:37', 'active', 0, '2026-03-26 14:51:37');
INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `status`, `must_change_password`, `password_changed_at`) VALUES (18, 'M.rizwan', 'mrizwan@kort.edu.pk', NULL, '$2y$12$Se3D4oksgHH/GKmPUxBnKOXJHnCoAUO5gUS42jTO1sLZZl4L5t6fS', NULL, '2026-03-25 21:31:29', '2026-03-26 14:51:38', 'active', 0, '2026-03-26 14:51:38');
INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `status`, `must_change_password`, `password_changed_at`) VALUES (19, 'M.waqas', 'mwaqas@kort.edu.pk', NULL, '$2y$12$WJ1ds6pd5NuB.6BLIUm.p.chWrno.somG4e7ITpO8kNOym2AxDPTC', NULL, '2026-03-25 21:31:30', '2026-03-26 14:51:38', 'active', 0, '2026-03-26 14:51:38');
INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `status`, `must_change_password`, `password_changed_at`) VALUES (20, 'Afifa Noor', 'afifa-noor@kort.edu.pk', NULL, '$2y$12$7yQoTk2TxPdZArFIQ9YDiupQkIhbVpaywxAGVO4ocjpiHKoi2smg6', NULL, '2026-03-25 21:31:30', '2026-03-26 14:51:39', 'active', 0, '2026-03-26 14:51:39');
INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `status`, `must_change_password`, `password_changed_at`) VALUES (21, 'Saiqa Saddique', 'saiqa-saddique@kort.edu.pk', NULL, '$2y$12$caruJrCW5uuwFiqjNNZoyOxAD7qkPGTflXKYepaHiLZ7QV/GKl55a', NULL, '2026-03-25 21:31:31', '2026-03-26 14:51:39', 'active', 0, '2026-03-26 14:51:39');
INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `status`, `must_change_password`, `password_changed_at`) VALUES (22, 'Shumaila Ashraf', 'shumaila-ashraf@kort.edu.pk', NULL, '$2y$12$7VlxDneoGSdIxCuMhngWL.t5VQ6P869gaT7uLsSXZfwbbj6s8DJo2', NULL, '2026-03-25 21:31:32', '2026-03-26 14:51:40', 'active', 0, '2026-03-26 14:51:40');
INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `status`, `must_change_password`, `password_changed_at`) VALUES (23, 'M.ismail', 'mismail@kort.edu.pk', NULL, '$2y$12$KSOvjmqL2KQUM0ZU7lcHjuDxee34tOWk0bYk1T17HZv0HWirbsZTa', NULL, '2026-03-25 21:31:33', '2026-03-26 14:51:41', 'active', 0, '2026-03-26 14:51:41');
INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `status`, `must_change_password`, `password_changed_at`) VALUES (24, 'Ishrat Bano', 'ishrat-bano@kort.edu.pk', NULL, '$2y$12$vtakLq0FpB2gOmlODrb1Ke4TKfZayqnLbFxyk5E67KnkROxY7JX6u', NULL, '2026-03-25 21:31:33', '2026-03-26 14:51:42', 'active', 0, '2026-03-26 14:51:42');
INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `status`, `must_change_password`, `password_changed_at`) VALUES (25, 'Anbreen Gulzar', 'anbreen-gulzar@kort.edu.pk', NULL, '$2y$12$sjCno12/MhOMPv1emIzrZew2B8dsl0bAEB3eu2kIUy/i95XwV0aua', NULL, '2026-03-25 21:31:33', '2026-03-26 14:51:43', 'active', 0, '2026-03-26 14:51:43');
INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `status`, `must_change_password`, `password_changed_at`) VALUES (26, 'Nasira Youns', 'nasira-youns@kort.edu.pk', NULL, '$2y$12$G2jTWyMmAK55kBdQzw3psOG/uGB3gFPlQI0IVS6WJUEgnxiIwGmem', NULL, '2026-03-25 21:31:34', '2026-03-26 14:51:44', 'active', 0, '2026-03-26 14:51:44');
INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `status`, `must_change_password`, `password_changed_at`) VALUES (27, 'Memoonah Khurshaid', 'memoonah-khurshaid@kort.edu.pk', NULL, '$2y$12$KK0UIdnHdDq9MC8D1ZC0M.owqEXi3dQ4afmQsH3YDUCviaS2A6bNG', NULL, '2026-03-25 21:31:34', '2026-03-26 14:51:45', 'active', 0, '2026-03-26 14:51:45');
INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `status`, `must_change_password`, `password_changed_at`) VALUES (28, 'Tasleem Kosar', 'tasleem-kosar@kort.edu.pk', NULL, '$2y$12$6FFOLgkO0Tk8zmPeIsmVdOIUXR/jM6PAJuYt2hz/4maDT3xJZ0hFi', NULL, '2026-03-25 21:31:35', '2026-03-26 14:51:46', 'active', 0, '2026-03-26 14:51:46');
INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `status`, `must_change_password`, `password_changed_at`) VALUES (29, 'Sana Rasheed', 'sana-rasheed@kort.edu.pk', NULL, '$2y$12$LYvvXGgrdwre0Dj6YZyiXe4yeuS7E4xS8TFVYaKB8zWeCel448FZW', NULL, '2026-03-25 21:31:35', '2026-03-26 14:51:47', 'active', 0, '2026-03-26 14:51:47');
INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `status`, `must_change_password`, `password_changed_at`) VALUES (30, 'Humaira Nadeem', 'humaira-nadeem@kort.edu.pk', NULL, '$2y$12$VeVISoLaH0QHcKjT7Bojf.YPLJVlLu/4wJvPY2SpZ3KT4N4/bzrCa', NULL, '2026-03-25 21:31:36', '2026-03-26 14:51:49', 'active', 0, '2026-03-26 14:51:49');
INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `status`, `must_change_password`, `password_changed_at`) VALUES (31, 'Komal Sajid', 'komal-sajid@kort.edu.pk', NULL, '$2y$12$amzZDJ.882Q2s8WBoBfkquux1tKcLFJVSCFwdP0gKo3ErGR8dP4RS', NULL, '2026-03-25 21:31:36', '2026-03-26 14:51:50', 'active', 0, '2026-03-26 14:51:50');
INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `status`, `must_change_password`, `password_changed_at`) VALUES (32, 'Samina Walayt', 'samina-walayt@kort.edu.pk', NULL, '$2y$12$rRUauRNB/LqRYaF9i6cgpOCMp0cKTlEU9M.O3oD68BtzlYCUzmWUm', NULL, '2026-03-25 21:31:37', '2026-03-26 14:51:51', 'active', 0, '2026-03-26 14:51:51');
INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `status`, `must_change_password`, `password_changed_at`) VALUES (33, 'Madiha Kanwal', 'madiha-kanwal@kort.edu.pk', NULL, '$2y$12$FQBw28fluLlu4f3MCLCPkOB2JLQGHCKadi.Q91CDsnYp5NG5.6Tbi', NULL, '2026-03-25 21:31:37', '2026-03-26 14:51:51', 'active', 0, '2026-03-26 14:51:51');
INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `status`, `must_change_password`, `password_changed_at`) VALUES (34, 'Nimra', 'nimra@kort.edu.pk', NULL, '$2y$12$1Dk47kVM5gVxucOYS3fRS.iublnLJbBJpyBYz9yVrPTxbdx1d4WFW', NULL, '2026-03-25 21:31:38', '2026-03-26 14:51:53', 'active', 0, '2026-03-26 14:51:53');
INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `status`, `must_change_password`, `password_changed_at`) VALUES (35, 'Shazia Waqas', 'shazia-waqas@kort.edu.pk', NULL, '$2y$12$X7hSPqST7CJBZDOu9UjxMe5C3hKKBcCnERy6fGcPH1sv/Rl8ZAxt6', NULL, '2026-03-25 21:31:38', '2026-03-26 14:51:54', 'active', 0, '2026-03-26 14:51:54');
INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `status`, `must_change_password`, `password_changed_at`) VALUES (36, 'Umair Nazar', 'umair-nazar@kort.edu.pk', NULL, '$2y$12$304D9hlS2GSk6RHmvEJknOIqCdAROOHMFHFv6yT/yeFGuOhDJOVOO', NULL, '2026-03-25 21:31:38', '2026-03-26 14:51:54', 'active', 0, '2026-03-26 14:51:54');
INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `status`, `must_change_password`, `password_changed_at`) VALUES (37, 'Shumila Naveed', 'shumila-naveed@kort.edu.pk', NULL, '$2y$12$VQQGjys9ilemAUettnoBo.8cDwJXPR55t8eyOOuzio2eVtY36bc5.', NULL, '2026-03-25 21:31:39', '2026-03-26 14:51:56', 'active', 0, '2026-03-26 14:51:56');
INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `status`, `must_change_password`, `password_changed_at`) VALUES (38, 'Waqas Zahoor', 'waqas-zahoor@kort.edu.pk', NULL, '$2y$12$mhULnH74fXMT/.2L7PO93OzhKNfT23dv1FZKSJiF9m2XhmxEmIIKq', NULL, '2026-03-25 21:31:40', '2026-03-26 14:51:56', 'active', 0, '2026-03-26 14:51:56');
INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `status`, `must_change_password`, `password_changed_at`) VALUES (39, 'Madiha Iqbal', 'madiha-iqbal@kort.edu.pk', NULL, '$2y$12$TplOETbtZv.XQUl42MwvSOrVdvDaw0yz2iTR21UQMSCturIwudqLu', NULL, '2026-03-25 21:31:40', '2026-03-26 14:51:58', 'active', 0, '2026-03-26 14:51:58');
INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `status`, `must_change_password`, `password_changed_at`) VALUES (40, 'Sehrish Kazmi', 'sehrish-kazmi@kort.edu.pk', NULL, '$2y$12$oidvjX.zuE9qUM8lQVHrzea4vjXZ4aNpd9aX4be9Enb6guAub9otC', NULL, '2026-03-25 21:31:41', '2026-03-26 14:51:59', 'active', 0, '2026-03-26 14:51:59');
INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `status`, `must_change_password`, `password_changed_at`) VALUES (41, 'M. Rizwan', 'm-rizwan@kort.edu.pk', NULL, '$2y$12$DCZdGtzxtMvARkt8pnEc3OeTloJSgBWNKw.4tTRe..T0b4/kfASRu', NULL, '2026-03-26 12:19:15', '2026-03-26 14:52:01', 'active', 0, '2026-03-26 14:52:01');
INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `status`, `must_change_password`, `password_changed_at`) VALUES (42, 'Moazam ayub', 'moazam-ayub@kort.edu.pk', NULL, '$2y$12$P/bUVeJWKymZiqrkAzgKguB1WH.DIV8aW6jYh/eAX0oijAO61rOPm', NULL, '2026-03-26 12:19:17', '2026-03-26 14:52:02', 'active', 0, '2026-03-26 14:52:02');
INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `status`, `must_change_password`, `password_changed_at`) VALUES (43, 'Rohila', 'rohila@kort.edu.pk', NULL, '$2y$12$an3K/glPYZlVEr42cDVSxOyxhvQsBQxCnJRuXteJOp.MDyhpxCinC', NULL, '2026-03-26 12:19:18', '2026-03-26 14:52:03', 'active', 0, '2026-03-26 14:52:03');

ALTER TABLE `migrations` AUTO_INCREMENT = 45;
ALTER TABLE `school_classes` AUTO_INCREMENT = 55;
ALTER TABLE `student_subject_assignments` AUTO_INCREMENT = 217;
ALTER TABLE `fee_challan_items` AUTO_INCREMENT = 1;
ALTER TABLE `permissions` AUTO_INCREMENT = 35;
ALTER TABLE `roles` AUTO_INCREMENT = 7;
ALTER TABLE `users` AUTO_INCREMENT = 44;
ALTER TABLE `school_settings` AUTO_INCREMENT = 2;
ALTER TABLE `students` AUTO_INCREMENT = 616;
ALTER TABLE `teachers` AUTO_INCREMENT = 39;
ALTER TABLE `subjects` AUTO_INCREMENT = 30;
ALTER TABLE `teacher_assignments` AUTO_INCREMENT = 260;
ALTER TABLE `time_slots` AUTO_INCREMENT = 55;
ALTER TABLE `class_sections` AUTO_INCREMENT = 36;
ALTER TABLE `rooms` AUTO_INCREMENT = 23;
ALTER TABLE `class_subject` AUTO_INCREMENT = 300;
ALTER TABLE `timetable_entries` AUTO_INCREMENT = 1105;
ALTER TABLE `teacher_subject_assignments` AUTO_INCREMENT = 221;
ALTER TABLE `subject_period_rules` AUTO_INCREMENT = 219;
ALTER TABLE `exam_rooms` AUTO_INCREMENT = 2;
ALTER TABLE `exam_sessions` AUTO_INCREMENT = 2;
ALTER TABLE `exam_seating_plans` AUTO_INCREMENT = 2;
ALTER TABLE `exam_seat_assignments` AUTO_INCREMENT = 36;
ALTER TABLE `exam_room_invigilators` AUTO_INCREMENT = 4;
ALTER TABLE `class_promotion_mappings` AUTO_INCREMENT = 7;

SET FOREIGN_KEY_CHECKS=1;