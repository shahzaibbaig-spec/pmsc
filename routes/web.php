<?php

use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\DailyDiaryAttachmentController;
use App\Http\Controllers\Inventory\DeviceDeclarationReviewController;
use App\Http\Controllers\Inventory\InventoryDemandReviewController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Principal\TeacherAcrController;
use App\Http\Controllers\Principal\TeacherAttendanceController as PrincipalTeacherAttendanceController;
use App\Http\Controllers\Principal\TeacherResultEntryController;
use App\Http\Controllers\Principal\TeacherRankingController;
use App\Http\Controllers\Principal\TeacherAssignmentController as PrincipalTeacherAssignmentController;
use App\Http\Controllers\Principal\TeacherAssignmentRolloverController;
use App\Http\Controllers\Principal\AnalyticsExportController;
use App\Http\Controllers\Principal\DailyDiaryMonitoringController;
use App\Http\Controllers\Principal\PrincipalPromotionController;
use App\Http\Controllers\Student\StudentDailyDiaryController;
use App\Http\Controllers\Teacher\DailyDiaryController;
use App\Http\Controllers\Teacher\TeacherDeviceDeclarationController;
use App\Http\Controllers\Teacher\TeacherEResourceController;
use App\Http\Controllers\Teacher\TeacherInventoryController;
use App\Http\Controllers\Teacher\TeacherInventoryDemandController;
use App\Http\Controllers\Teacher\TeacherPromotionController;
use App\Http\Controllers\Warden\WardenDailyDiaryController;
use App\Http\Controllers\Warden\WardenDashboardController;
use App\Http\Controllers\Warden\WardenDisciplineController;
use App\Http\Controllers\Warden\HostelLeaveController;
use App\Http\Controllers\Warden\HostelNightAttendanceController;
use App\Http\Controllers\Warden\HostelRoomAllocationController;
use App\Http\Controllers\Warden\HostelRoomController;
use App\Http\Controllers\Warden\WardenStudentRecordController;
use App\Modules\Academic\Controllers\AcademicCalendarController;
use App\Modules\Academic\Controllers\AcademicNotificationController;
use App\Modules\Accountant\Controllers\AccountantDashboardController;
use App\Modules\Admin\Controllers\AdminDashboardController;
use App\Modules\Admin\Controllers\RbacMatrixController;
use App\Modules\Admin\Controllers\SchoolSettingController;
use App\Modules\Admin\Controllers\UserManagementController;
use App\Modules\Analytics\Controllers\PerformanceInsightsController;
use App\Modules\Analytics\Controllers\PrincipalAnalyticsDashboardController;
use App\Modules\Analytics\Controllers\TeacherAnalyticsController;
use App\Modules\Attendance\Controllers\PrincipalAttendanceController;
use App\Modules\Attendance\Controllers\TeacherAttendanceController;
use App\Modules\Classes\Controllers\ClassManagementController;
use App\Modules\Classes\Controllers\PrincipalDashboardController;
use App\Modules\Exams\Controllers\TeacherExamController;
use App\Modules\Exams\Controllers\TeacherMarkEntryController;
use App\Modules\Exams\Controllers\ExamSeatingPlanController;
use App\Modules\Exams\Controllers\ExamRoomInvigilatorController;
use App\Modules\Exams\Controllers\ExamHallAttendanceController;
use App\Modules\Exams\Controllers\LiveExamAttendanceMonitorController;
use App\Modules\Fees\Controllers\FeeChallanController;
use App\Modules\Fees\Controllers\FeeDefaulterController;
use App\Modules\Fees\Controllers\FeeInstallmentPlanController;
use App\Modules\Fees\Controllers\FeePaymentController;
use App\Modules\Fees\Controllers\FeeReportController;
use App\Modules\Fees\Controllers\FeeStructureController;
use App\Modules\Fees\Controllers\StudentArrearController;
use App\Modules\Fees\Controllers\StudentCustomFeeController;
use App\Modules\Medical\Controllers\DoctorDashboardController;
use App\Modules\Medical\Controllers\DoctorMedicalRequestListController;
use App\Modules\Medical\Controllers\MedicalReferralController;
use App\Modules\Payroll\Controllers\PayrollProfileController;
use App\Modules\Payroll\Controllers\PayrollRunController;
use App\Modules\Payroll\Controllers\PayrollDashboardController;
use App\Modules\Reports\Controllers\ReportPdfController;
use App\Modules\Reports\Controllers\AdmitCardController;
use App\Modules\Reports\Controllers\StudentIdCardController;
use App\Modules\Results\Controllers\PrincipalResultController;
use App\Modules\Results\Controllers\ResultSheetController;
use App\Modules\Results\Controllers\StudentResultController;
use App\Modules\Results\Controllers\TeacherResultController;
use App\Modules\Results\Controllers\ClassResultAnalyzerController;
use App\Modules\Results\Controllers\LearningProfileController;
use App\Modules\Results\Controllers\PromotionAnalyzerController;
use App\Modules\Search\Controllers\StudentSearchController;
use App\Modules\Students\Controllers\StudentDashboardController;
use App\Modules\Students\Controllers\PrincipalStudentListController;
use App\Modules\Students\Controllers\StudentManagementController;
use App\Modules\Students\Controllers\StudentQrProfileController;
use App\Modules\Subjects\Controllers\StudentSubjectAssignmentMatrixController;
use App\Modules\Subjects\Controllers\SubjectManagementController;
use App\Modules\Teachers\Controllers\TeacherDashboardController;
use App\Modules\Teachers\Controllers\PrincipalTeacherListController;
use App\Modules\Timetable\Controllers\SubjectPeriodRuleController;
use App\Modules\Timetable\Controllers\TimetableEntryController;
use App\Modules\Timetable\Controllers\TimetableExportController;
use App\Modules\Timetable\Controllers\TimetableGenerationController;
use App\Modules\Timetable\Controllers\TimetableImportController;
use App\Modules\Timetable\Controllers\TimetableViewerController;
use App\Modules\Timetable\Controllers\TeacherAvailabilityController;
use App\Modules\Timetable\Controllers\TimetableSettingsController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/student/qr/{code}', [StudentQrProfileController::class, 'show'])
    ->name('students.qr.profile');

Route::get('/student/photo/{student}', [StudentQrProfileController::class, 'photo'])
    ->whereNumber('student')
    ->name('students.photo');

Route::get('/dashboard', DashboardRedirectController::class)
    ->middleware(['auth', 'verified', 'force-password-change'])
    ->name('dashboard');

Route::middleware(['auth', 'force-password-change'])->group(function () {
    Route::get('/daily-diary/{dailyDiary}/attachment', DailyDiaryAttachmentController::class)
        ->whereNumber('dailyDiary')
        ->name('daily-diary.attachment');

    Route::get('/notifications', [NotificationController::class, 'index'])
        ->name('notifications.index');

    Route::get('/notifications/data', [NotificationController::class, 'data'])
        ->name('notifications.data');

    Route::post('/notifications/push/subscribe', [NotificationController::class, 'subscribePush'])
        ->name('notifications.push.subscribe');

    Route::post('/notifications/push/unsubscribe', [NotificationController::class, 'unsubscribePush'])
        ->name('notifications.push.unsubscribe');

    Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])
        ->name('notifications.read');

    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])
        ->name('notifications.read-all');

    Route::get('/academic-calendar', [AcademicCalendarController::class, 'index'])
        ->middleware(['role:Admin,Principal,Teacher'])
        ->name('academic-calendar.index');

    Route::post('/academic-calendar', [AcademicCalendarController::class, 'store'])
        ->middleware(['role:Admin,Principal'])
        ->name('academic-calendar.store');

    Route::put('/academic-calendar/{academicEvent}', [AcademicCalendarController::class, 'update'])
        ->middleware(['role:Admin,Principal'])
        ->name('academic-calendar.update');

    Route::delete('/academic-calendar/{academicEvent}', [AcademicCalendarController::class, 'destroy'])
        ->middleware(['role:Admin,Principal'])
        ->name('academic-calendar.destroy');

    Route::post('/academic-calendar/{academicEvent}/send-reminder', [AcademicCalendarController::class, 'sendReminder'])
        ->middleware(['role:Admin,Principal'])
        ->name('academic-calendar.send-reminder');

    Route::post('/academic-notifications/{academicNotification}/read', [AcademicNotificationController::class, 'read'])
        ->name('academic-notifications.read');

    Route::post('/academic-notifications/read-all', [AcademicNotificationController::class, 'readAll'])
        ->name('academic-notifications.read-all');

    Route::get('/admin/dashboard', AdminDashboardController::class)
        ->middleware(['role:Admin', 'permission:manage_users'])
        ->name('admin.dashboard');

    Route::get('/accountant/dashboard', AccountantDashboardController::class)
        ->middleware(['role:Admin,Accountant'])
        ->name('accountant.dashboard');

    Route::get('/admin/rbac-matrix', [RbacMatrixController::class, 'index'])
        ->middleware(['role:Admin', 'permission:assign_roles'])
        ->name('admin.rbac-matrix.index');

    Route::get('/admin/rbac-matrix/data', [RbacMatrixController::class, 'data'])
        ->middleware(['role:Admin', 'permission:assign_roles'])
        ->name('admin.rbac-matrix.data');

    Route::post('/admin/rbac-matrix/toggle', [RbacMatrixController::class, 'toggle'])
        ->middleware(['role:Admin', 'permission:assign_roles'])
        ->name('admin.rbac-matrix.toggle');

    Route::post('/admin/rbac-matrix/save', [RbacMatrixController::class, 'save'])
        ->middleware(['role:Admin', 'permission:assign_roles'])
        ->name('admin.rbac-matrix.save');

    Route::get('/admin/users', [UserManagementController::class, 'index'])
        ->middleware(['role:Admin', 'permission:manage_users'])
        ->name('admin.users.index');

    Route::get('/admin/settings', [SchoolSettingController::class, 'edit'])
        ->middleware(['role:Admin', 'permission:manage_school_settings'])
        ->name('admin.settings.edit');

    Route::post('/admin/settings', [SchoolSettingController::class, 'update'])
        ->middleware(['role:Admin', 'permission:manage_school_settings'])
        ->name('admin.settings.update');

    Route::get('/admin/users/data', [UserManagementController::class, 'data'])
        ->middleware(['role:Admin', 'permission:manage_users'])
        ->name('admin.users.data');

    Route::post('/admin/users', [UserManagementController::class, 'store'])
        ->middleware(['role:Admin', 'permission:manage_users'])
        ->name('admin.users.store');

    Route::put('/admin/users/{user}', [UserManagementController::class, 'update'])
        ->middleware(['role:Admin', 'permission:manage_users'])
        ->name('admin.users.update');

    Route::delete('/admin/users/{user}', [UserManagementController::class, 'destroy'])
        ->middleware(['role:Admin', 'permission:manage_users'])
        ->name('admin.users.destroy');

    Route::post('/admin/users/{user}/assign-role', [UserManagementController::class, 'assignRole'])
        ->middleware(['role:Admin', 'permission:assign_roles'])
        ->name('admin.users.assign-role');

    Route::get('/admin/students', [StudentManagementController::class, 'index'])
        ->middleware(['role:Admin', 'permission:manage_users'])
        ->name('admin.students.index');

    Route::get('/admin/students/data', [StudentManagementController::class, 'data'])
        ->middleware(['role:Admin', 'permission:manage_users'])
        ->name('admin.students.data');

    Route::get('/admin/students/create', [StudentManagementController::class, 'create'])
        ->middleware(['role:Admin', 'permission:manage_users'])
        ->name('admin.students.create');

    Route::post('/admin/students', [StudentManagementController::class, 'store'])
        ->middleware(['role:Admin', 'permission:manage_users'])
        ->name('admin.students.store');

    Route::post('/admin/students/import', [StudentManagementController::class, 'import'])
        ->middleware(['role:Admin', 'permission:manage_users'])
        ->name('admin.students.import');

    Route::post('/admin/students/bulk-add', [StudentManagementController::class, 'bulkAdd'])
        ->middleware(['role:Admin', 'permission:manage_users'])
        ->name('admin.students.bulk-add');

    Route::delete('/admin/students/bulk-delete', [StudentManagementController::class, 'bulkDelete'])
        ->middleware(['role:Admin', 'permission:manage_users'])
        ->name('admin.students.bulk-delete');

    Route::get('/admin/students/{student}/edit', [StudentManagementController::class, 'edit'])
        ->middleware(['role:Admin', 'permission:manage_users'])
        ->name('admin.students.edit');

    Route::put('/admin/students/{student}', [StudentManagementController::class, 'update'])
        ->middleware(['role:Admin', 'permission:manage_users'])
        ->name('admin.students.update');

    Route::post('/admin/students/{student}/photo', [StudentManagementController::class, 'updatePhoto'])
        ->middleware(['role:Admin', 'permission:manage_users'])
        ->name('admin.students.photo.update');

    Route::get('/admin/students/{student}/delete', [StudentManagementController::class, 'deletePage'])
        ->middleware(['role:Admin', 'permission:manage_users'])
        ->name('admin.students.delete-page');

    Route::delete('/admin/students/{student}', [StudentManagementController::class, 'destroy'])
        ->middleware(['role:Admin', 'permission:manage_users'])
        ->name('admin.students.destroy');

    Route::get('/admin/students/{student}', [StudentManagementController::class, 'show'])
        ->middleware(['role:Admin', 'permission:manage_users'])
        ->name('admin.students.show');

    Route::get('/admin/students/{student}/tabs/{tab}', [StudentManagementController::class, 'tabContent'])
        ->middleware(['role:Admin', 'permission:manage_users'])
        ->name('admin.students.tabs');

    Route::get('/principal/students', [PrincipalStudentListController::class, 'index'])
        ->middleware(['role:Principal'])
        ->name('principal.students.index');

    Route::get('/principal/students/data', [PrincipalStudentListController::class, 'data'])
        ->middleware(['role:Principal'])
        ->name('principal.students.data');

    Route::get('/principal/students/{student}', [StudentManagementController::class, 'show'])
        ->middleware('role:Principal')
        ->name('principal.students.show');

    Route::post('/principal/students/{student}/photo', [StudentManagementController::class, 'updatePhoto'])
        ->middleware('role:Principal')
        ->name('principal.students.photo.update');

    Route::get('/principal/students/{student}/tabs/{tab}', [StudentManagementController::class, 'tabContent'])
        ->middleware('role:Principal')
        ->name('principal.students.tabs');

    Route::get('/principal/dashboard', PrincipalDashboardController::class)
        ->middleware('role:Principal')
        ->name('principal.dashboard');

    Route::get('/principal/subjects', [SubjectManagementController::class, 'index'])
        ->middleware(['role:Principal', 'permission:manage_subjects'])
        ->name('principal.subjects.index');

    Route::get('/principal/subjects/data', [SubjectManagementController::class, 'data'])
        ->middleware(['role:Principal', 'permission:manage_subjects'])
        ->name('principal.subjects.data');

    Route::post('/principal/subjects', [SubjectManagementController::class, 'store'])
        ->middleware(['role:Principal', 'permission:manage_subjects'])
        ->name('principal.subjects.store');

    Route::put('/principal/subjects/{subject}', [SubjectManagementController::class, 'update'])
        ->middleware(['role:Principal', 'permission:manage_subjects'])
        ->name('principal.subjects.update');

    Route::delete('/principal/subjects/{subject}', [SubjectManagementController::class, 'destroy'])
        ->middleware(['role:Principal', 'permission:manage_subjects'])
        ->name('principal.subjects.destroy');

    Route::get('/principal/subjects/matrix', function () {
        return redirect()->route('principal.subject-matrix.index');
    })
        ->middleware(['permission:manage_subject_assignments'])
        ->name('principal.subjects.matrix.index');

    Route::get('/principal/student-subjects', [StudentSubjectAssignmentMatrixController::class, 'index'])
        ->middleware(['permission:manage_subject_assignments'])
        ->name('principal.student-subjects.index');

    Route::get('/principal/subject-matrix', [StudentSubjectAssignmentMatrixController::class, 'index'])
        ->middleware(['permission:manage_subject_assignments'])
        ->name('principal.subject-matrix.index');

    Route::get('/principal/student-subjects/data', [StudentSubjectAssignmentMatrixController::class, 'data'])
        ->middleware(['permission:manage_subject_assignments'])
        ->name('principal.student-subjects.data');

    Route::post('/principal/student-subjects/update', [StudentSubjectAssignmentMatrixController::class, 'update'])
        ->middleware(['permission:manage_subject_assignments'])
        ->name('principal.student-subjects.update');

    Route::post('/principal/student-subjects/assign-class', [StudentSubjectAssignmentMatrixController::class, 'assignClass'])
        ->middleware(['permission:manage_subject_assignments'])
        ->name('principal.student-subjects.assign-class');

    Route::post('/principal/student-subjects/custom-subject', [StudentSubjectAssignmentMatrixController::class, 'storeCustomSubject'])
        ->middleware(['permission:manage_subject_assignments'])
        ->name('principal.student-subjects.custom-subject');

    Route::get('/principal/subject-groups', [StudentSubjectAssignmentMatrixController::class, 'subjectGroups'])
        ->middleware(['permission:manage_subject_assignments'])
        ->name('principal.subject-groups.index');

    Route::post('/principal/subject-groups', [StudentSubjectAssignmentMatrixController::class, 'storeSubjectGroup'])
        ->middleware(['permission:manage_subject_assignments'])
        ->name('principal.subject-groups.store');

    Route::put('/principal/subject-groups/{subjectGroup}', [StudentSubjectAssignmentMatrixController::class, 'updateSubjectGroup'])
        ->middleware(['permission:manage_subject_assignments'])
        ->name('principal.subject-groups.update');

    Route::post('/principal/student-subjects/assign-group', [StudentSubjectAssignmentMatrixController::class, 'assignGroup'])
        ->middleware(['permission:manage_subject_assignments'])
        ->name('principal.student-subjects.assign-group');

    Route::get('/principal/classes', [ClassManagementController::class, 'index'])
        ->middleware(['role:Principal', 'permission:assign_subjects'])
        ->name('principal.classes.index');

    Route::get('/principal/classes/options', [ClassManagementController::class, 'options'])
        ->middleware(['role:Principal', 'permission:assign_subjects'])
        ->name('principal.classes.options');

    Route::get('/principal/classes/data', [ClassManagementController::class, 'data'])
        ->middleware(['role:Principal', 'permission:assign_subjects'])
        ->name('principal.classes.data');

    Route::post('/principal/classes', [ClassManagementController::class, 'store'])
        ->middleware(['role:Principal', 'permission:assign_subjects'])
        ->name('principal.classes.store');

    Route::put('/principal/classes/{schoolClass}', [ClassManagementController::class, 'update'])
        ->middleware(['role:Principal', 'permission:assign_subjects'])
        ->name('principal.classes.update');

    Route::post('/principal/classes/{schoolClass}/assign-subjects', [ClassManagementController::class, 'assignSubjects'])
        ->middleware(['role:Principal', 'permission:assign_subjects'])
        ->name('principal.classes.assign-subjects');

    Route::get('/principal/timetable/settings', [TimetableSettingsController::class, 'index'])
        ->middleware(['role:Principal'])
        ->name('principal.timetable.settings.index');

    Route::get('/principal/timetable/settings/time-slots/data', [TimetableSettingsController::class, 'timeSlotsData'])
        ->middleware(['role:Principal'])
        ->name('principal.timetable.settings.time-slots.data');

    Route::post('/principal/timetable/settings/time-slots/regenerate', [TimetableSettingsController::class, 'regenerateTimeSlots'])
        ->middleware(['role:Principal'])
        ->name('principal.timetable.settings.time-slots.regenerate');

    Route::get('/principal/timetable/settings/constraints/data', [TimetableSettingsController::class, 'constraintsData'])
        ->middleware(['role:Principal'])
        ->name('principal.timetable.settings.constraints.data');

    Route::post('/principal/timetable/settings/constraints', [TimetableSettingsController::class, 'storeConstraint'])
        ->middleware(['role:Principal'])
        ->name('principal.timetable.settings.constraints.store');

    Route::get('/principal/timetable/subject-rules', [SubjectPeriodRuleController::class, 'index'])
        ->middleware(['role:Principal'])
        ->name('principal.timetable.subject-rules.index');

    Route::get('/principal/timetable/subject-rules/options', [SubjectPeriodRuleController::class, 'options'])
        ->middleware(['role:Principal'])
        ->name('principal.timetable.subject-rules.options');

    Route::get('/principal/timetable/subject-rules/data', [SubjectPeriodRuleController::class, 'data'])
        ->middleware(['role:Principal'])
        ->name('principal.timetable.subject-rules.data');

    Route::post('/principal/timetable/subject-rules', [SubjectPeriodRuleController::class, 'store'])
        ->middleware(['role:Principal'])
        ->name('principal.timetable.subject-rules.store');

    Route::put('/principal/timetable/subject-rules/{subjectPeriodRule}', [SubjectPeriodRuleController::class, 'update'])
        ->middleware(['role:Principal'])
        ->name('principal.timetable.subject-rules.update');

    Route::delete('/principal/timetable/subject-rules/{subjectPeriodRule}', [SubjectPeriodRuleController::class, 'destroy'])
        ->middleware(['role:Principal'])
        ->name('principal.timetable.subject-rules.destroy');

    Route::get('/principal/timetable/teacher-availability', [TeacherAvailabilityController::class, 'index'])
        ->middleware(['role:Principal'])
        ->name('principal.timetable.teacher-availability.index');

    Route::get('/principal/timetable/teacher-availability/options', [TeacherAvailabilityController::class, 'options'])
        ->middleware(['role:Principal'])
        ->name('principal.timetable.teacher-availability.options');

    Route::get('/principal/timetable/teacher-availability/matrix', [TeacherAvailabilityController::class, 'matrix'])
        ->middleware(['role:Principal'])
        ->name('principal.timetable.teacher-availability.matrix');

    Route::get('/principal/timetable/teacher-availability/data', [TeacherAvailabilityController::class, 'data'])
        ->middleware(['role:Principal'])
        ->name('principal.timetable.teacher-availability.data');

    Route::post('/principal/timetable/teacher-availability/save', [TeacherAvailabilityController::class, 'save'])
        ->middleware(['role:Principal'])
        ->name('principal.timetable.teacher-availability.save');

    Route::get('/principal/timetable/viewer', [TimetableViewerController::class, 'principalViewer'])
        ->middleware(['role:Principal'])
        ->name('principal.timetable.viewer.index');

    Route::get('/principal/timetable/generate', [TimetableGenerationController::class, 'index'])
        ->middleware(['role:Principal'])
        ->name('principal.timetable.generate.index');

    Route::post('/principal/timetable/generate', [TimetableGenerationController::class, 'generate'])
        ->middleware(['role:Principal'])
        ->name('principal.timetable.generate.run');

    Route::get('/principal/timetable/import', [TimetableImportController::class, 'index'])
        ->middleware(['role:Principal'])
        ->name('principal.timetable.import.index');

    Route::post('/principal/timetable/import', [TimetableImportController::class, 'store'])
        ->middleware(['role:Principal'])
        ->name('principal.timetable.import.store');

    Route::get('/principal/timetable/export/pdf', [TimetableExportController::class, 'pdf'])
        ->middleware(['role:Principal'])
        ->name('principal.timetable.export.pdf');

    Route::get('/principal/timetable/export/csv', [TimetableExportController::class, 'csv'])
        ->middleware(['role:Principal'])
        ->name('principal.timetable.export.csv');

    Route::get('/principal/teacher-assignments', [PrincipalTeacherAssignmentController::class, 'index'])
        ->middleware(['role:Principal|Admin', 'permission:assign_teachers'])
        ->name('principal.teacher-assignments.index');

    Route::get('/principal/teacher-assignments/create', [PrincipalTeacherAssignmentController::class, 'create'])
        ->middleware(['role:Principal|Admin', 'permission:assign_teachers'])
        ->name('principal.teacher-assignments.create');

    Route::get('/principal/teacher-assignments/search', [PrincipalTeacherAssignmentController::class, 'search'])
        ->middleware(['role:Principal|Admin', 'permission:assign_teachers'])
        ->name('principal.teacher-assignments.search');

    Route::get('/principal/teacher-assignments/class-teachers', [PrincipalTeacherAssignmentController::class, 'classTeacherMatrix'])
        ->middleware(['role:Principal|Admin', 'permission:assign_teachers'])
        ->name('principal.teacher-assignments.class-teachers');

    Route::post('/principal/teacher-assignments/class-teachers/assign', [PrincipalTeacherAssignmentController::class, 'assignClassTeacher'])
        ->middleware(['role:Principal|Admin', 'permission:assign_teachers'])
        ->name('principal.teacher-assignments.class-teachers.assign');

    Route::get('/principal/teacher-assignments/teacher/{teacher}', [PrincipalTeacherAssignmentController::class, 'showTeacher'])
        ->middleware(['role:Principal|Admin', 'permission:assign_teachers'])
        ->whereNumber('teacher')
        ->name('principal.teacher-assignments.teacher.show');

    Route::post('/principal/teacher-assignments/teacher/{teacher}/bulk-store', [PrincipalTeacherAssignmentController::class, 'storeBulkForTeacher'])
        ->middleware(['role:Principal|Admin', 'permission:assign_teachers'])
        ->whereNumber('teacher')
        ->name('principal.teacher-assignments.teacher.bulk-store');

    Route::post('/principal/teacher-assignments/teacher/{teacher}/replace-session-assignments', [PrincipalTeacherAssignmentController::class, 'replaceSessionAssignmentsForTeacher'])
        ->middleware(['role:Principal|Admin', 'permission:assign_teachers'])
        ->whereNumber('teacher')
        ->name('principal.teacher-assignments.teacher.replace-session-assignments');

    Route::get('/principal/teacher-assignments/rollover', [TeacherAssignmentRolloverController::class, 'index'])
        ->middleware(['role:Principal|Admin', 'permission:assign_teachers'])
        ->name('principal.teacher-assignments.rollover.index');

    Route::post('/principal/teacher-assignments/rollover/preview', [TeacherAssignmentRolloverController::class, 'preview'])
        ->middleware(['role:Principal|Admin', 'permission:assign_teachers'])
        ->name('principal.teacher-assignments.rollover.preview');

    Route::post('/principal/teacher-assignments/rollover', [TeacherAssignmentRolloverController::class, 'store'])
        ->middleware(['role:Principal|Admin', 'permission:assign_teachers'])
        ->name('principal.teacher-assignments.rollover.store');

    Route::get('/principal/teachers', [PrincipalTeacherListController::class, 'index'])
        ->middleware(['role:Principal'])
        ->name('principal.teachers.index');

    Route::get('/principal/teachers/data', [PrincipalTeacherListController::class, 'data'])
        ->middleware(['role:Principal'])
        ->name('principal.teachers.data');

    Route::get('/principal/teachers/export/active-docx', [PrincipalTeacherListController::class, 'exportActiveDocx'])
        ->middleware(['role:Principal'])
        ->name('principal.teachers.export-active-docx');

    Route::post('/principal/teacher-assignments/bulk-store', [PrincipalTeacherAssignmentController::class, 'storeBulk'])
        ->middleware(['role:Principal|Admin', 'permission:assign_teachers'])
        ->name('principal.teacher-assignments.bulk-store');

    Route::delete('/principal/teacher-assignments/{assignment}', [PrincipalTeacherAssignmentController::class, 'destroy'])
        ->middleware(['role:Principal|Admin', 'permission:assign_teachers'])
        ->whereNumber('assignment')
        ->name('principal.teacher-assignments.destroy');

    Route::prefix('inventory')
        ->name('inventory.')
        ->middleware(['role:Admin,Principal'])
        ->group(function (): void {
            Route::get('/demands', [InventoryDemandReviewController::class, 'index'])
                ->middleware(['permission:review_inventory_demands'])
                ->name('demands.index');

            Route::get('/demands/{demand}', [InventoryDemandReviewController::class, 'show'])
                ->middleware(['permission:review_inventory_demands'])
                ->name('demands.show');

            Route::post('/demands/{demand}/review', [InventoryDemandReviewController::class, 'review'])
                ->middleware(['permission:review_inventory_demands'])
                ->name('demands.review');

            Route::post('/demands/{demand}/fulfill', [InventoryDemandReviewController::class, 'fulfill'])
                ->middleware(['permission:fulfill_inventory_demands'])
                ->name('demands.fulfill');

            Route::get('/device-declarations', [DeviceDeclarationReviewController::class, 'index'])
                ->middleware(['permission:review_device_declarations'])
                ->name('device-declarations.index');

            Route::get('/device-declarations/{declaration}', [DeviceDeclarationReviewController::class, 'show'])
                ->middleware(['permission:review_device_declarations'])
                ->name('device-declarations.show');

            Route::post('/device-declarations/{declaration}/review', [DeviceDeclarationReviewController::class, 'review'])
                ->middleware(['permission:review_device_declarations'])
                ->name('device-declarations.review');
        });

    Route::get('/principal/results', [PrincipalResultController::class, 'index'])
        ->middleware(['role:Admin|Principal', 'permission:generate_results'])
        ->name('principal.results.index');

    Route::get('/principal/results/generator', [PrincipalResultController::class, 'generator'])
        ->middleware(['role:Admin|Principal', 'permission:generate_results'])
        ->name('principal.results.generator');

    Route::get('/principal/results/gazette', [ResultSheetController::class, 'gazette'])
        ->middleware(['role:Admin|Principal', 'permission:generate_results'])
        ->name('principal.results.gazette');

    Route::get('/principal/results/tabulation', [ResultSheetController::class, 'tabulation'])
        ->middleware(['role:Admin|Principal', 'permission:generate_results'])
        ->name('principal.results.tabulation');

    Route::get('/principal/results/teacher-entries', [TeacherResultEntryController::class, 'index'])
        ->middleware(['role:Admin|Principal', 'permission:view_teacher_result_entries'])
        ->name('principal.results.teacher-entries.index');

    Route::get('/principal/results/teacher-entries/{teacher}', [TeacherResultEntryController::class, 'showTeacher'])
        ->middleware(['role:Admin|Principal', 'permission:view_teacher_result_entries'])
        ->whereNumber('teacher')
        ->name('principal.results.teacher-entries.show');

    Route::get('/principal/results/teacher-entries/{teacher}/logs', [TeacherResultEntryController::class, 'logs'])
        ->middleware(['role:Admin|Principal', 'permission:view_result_entry_logs'])
        ->whereNumber('teacher')
        ->name('principal.results.teacher-entries.logs');

    Route::get('/principal/analytics', [PrincipalAnalyticsDashboardController::class, 'index'])
        ->middleware(['role:Admin,Principal'])
        ->name('principal.analytics.dashboard.index');

    Route::get('/principal/analytics/export/pdf', [AnalyticsExportController::class, 'exportPdf'])
        ->middleware(['role:Admin,Principal'])
        ->name('principal.analytics.export.pdf');

    Route::get('/principal/analytics/export/excel', [AnalyticsExportController::class, 'exportExcel'])
        ->middleware(['role:Admin,Principal'])
        ->name('principal.analytics.export.excel');

    Route::get('/principal/analytics/export/board-summary-pdf', [AnalyticsExportController::class, 'boardSummaryPdf'])
        ->middleware(['role:Admin,Principal'])
        ->name('principal.analytics.export.board-summary-pdf');

    Route::get('/principal/analytics/class/{schoolClass}', [PrincipalAnalyticsDashboardController::class, 'classDrilldown'])
        ->middleware(['role:Admin,Principal'])
        ->name('principal.analytics.dashboard.class');

    Route::get('/principal/analytics/teacher/{teacher}', [PrincipalAnalyticsDashboardController::class, 'teacherDrilldown'])
        ->middleware(['role:Admin,Principal'])
        ->name('principal.analytics.dashboard.teacher');

    Route::get('/principal/analytics/performance-insights', [PerformanceInsightsController::class, 'index'])
        ->middleware(['role:Principal', 'permission:view_teacher_performance'])
        ->name('principal.analytics.performance-insights.index');

    Route::get('/principal/analytics/teachers', [TeacherAnalyticsController::class, 'index'])
        ->middleware(['role:Principal', 'permission:view_teacher_performance'])
        ->name('principal.analytics.teachers.index');

    Route::get('/principal/analytics/teachers/data', [TeacherAnalyticsController::class, 'data'])
        ->middleware(['role:Principal', 'permission:view_teacher_performance'])
        ->name('principal.analytics.teachers.data');

    Route::get('/principal/analytics/teachers/{teacher}/detail', [TeacherAnalyticsController::class, 'detail'])
        ->middleware(['role:Principal', 'permission:view_teacher_performance'])
        ->name('principal.analytics.teachers.detail');

    Route::get('/principal/analytics/teacher-rankings', [TeacherRankingController::class, 'index'])
        ->middleware(['role:Principal', 'permission:view_teacher_performance'])
        ->name('principal.analytics.teacher-rankings.index');

    Route::post('/principal/analytics/teacher-rankings/regenerate', [TeacherRankingController::class, 'regenerate'])
        ->middleware(['role:Principal', 'permission:view_teacher_performance'])
        ->name('principal.analytics.teacher-rankings.regenerate');

    Route::get('/principal/acr', [TeacherAcrController::class, 'index'])
        ->middleware(['role:Admin|Principal', 'permission:view_teacher_acr'])
        ->name('principal.acr.index');

    Route::post('/principal/acr/generate', [TeacherAcrController::class, 'generate'])
        ->middleware(['role:Admin|Principal', 'permission:manage_teacher_acr'])
        ->name('principal.acr.generate');

    Route::get('/principal/acr/bulk-print', [TeacherAcrController::class, 'bulkPrintForm'])
        ->middleware(['role:Admin|Principal', 'permission:bulk_print_teacher_acr'])
        ->name('principal.acr.bulk-print.form');

    Route::post('/principal/acr/bulk-print', [TeacherAcrController::class, 'bulkPrint'])
        ->middleware(['role:Admin|Principal', 'permission:bulk_print_teacher_acr'])
        ->name('principal.acr.bulk-print');

    Route::post('/principal/acr/export-summary-excel', [TeacherAcrController::class, 'exportSummaryExcel'])
        ->middleware(['role:Admin|Principal', 'permission:export_teacher_acr_summary'])
        ->name('principal.acr.export-summary-excel');

    Route::get('/principal/acr/{acr}', [TeacherAcrController::class, 'show'])
        ->middleware(['role:Admin|Principal', 'permission:view_teacher_acr'])
        ->whereNumber('acr')
        ->name('principal.acr.show');

    Route::put('/principal/acr/{acr}', [TeacherAcrController::class, 'update'])
        ->middleware(['role:Admin|Principal', 'permission:manage_teacher_acr'])
        ->whereNumber('acr')
        ->name('principal.acr.update');

    Route::post('/principal/acr/{acr}/finalize', [TeacherAcrController::class, 'finalize'])
        ->middleware(['role:Admin|Principal', 'permission:finalize_teacher_acr'])
        ->whereNumber('acr')
        ->name('principal.acr.finalize');

    Route::post('/principal/acr/{acr}/refresh', [TeacherAcrController::class, 'refresh'])
        ->middleware(['role:Admin|Principal', 'permission:manage_teacher_acr'])
        ->whereNumber('acr')
        ->name('principal.acr.refresh');

    Route::get('/principal/acr/{acr}/print', [TeacherAcrController::class, 'print'])
        ->middleware(['role:Admin|Principal', 'permission:view_teacher_acr'])
        ->whereNumber('acr')
        ->name('principal.acr.print');

    Route::get('/principal/teacher-attendance', [PrincipalTeacherAttendanceController::class, 'index'])
        ->middleware(['role:Admin|Principal', 'permission:manage_teacher_attendance'])
        ->name('principal.teacher-attendance.index');

    Route::get('/principal/attendance', [PrincipalAttendanceController::class, 'index'])
        ->middleware(['role:Principal', 'permission:view_attendance'])
        ->name('principal.attendance.index');

    Route::get('/principal/teacher-attendance/create', [PrincipalTeacherAttendanceController::class, 'create'])
        ->middleware(['role:Admin|Principal', 'permission:manage_teacher_attendance'])
        ->name('principal.teacher-attendance.create');

    Route::post('/principal/teacher-attendance', [PrincipalTeacherAttendanceController::class, 'store'])
        ->middleware(['role:Admin|Principal', 'permission:manage_teacher_attendance'])
        ->name('principal.teacher-attendance.store');

    Route::get('/principal/teacher-attendance/{attendance}/edit', [PrincipalTeacherAttendanceController::class, 'edit'])
        ->middleware(['role:Admin|Principal', 'permission:manage_teacher_attendance'])
        ->whereNumber('attendance')
        ->name('principal.teacher-attendance.edit');

    Route::put('/principal/teacher-attendance/{attendance}', [PrincipalTeacherAttendanceController::class, 'update'])
        ->middleware(['role:Admin|Principal', 'permission:manage_teacher_attendance'])
        ->whereNumber('attendance')
        ->name('principal.teacher-attendance.update');

    Route::get('/principal/daily-diary', [DailyDiaryMonitoringController::class, 'index'])
        ->middleware(['role:Admin,Principal', 'permission:view_all_daily_diary'])
        ->name('principal.daily-diary.index');

    Route::get('/principal/daily-diary/completion-report', [DailyDiaryMonitoringController::class, 'completionReport'])
        ->middleware(['role:Admin,Principal', 'permission:monitor_daily_diary'])
        ->name('principal.daily-diary.completion-report');

    Route::get('/principal/analytics/performance-insights/data', [PerformanceInsightsController::class, 'data'])
        ->middleware(['role:Principal', 'permission:view_teacher_performance'])
        ->name('principal.analytics.performance-insights.data');

    Route::post('/principal/analytics/predict', [PerformanceInsightsController::class, 'predict'])
        ->middleware(['role:Principal', 'permission:view_teacher_performance'])
        ->name('principal.analytics.predict');

    Route::get('/principal/results/students', [PrincipalResultController::class, 'students'])
        ->middleware(['role:Admin|Principal', 'permission:generate_results'])
        ->name('principal.results.students');

    Route::get('/principal/results/preview', [PrincipalResultController::class, 'preview'])
        ->middleware(['role:Admin|Principal', 'permission:generate_results'])
        ->name('principal.results.preview');

    Route::post('/principal/results/publish', [PrincipalResultController::class, 'publish'])
        ->middleware(['role:Admin|Principal', 'permission:generate_results'])
        ->name('principal.results.publish');

    Route::get('/principal/results/marking-mode', [PrincipalResultController::class, 'markingModeContext'])
        ->middleware(['role:Admin|Principal', 'permission:generate_results'])
        ->name('principal.results.marking-mode.context');

    Route::post('/principal/results/marking-mode', [PrincipalResultController::class, 'updateMarkingMode'])
        ->middleware(['role:Admin|Principal', 'permission:generate_results'])
        ->name('principal.results.marking-mode.update');

    Route::get('/principal/results/card', [PrincipalResultController::class, 'card'])
        ->middleware(['role:Admin|Principal', 'permission:generate_results'])
        ->name('principal.results.card');

    Route::get('/principal/promotions', [PrincipalPromotionController::class, 'index'])
        ->middleware(['role:Admin|Principal', 'permission:generate_results'])
        ->name('principal.promotions.index');

    Route::get('/principal/promotions/create', [PrincipalPromotionController::class, 'create'])
        ->middleware(['role:Admin|Principal', 'permission:generate_results'])
        ->name('principal.promotions.create');

    Route::post('/principal/promotions/classes', [PrincipalPromotionController::class, 'storeClass'])
        ->middleware(['role:Admin|Principal', 'permission:generate_results'])
        ->name('principal.promotions.store-class');

    Route::post('/principal/promotions', [PrincipalPromotionController::class, 'storeCampaign'])
        ->middleware(['role:Admin|Principal', 'permission:generate_results'])
        ->name('principal.promotions.store');

    Route::post('/principal/promotions/undo-approved-executed', [PrincipalPromotionController::class, 'undoApprovedAndExecuted'])
        ->middleware(['role:Admin|Principal', 'permission:generate_results'])
        ->name('principal.promotions.undo-approved-executed');

    Route::get('/principal/promotions/{promotionCampaign}', [PrincipalPromotionController::class, 'show'])
        ->middleware(['role:Admin|Principal', 'permission:generate_results'])
        ->name('principal.promotions.show');

    Route::post('/principal/promotions/{promotionCampaign}/group-action', [PrincipalPromotionController::class, 'applyGroupAction'])
        ->middleware(['role:Admin|Principal', 'permission:generate_results'])
        ->name('principal.promotions.group-action');

    Route::put('/principal/promotions/{promotionCampaign}/review', [PrincipalPromotionController::class, 'review'])
        ->middleware(['role:Admin|Principal', 'permission:generate_results'])
        ->name('principal.promotions.review');

    Route::post('/principal/promotions/{promotionCampaign}/approve', [PrincipalPromotionController::class, 'approve'])
        ->middleware(['role:Admin|Principal', 'permission:generate_results'])
        ->name('principal.promotions.approve');

    Route::post('/principal/promotions/{promotionCampaign}/reject', [PrincipalPromotionController::class, 'reject'])
        ->middleware(['role:Admin|Principal', 'permission:generate_results'])
        ->name('principal.promotions.reject');

    Route::post('/principal/promotions/{promotionCampaign}/execute', [PrincipalPromotionController::class, 'execute'])
        ->middleware(['role:Admin|Principal', 'permission:generate_results'])
        ->name('principal.promotions.execute');

    Route::get('/results/analyzer', [ClassResultAnalyzerController::class, 'index'])
        ->middleware(['role:Principal,Teacher'])
        ->name('results.analyzer');

    Route::get('/results/promotion-analyzer', [PromotionAnalyzerController::class, 'index'])
        ->middleware(['role:Principal,Teacher'])
        ->name('results.promotion-analyzer');

    Route::get('/results/learning-profiles', [LearningProfileController::class, 'index'])
        ->middleware(['role:Principal,Teacher'])
        ->name('results.learning-profiles');

    Route::post('/results/learning-profiles/generate', [LearningProfileController::class, 'generate'])
        ->middleware(['role:Principal,Teacher'])
        ->name('results.learning-profiles.generate');

    Route::post('/results/learning-profiles/comment', [LearningProfileController::class, 'saveComment'])
        ->middleware(['role:Principal,Teacher'])
        ->name('results.learning-profiles.comment');

    Route::get('/principal/fees/structures', [FeeStructureController::class, 'index'])
        ->middleware(['role:Admin,Accountant', 'permission:view_fee_structure'])
        ->name('principal.fees.structures.index');

    Route::get('/principal/fees/structures/create', [FeeStructureController::class, 'create'])
        ->middleware(['role:Admin,Accountant', 'permission:create_fee_structure'])
        ->name('principal.fees.structures.create');

    Route::post('/principal/fees/structures', [FeeStructureController::class, 'store'])
        ->middleware(['role:Admin,Accountant', 'permission:create_fee_structure'])
        ->name('principal.fees.structures.store');

    Route::get('/principal/fees/structures/{feeStructure}/edit', [FeeStructureController::class, 'edit'])
        ->middleware(['role:Admin,Accountant', 'permission:edit_fee_structure'])
        ->name('principal.fees.structures.edit');

    Route::put('/principal/fees/structures/{feeStructure}', [FeeStructureController::class, 'update'])
        ->middleware(['role:Admin,Accountant', 'permission:edit_fee_structure'])
        ->name('principal.fees.structures.update');

    Route::delete('/principal/fees/structures/{feeStructure}', [FeeStructureController::class, 'destroy'])
        ->middleware(['role:Admin,Accountant', 'permission:delete_fee_structure'])
        ->name('principal.fees.structures.destroy');

    Route::get('/principal/fees/student-custom-fees', [StudentCustomFeeController::class, 'index'])
        ->middleware(['role:Admin,Accountant', 'permission:edit_fee_structure'])
        ->name('principal.fees.student-custom-fees.index');

    Route::post('/principal/fees/student-custom-fees', [StudentCustomFeeController::class, 'store'])
        ->middleware(['role:Admin,Accountant', 'permission:edit_fee_structure'])
        ->name('principal.fees.student-custom-fees.store');

    Route::delete('/principal/fees/student-custom-fees/{studentFeeStructure}', [StudentCustomFeeController::class, 'reset'])
        ->middleware(['role:Admin,Accountant', 'permission:edit_fee_structure'])
        ->name('principal.fees.student-custom-fees.reset');

    Route::get('/principal/fees/installment-plans', [FeeInstallmentPlanController::class, 'index'])
        ->middleware(['role:Admin,Accountant', 'permission:view_fee_structure'])
        ->name('principal.fees.installment-plans.index');

    Route::post('/principal/fees/installment-plans', [FeeInstallmentPlanController::class, 'store'])
        ->middleware(['role:Admin,Accountant', 'permission:edit_fee_structure'])
        ->name('principal.fees.installment-plans.store');

    Route::post('/principal/fees/installment-plans/installments/{feeInstallment}/pay', [FeeInstallmentPlanController::class, 'payInstallment'])
        ->middleware(['role:Admin,Accountant', 'permission:record_fee_payment'])
        ->name('principal.fees.installment-plans.installments.pay');

    Route::get('/principal/fees/add-arrears', [StudentArrearController::class, 'index'])
        ->middleware(['role:Admin,Accountant', 'permission:view_fee_reports'])
        ->name('principal.fees.add-arrears.index');

    Route::post('/principal/fees/add-arrears', [StudentArrearController::class, 'store'])
        ->middleware(['role:Admin,Accountant', 'permission:edit_fee_structure'])
        ->name('principal.fees.add-arrears.store');

    Route::post('/principal/fees/add-arrears/{studentArrear}/pay', [StudentArrearController::class, 'pay'])
        ->middleware(['role:Admin,Accountant', 'permission:record_fee_payment'])
        ->name('principal.fees.add-arrears.pay');

    Route::get('/principal/fees/challans/generate', [FeeChallanController::class, 'create'])
        ->middleware(['role:Admin,Accountant', 'permission:generate_fee_challans'])
        ->name('principal.fees.challans.generate');

    Route::post('/principal/fees/challans/generate', [FeeChallanController::class, 'store'])
        ->middleware(['role:Admin,Accountant', 'permission:generate_fee_challans'])
        ->name('principal.fees.challans.store');

    Route::get('/principal/fees/challans', [FeeChallanController::class, 'index'])
        ->middleware(['role:Admin,Accountant', 'permission:view_fee_challans'])
        ->name('principal.fees.challans.index');

    Route::get('/principal/fees/challans/data', [FeeChallanController::class, 'data'])
        ->middleware(['role:Admin,Accountant', 'permission:view_fee_challans'])
        ->name('principal.fees.challans.data');

    Route::get('/principal/fees/challans/fee-structure-preview', [FeeChallanController::class, 'feeStructurePreview'])
        ->middleware(['role:Admin,Accountant', 'permission:view_fee_challans,generate_fee_challans'])
        ->name('principal.fees.challans.fee-structure-preview');

    Route::get('/principal/fees/challans/class-pdf', [FeeChallanController::class, 'classPdf'])
        ->middleware(['role:Admin,Accountant', 'permission:view_fee_challans'])
        ->name('principal.fees.challans.class-pdf');

    Route::get('/principal/fees/challans/{feeChallan}', [FeeChallanController::class, 'show'])
        ->middleware(['role:Admin,Accountant', 'permission:view_fee_challans'])
        ->name('principal.fees.challans.show');

    Route::get('/principal/fees/challans/{feeChallan}/pdf', [FeeChallanController::class, 'pdf'])
        ->middleware(['role:Admin,Accountant', 'permission:view_fee_challans'])
        ->name('principal.fees.challans.pdf');

    Route::post('/principal/fees/challans/{feeChallan}/mark-paid', [FeeChallanController::class, 'markPaid'])
        ->middleware(['role:Admin,Accountant', 'permission:record_fee_payment'])
        ->name('principal.fees.challans.mark-paid');

    Route::post('/principal/fees/challans/{feeChallan}/waive-late-fee', [FeeChallanController::class, 'waiveLateFee'])
        ->middleware(['role:Admin,Accountant', 'permission:record_fee_payment'])
        ->name('principal.fees.challans.waive-late-fee');

    Route::get('/principal/fees/payments', [FeePaymentController::class, 'index'])
        ->middleware(['role:Admin,Accountant', 'permission:record_fee_payment'])
        ->name('principal.fees.payments.index');

    Route::post('/principal/fees/payments', [FeePaymentController::class, 'store'])
        ->middleware(['role:Admin,Accountant', 'permission:record_fee_payment'])
        ->name('principal.fees.payments.store');

    Route::get('/principal/fees/reports', [FeeReportController::class, 'index'])
        ->middleware(['role:Admin,Accountant', 'permission:view_fee_reports'])
        ->name('principal.fees.reports.index');

    Route::get('/principal/fees/reports/arrears', [FeeReportController::class, 'arrears'])
        ->middleware(['role:Admin,Accountant', 'permission:view_fee_reports'])
        ->name('principal.fees.reports.arrears');

    Route::get('/principal/fees/defaulters', [FeeDefaulterController::class, 'index'])
        ->middleware(['role:Admin,Principal,Accountant'])
        ->name('principal.fees.defaulters.index');

    Route::post('/principal/fees/defaulters/{feeDefaulter}/send-reminder', [FeeDefaulterController::class, 'sendReminder'])
        ->middleware(['role:Admin,Principal,Accountant'])
        ->name('principal.fees.defaulters.send-reminder');

    Route::post('/principal/fees/defaulters/{feeDefaulter}/note', [FeeDefaulterController::class, 'addNote'])
        ->middleware(['role:Admin,Principal,Accountant'])
        ->name('principal.fees.defaulters.add-note');

    Route::post('/principal/fees/defaulters/{feeDefaulter}/override', [FeeDefaulterController::class, 'createOverride'])
        ->middleware(['role:Admin,Principal'])
        ->name('principal.fees.defaulters.create-override');

    Route::post('/principal/fees/defaulters/{feeDefaulter}/waive-late-fee', [FeeDefaulterController::class, 'waiveLateFee'])
        ->middleware(['role:Admin,Principal,Accountant'])
        ->name('principal.fees.defaulters.waive-late-fee');

    Route::get('/principal/payroll/profiles', [PayrollProfileController::class, 'index'])
        ->middleware(['role:Admin,Accountant', 'permission:view_payroll'])
        ->name('principal.payroll.profiles.index');

    Route::get('/principal/payroll', [PayrollDashboardController::class, 'index'])
        ->middleware(['role:Admin,Accountant', 'permission:view_payroll,manage_payroll_profiles,manage_payroll,generate_payroll,generate_salary_sheet,view_salary_slips,edit_salary_structure,view_payroll_reports'])
        ->name('principal.payroll.dashboard');

    Route::get('/principal/payroll/data', [PayrollDashboardController::class, 'data'])
        ->middleware(['role:Admin,Accountant', 'permission:view_payroll,manage_payroll_profiles,manage_payroll,generate_payroll,generate_salary_sheet,view_salary_slips,edit_salary_structure,view_payroll_reports'])
        ->name('principal.payroll.dashboard.data');

    Route::get('/principal/payroll/items/{payrollItem}', [PayrollDashboardController::class, 'item'])
        ->middleware(['role:Admin,Accountant', 'permission:view_payroll,manage_payroll_profiles,manage_payroll,generate_payroll,generate_salary_sheet,view_salary_slips,edit_salary_structure,view_payroll_reports'])
        ->name('principal.payroll.dashboard.item');

    Route::get('/principal/payroll/profiles/{payrollProfile}/detail', [PayrollDashboardController::class, 'profile'])
        ->middleware(['role:Admin,Accountant', 'permission:view_payroll,manage_payroll_profiles,manage_payroll,generate_payroll,generate_salary_sheet,view_salary_slips,edit_salary_structure,view_payroll_reports'])
        ->name('principal.payroll.dashboard.profile');

    Route::post('/principal/payroll/profiles', [PayrollProfileController::class, 'store'])
        ->middleware(['role:Admin,Accountant', 'permission:manage_payroll_profiles,manage_payroll'])
        ->name('principal.payroll.profiles.store');

    Route::get('/principal/payroll/profiles/{payrollProfile}/edit', [PayrollProfileController::class, 'edit'])
        ->middleware(['role:Admin,Accountant', 'permission:manage_payroll_profiles,edit_salary_structure'])
        ->name('principal.payroll.profiles.edit');

    Route::put('/principal/payroll/profiles/{payrollProfile}', [PayrollProfileController::class, 'update'])
        ->middleware(['role:Admin,Accountant', 'permission:manage_payroll_profiles,edit_salary_structure'])
        ->name('principal.payroll.profiles.update');

    Route::get('/principal/payroll/generate', [PayrollRunController::class, 'generateForm'])
        ->middleware(['role:Admin,Accountant', 'permission:generate_payroll,generate_salary_sheet'])
        ->name('principal.payroll.generate.index');

    Route::post('/principal/payroll/generate', [PayrollRunController::class, 'generate'])
        ->middleware(['role:Admin,Accountant', 'permission:generate_payroll,generate_salary_sheet'])
        ->name('principal.payroll.generate.run');

    Route::get('/principal/payroll/salary-sheet', [PayrollRunController::class, 'salarySheet'])
        ->middleware(['role:Admin,Accountant', 'permission:generate_payroll,generate_salary_sheet'])
        ->name('principal.payroll.sheet.index');

    Route::get('/principal/payroll/salary-slips', [PayrollRunController::class, 'salarySlips'])
        ->middleware(['role:Admin,Accountant', 'permission:view_salary_slips'])
        ->name('principal.payroll.slips.index');

    Route::get('/principal/payroll/salary-slips/{payrollItem}/pdf', [PayrollRunController::class, 'salarySlipPdf'])
        ->middleware(['role:Admin,Accountant', 'permission:view_salary_slips'])
        ->name('principal.payroll.slips.pdf');

    Route::get('/principal/payroll/reports', [PayrollRunController::class, 'reports'])
        ->middleware(['role:Admin,Accountant', 'permission:view_payroll_reports,view_payroll'])
        ->name('principal.payroll.reports.index');

    Route::get('/principal/reports', [ReportPdfController::class, 'index'])
        ->middleware(['role:Principal'])
        ->name('reports.index');

    Route::get('/reports/student-result-card/pdf', [ReportPdfController::class, 'studentResultCardPdf'])
        ->middleware(['role:Admin|Principal', 'permission:generate_results'])
        ->name('reports.pdf.student-result-card');

    Route::get('/reports/class-result/pdf', [ReportPdfController::class, 'classResultPdf'])
        ->middleware(['role:Admin|Principal', 'permission:generate_results'])
        ->name('reports.pdf.class-result');

    Route::get('/reports/class-result-cards/pdf', [ReportPdfController::class, 'classResultCardsPdf'])
        ->middleware(['role:Admin|Principal', 'permission:generate_results'])
        ->name('reports.pdf.class-result-cards');

    Route::get('/reports/gazette/pdf', [ResultSheetController::class, 'gazettePdf'])
        ->middleware(['role:Admin|Principal', 'permission:generate_results'])
        ->name('reports.pdf.gazette');

    Route::get('/reports/tabulation/pdf', [ResultSheetController::class, 'tabulationPdf'])
        ->middleware(['role:Admin|Principal', 'permission:generate_results'])
        ->name('reports.pdf.tabulation');

    Route::get('/principal/admit-cards', [AdmitCardController::class, 'index'])
        ->middleware(['role:Admin|Principal', 'permission:generate_results'])
        ->name('principal.admit-cards.index');

    Route::get('/principal/exams/seating-plan', [ExamSeatingPlanController::class, 'index'])
        ->middleware(['role:Admin|Principal', 'permission:generate_results'])
        ->name('principal.exams.seating-plans.index');

    Route::post('/principal/exams/seating-plan/rooms', [ExamSeatingPlanController::class, 'storeRoom'])
        ->middleware(['role:Admin|Principal', 'permission:generate_results'])
        ->name('principal.exams.seating-plans.rooms.store');

    Route::post('/principal/exams/seating-plan/generate', [ExamSeatingPlanController::class, 'generate'])
        ->middleware(['role:Admin|Principal', 'permission:generate_results'])
        ->name('principal.exams.seating-plans.generate');

    Route::get('/principal/exams/seating-plan/{examSeatingPlan}', [ExamSeatingPlanController::class, 'show'])
        ->middleware(['role:Admin|Principal', 'permission:generate_results'])
        ->name('principal.exams.seating-plans.show');

    Route::get('/principal/exams/seating-plan/{examSeatingPlan}/print', [ExamSeatingPlanController::class, 'print'])
        ->middleware(['role:Admin|Principal', 'permission:generate_results'])
        ->name('principal.exams.seating-plans.print');

    Route::get('/principal/exams/seating-plan/{examSeatingPlan}/seat-slip/{examSeatAssignment}', [ExamSeatingPlanController::class, 'seatSlip'])
        ->middleware(['role:Admin|Principal', 'permission:generate_results'])
        ->name('principal.exams.seating-plans.seat-slip');

    Route::get('/principal/exams/room-invigilators', [ExamRoomInvigilatorController::class, 'index'])
        ->middleware(['role:Admin|Principal', 'permission:generate_results'])
        ->name('principal.exams.room-invigilators.index');

    Route::post('/principal/exams/room-invigilators', [ExamRoomInvigilatorController::class, 'store'])
        ->middleware(['role:Admin|Principal', 'permission:generate_results'])
        ->name('principal.exams.room-invigilators.store');

    Route::delete('/principal/exams/room-invigilators/{examRoomInvigilator}', [ExamRoomInvigilatorController::class, 'destroy'])
        ->middleware(['role:Admin|Principal', 'permission:generate_results'])
        ->name('principal.exams.room-invigilators.destroy');

    Route::get('/exams/hall-attendance', [ExamHallAttendanceController::class, 'index'])
        ->middleware(['role:Admin|Principal|Teacher'])
        ->name('exams.hall-attendance.index');

    Route::get('/exams/hall-attendance/options', [ExamHallAttendanceController::class, 'options'])
        ->middleware(['role:Admin|Principal|Teacher'])
        ->name('exams.hall-attendance.options');

    Route::get('/exams/hall-attendance/sheet', [ExamHallAttendanceController::class, 'sheet'])
        ->middleware(['role:Admin|Principal|Teacher'])
        ->name('exams.hall-attendance.sheet');

    Route::post('/exams/hall-attendance/save', [ExamHallAttendanceController::class, 'save'])
        ->middleware(['role:Admin|Principal|Teacher'])
        ->name('exams.hall-attendance.save');

    Route::get('/exams/hall-attendance/room-sheet/pdf', [ExamHallAttendanceController::class, 'roomSheetPdf'])
        ->middleware(['role:Admin|Principal|Teacher'])
        ->name('exams.hall-attendance.room-sheet-pdf');

    Route::get('/principal/exams/live-attendance-monitor', [LiveExamAttendanceMonitorController::class, 'index'])
        ->middleware(['role:Admin|Principal', 'permission:generate_results'])
        ->name('principal.exams.live-attendance-monitor.index');

    Route::get('/principal/exams/live-attendance-monitor/data', [LiveExamAttendanceMonitorController::class, 'data'])
        ->middleware(['role:Admin|Principal', 'permission:generate_results'])
        ->name('principal.exams.live-attendance-monitor.data');

    Route::post('/principal/admit-cards/exam-sessions', [AdmitCardController::class, 'storeExamSession'])
        ->middleware(['role:Admin|Principal', 'permission:generate_results'])
        ->name('principal.admit-cards.exam-sessions.store');

    Route::get('/principal/admit-cards/single/pdf', [AdmitCardController::class, 'singlePdf'])
        ->middleware(['role:Admin|Principal', 'permission:generate_results'])
        ->name('principal.admit-cards.single-pdf');

    Route::get('/principal/admit-cards/class/pdf', [AdmitCardController::class, 'classPdf'])
        ->middleware(['role:Admin|Principal', 'permission:generate_results'])
        ->name('principal.admit-cards.class-pdf');

    Route::get('/principal/admit-cards/overrides', [AdmitCardController::class, 'overrides'])
        ->middleware(['role:Admin|Principal', 'permission:generate_results'])
        ->name('principal.admit-cards.overrides.index');

    Route::post('/principal/admit-cards/overrides', [AdmitCardController::class, 'storeOverride'])
        ->middleware(['role:Admin|Principal', 'permission:generate_results'])
        ->name('principal.admit-cards.overrides.store');

    Route::get('/id-card/{student}', [StudentIdCardController::class, 'single'])
        ->middleware(['role:Admin,Principal'])
        ->name('idcards.single');

    Route::get('/id-card/class/{class}', [StudentIdCardController::class, 'bulk'])
        ->middleware(['role:Admin,Principal'])
        ->name('idcards.class');

    Route::get('/reports/attendance/pdf', [ReportPdfController::class, 'attendanceReportPdf'])
        ->middleware(['role:Principal', 'permission:view_attendance'])
        ->name('reports.pdf.attendance-report');

    Route::get('/principal/medical/referrals', [MedicalReferralController::class, 'principalIndex'])
        ->middleware(['role:Principal', 'permission:create_medical_requests'])
        ->name('principal.medical.referrals.index');

    Route::get('/principal/medical/referrals/data', [MedicalReferralController::class, 'principalData'])
        ->middleware(['role:Principal', 'permission:view_medical_requests'])
        ->name('principal.medical.referrals.data');

    Route::post('/principal/medical/referrals', [MedicalReferralController::class, 'store'])
        ->middleware(['role:Principal', 'permission:create_medical_requests'])
        ->name('principal.medical.referrals.store');

    Route::get('/medical/students/search', [MedicalReferralController::class, 'searchStudents'])
        ->middleware(['role:Principal,Doctor', 'permission:view_medical_requests'])
        ->name('medical.students.search');

    Route::get('/medical/reports', [MedicalReferralController::class, 'reportsIndex'])
        ->middleware(['role:Principal,Doctor', 'permission:view_medical_requests'])
        ->name('medical.reports.index');

    Route::get('/medical/reports/data', [MedicalReferralController::class, 'reportsData'])
        ->middleware(['role:Principal,Doctor', 'permission:view_medical_requests'])
        ->name('medical.reports.data');

    Route::get('/reports/medical/pdf', [ReportPdfController::class, 'medicalReportPdf'])
        ->middleware(['role:Principal,Doctor', 'permission:view_medical_requests'])
        ->name('reports.pdf.medical-report');

    Route::get('/api/search/students', [StudentSearchController::class, 'students'])
        ->middleware('role:Principal,Admin')
        ->name('api.search.students');

    Route::get('/api/timetable/class', [TimetableViewerController::class, 'classApi'])
        ->middleware(['role:Principal'])
        ->name('api.timetable.class');

    Route::get('/api/timetable/teacher', [TimetableViewerController::class, 'teacherApi'])
        ->middleware(['role:Principal,Teacher'])
        ->name('api.timetable.teacher');

    Route::post('/api/timetable/entry/update', [TimetableEntryController::class, 'update'])
        ->middleware(['role:Principal'])
        ->name('api.timetable.entry.update');

    Route::get('/teacher/dashboard', TeacherDashboardController::class)
        ->middleware('role:Teacher')
        ->name('teacher.dashboard');

    Route::get('/teacher/e-resources', [TeacherEResourceController::class, 'index'])
        ->middleware(['role:Principal,Teacher'])
        ->name('teacher.e-resources.index');

    Route::get('/teacher/e-resources/file', [TeacherEResourceController::class, 'file'])
        ->middleware(['role:Principal,Teacher'])
        ->name('teacher.e-resources.file');

    Route::get('/teacher/daily-diary', [DailyDiaryController::class, 'index'])
        ->middleware(['role:Teacher', 'permission:view_own_daily_diary_entries'])
        ->name('teacher.daily-diary.index');

    Route::get('/teacher/daily-diary/create', [DailyDiaryController::class, 'create'])
        ->middleware(['role:Teacher', 'permission:create_daily_diary'])
        ->name('teacher.daily-diary.create');

    Route::post('/teacher/daily-diary', [DailyDiaryController::class, 'store'])
        ->middleware(['role:Teacher', 'permission:create_daily_diary'])
        ->name('teacher.daily-diary.store');

    Route::get('/teacher/daily-diary/{dailyDiary}/edit', [DailyDiaryController::class, 'edit'])
        ->middleware(['role:Teacher', 'permission:edit_own_daily_diary'])
        ->whereNumber('dailyDiary')
        ->name('teacher.daily-diary.edit');

    Route::put('/teacher/daily-diary/{dailyDiary}', [DailyDiaryController::class, 'update'])
        ->middleware(['role:Teacher', 'permission:edit_own_daily_diary'])
        ->whereNumber('dailyDiary')
        ->name('teacher.daily-diary.update');

    Route::prefix('teacher/my-inventory')
        ->name('teacher.my-inventory.')
        ->middleware(['role:Teacher'])
        ->group(function (): void {
            Route::get('/', [TeacherInventoryController::class, 'index'])
                ->middleware(['permission:view_own_inventory,view_own_inventory_demands,create_inventory_demand,submit_device_declaration'])
                ->name('index');

            Route::get('/demands', [TeacherInventoryDemandController::class, 'index'])
                ->middleware(['permission:view_own_inventory_demands'])
                ->name('demands.index');

            Route::get('/demands/create', [TeacherInventoryDemandController::class, 'create'])
                ->middleware(['permission:create_inventory_demand'])
                ->name('demands.create');

            Route::post('/demands', [TeacherInventoryDemandController::class, 'store'])
                ->middleware(['permission:create_inventory_demand'])
                ->name('demands.store');

            Route::get('/demands/{demand}', [TeacherInventoryDemandController::class, 'show'])
                ->middleware(['permission:view_own_inventory_demands'])
                ->name('demands.show');

            Route::put('/demands/{demand}', [TeacherInventoryDemandController::class, 'update'])
                ->middleware(['permission:create_inventory_demand'])
                ->name('demands.update');

            Route::get('/devices', [TeacherDeviceDeclarationController::class, 'index'])
                ->middleware(['permission:submit_device_declaration,view_own_inventory'])
                ->name('devices.index');

            Route::get('/devices/create', [TeacherDeviceDeclarationController::class, 'create'])
                ->middleware(['permission:submit_device_declaration'])
                ->name('devices.create');

            Route::post('/devices', [TeacherDeviceDeclarationController::class, 'store'])
                ->middleware(['permission:submit_device_declaration'])
                ->name('devices.store');
        });

    Route::get('/teacher/attendance', [TeacherAttendanceController::class, 'index'])
        ->middleware(['role:Teacher', 'permission:mark_attendance'])
        ->name('teacher.attendance.index');

    Route::get('/teacher/attendance/options', [TeacherAttendanceController::class, 'options'])
        ->middleware(['role:Teacher', 'permission:mark_attendance'])
        ->name('teacher.attendance.options');

    Route::get('/teacher/attendance/sheet', [TeacherAttendanceController::class, 'sheet'])
        ->middleware(['role:Teacher', 'permission:mark_attendance'])
        ->name('teacher.attendance.sheet');

    Route::post('/teacher/attendance/mark', [TeacherAttendanceController::class, 'mark'])
        ->middleware(['role:Teacher', 'permission:mark_attendance'])
        ->name('teacher.attendance.mark');

    Route::get('/teacher/exams', [TeacherExamController::class, 'index'])
        ->middleware(['role:Teacher', 'permission:enter_marks'])
        ->name('teacher.exams.index');

    Route::get('/teacher/timetable', [TimetableViewerController::class, 'teacherViewer'])
        ->middleware(['role:Teacher'])
        ->name('teacher.timetable.index');

    Route::get('/teacher/exams/options', [TeacherExamController::class, 'options'])
        ->middleware(['role:Teacher', 'permission:enter_marks'])
        ->name('teacher.exams.options');

    Route::get('/teacher/exams/sheet', [TeacherExamController::class, 'sheet'])
        ->middleware(['role:Teacher', 'permission:enter_marks'])
        ->name('teacher.exams.sheet');

    Route::post('/teacher/exams/save', [TeacherExamController::class, 'save'])
        ->middleware(['role:Teacher', 'permission:enter_marks'])
        ->name('teacher.exams.save');

    Route::get('/teacher/promotions', [TeacherPromotionController::class, 'index'])
        ->middleware(['role:Teacher', 'permission:enter_marks'])
        ->name('teacher.promotions.index');

    Route::post('/teacher/promotions', [TeacherPromotionController::class, 'store'])
        ->middleware(['role:Teacher', 'permission:enter_marks'])
        ->name('teacher.promotions.store');

    Route::get('/teacher/promotions/{promotionCampaign}', [TeacherPromotionController::class, 'show'])
        ->middleware(['role:Teacher', 'permission:enter_marks'])
        ->name('teacher.promotions.show');

    Route::put('/teacher/promotions/{promotionCampaign}', [TeacherPromotionController::class, 'update'])
        ->middleware(['role:Teacher', 'permission:enter_marks'])
        ->name('teacher.promotions.update');

    Route::post('/teacher/promotions/{promotionCampaign}/submit', [TeacherPromotionController::class, 'submit'])
        ->middleware(['role:Teacher', 'permission:enter_marks'])
        ->name('teacher.promotions.submit');

    Route::get('/teacher/results/class', [TeacherResultController::class, 'classResults'])
        ->middleware(['role:Teacher', 'permission:enter_marks'])
        ->name('teacher.results.class');

    Route::get('/teacher/my-mark-entries', [TeacherMarkEntryController::class, 'index'])
        ->middleware(['role:Teacher', 'permission:view_own_mark_entries'])
        ->name('teacher.marks.entries.index');

    Route::get('/teacher/my-mark-entries/{mark}/edit', [TeacherMarkEntryController::class, 'edit'])
        ->middleware(['role:Teacher', 'permission:edit_own_mark_entries'])
        ->name('teacher.marks.entries.edit');

    Route::put('/teacher/my-mark-entries/{mark}', [TeacherMarkEntryController::class, 'update'])
        ->middleware(['role:Teacher', 'permission:edit_own_mark_entries'])
        ->name('teacher.marks.entries.update');

    Route::delete('/teacher/my-mark-entries/{mark}', [TeacherMarkEntryController::class, 'destroy'])
        ->middleware(['role:Teacher', 'permission:delete_own_mark_entries'])
        ->name('teacher.marks.entries.destroy');

    Route::get('/doctor/medical/referrals', [MedicalReferralController::class, 'doctorIndex'])
        ->middleware(['role:Doctor', 'permission:view_medical_requests'])
        ->name('doctor.medical.referrals.index');

    Route::get('/doctor/medical/requests-list', [DoctorMedicalRequestListController::class, 'index'])
        ->middleware(['role:Doctor', 'permission:view_medical_requests'])
        ->name('doctor.medical.requests-list');

    Route::get('/doctor/medical/referrals/data', [MedicalReferralController::class, 'doctorData'])
        ->middleware(['role:Doctor', 'permission:view_medical_requests'])
        ->name('doctor.medical.referrals.data');

    Route::get('/doctor/medical/referrals/notifications', [MedicalReferralController::class, 'doctorNotifications'])
        ->middleware(['role:Doctor', 'permission:view_medical_requests'])
        ->name('doctor.medical.referrals.notifications');

    Route::put('/doctor/medical/referrals/{medicalReferral}', [MedicalReferralController::class, 'update'])
        ->middleware(['role:Doctor', 'permission:view_medical_requests'])
        ->name('doctor.medical.referrals.update');

    Route::get('/doctor/dashboard', DoctorDashboardController::class)
        ->middleware('role:Doctor')
        ->name('doctor.dashboard');

    Route::get('/warden/dashboard', WardenDashboardController::class)
        ->middleware(['role:Warden'])
        ->name('warden.dashboard');

    Route::get('/warden/daily-diary', [WardenDailyDiaryController::class, 'index'])
        ->middleware(['role:Warden', 'permission:view_all_daily_diary'])
        ->name('warden.daily-diary.index');

    Route::get('/warden/daily-diary/{dailyDiary}', [WardenDailyDiaryController::class, 'show'])
        ->middleware(['role:Warden', 'permission:view_all_daily_diary'])
        ->whereNumber('dailyDiary')
        ->name('warden.daily-diary.show');

    Route::get('/warden/discipline-reports', [WardenDisciplineController::class, 'index'])
        ->middleware(['role:Warden', 'permission:view_student_discipline_reports'])
        ->name('warden.discipline-reports.index');

    Route::get('/warden/discipline-reports/{report}', [WardenDisciplineController::class, 'show'])
        ->middleware(['role:Warden', 'permission:view_student_discipline_reports'])
        ->whereNumber('report')
        ->name('warden.discipline-reports.show');

    Route::get('/warden/students', [WardenStudentRecordController::class, 'index'])
        ->middleware(['role:Warden', 'permission:view_student_profiles_basic'])
        ->name('warden.students.index');

    Route::get('/warden/students/{student}', [WardenStudentRecordController::class, 'show'])
        ->middleware(['role:Warden', 'permission:view_student_academic_records'])
        ->whereNumber('student')
        ->name('warden.students.show');

    Route::get('/warden/hostel/rooms', [HostelRoomController::class, 'index'])
        ->middleware(['role:Warden', 'permission:manage_hostel_rooms'])
        ->name('warden.hostel.rooms.index');

    Route::get('/warden/hostel/rooms/create', [HostelRoomController::class, 'create'])
        ->middleware(['role:Warden', 'permission:manage_hostel_rooms'])
        ->name('warden.hostel.rooms.create');

    Route::post('/warden/hostel/rooms', [HostelRoomController::class, 'store'])
        ->middleware(['role:Warden', 'permission:manage_hostel_rooms'])
        ->name('warden.hostel.rooms.store');

    Route::get('/warden/hostel/rooms/{room}/edit', [HostelRoomController::class, 'edit'])
        ->middleware(['role:Warden', 'permission:manage_hostel_rooms'])
        ->whereNumber('room')
        ->name('warden.hostel.rooms.edit');

    Route::put('/warden/hostel/rooms/{room}', [HostelRoomController::class, 'update'])
        ->middleware(['role:Warden', 'permission:manage_hostel_rooms'])
        ->whereNumber('room')
        ->name('warden.hostel.rooms.update');

    Route::get('/warden/hostel/allocations', [HostelRoomAllocationController::class, 'index'])
        ->middleware(['role:Warden', 'permission:view_hostel_room_allocations'])
        ->name('warden.hostel.allocations.index');

    Route::get('/warden/hostel/allocations/create', [HostelRoomAllocationController::class, 'create'])
        ->middleware(['role:Warden', 'permission:assign_students_to_rooms'])
        ->name('warden.hostel.allocations.create');

    Route::post('/warden/hostel/allocations', [HostelRoomAllocationController::class, 'store'])
        ->middleware(['role:Warden', 'permission:assign_students_to_rooms'])
        ->name('warden.hostel.allocations.store');

    Route::get('/warden/hostel/allocations/{student}/shift', [HostelRoomAllocationController::class, 'editShift'])
        ->middleware(['role:Warden', 'permission:assign_students_to_rooms'])
        ->whereNumber('student')
        ->name('warden.hostel.allocations.shift.edit');

    Route::put('/warden/hostel/allocations/{student}/shift', [HostelRoomAllocationController::class, 'shift'])
        ->middleware(['role:Warden', 'permission:assign_students_to_rooms'])
        ->whereNumber('student')
        ->name('warden.hostel.allocations.shift.update');

    Route::post('/warden/hostel/allocations/{student}/remove', [HostelRoomAllocationController::class, 'remove'])
        ->middleware(['role:Warden', 'permission:assign_students_to_rooms'])
        ->whereNumber('student')
        ->name('warden.hostel.allocations.remove');

    Route::get('/warden/hostel/rooms/{room}/students', [HostelRoomAllocationController::class, 'roomStudents'])
        ->middleware(['role:Warden', 'permission:view_hostel_room_allocations'])
        ->whereNumber('room')
        ->name('warden.hostel.rooms.students');

    Route::get('/warden/hostel/leaves', [HostelLeaveController::class, 'index'])
        ->middleware(['role:Warden', 'permission:manage_hostel_leave'])
        ->name('warden.hostel.leaves.index');

    Route::get('/warden/hostel/leaves/create', [HostelLeaveController::class, 'create'])
        ->middleware(['role:Warden', 'permission:manage_hostel_leave'])
        ->name('warden.hostel.leaves.create');

    Route::post('/warden/hostel/leaves', [HostelLeaveController::class, 'store'])
        ->middleware(['role:Warden', 'permission:manage_hostel_leave'])
        ->name('warden.hostel.leaves.store');

    Route::get('/warden/hostel/leaves/{leave}', [HostelLeaveController::class, 'show'])
        ->middleware(['role:Warden', 'permission:manage_hostel_leave'])
        ->whereNumber('leave')
        ->name('warden.hostel.leaves.show');

    Route::post('/warden/hostel/leaves/{leave}/approve', [HostelLeaveController::class, 'approve'])
        ->middleware(['role:Warden', 'permission:manage_hostel_leave'])
        ->whereNumber('leave')
        ->name('warden.hostel.leaves.approve');

    Route::post('/warden/hostel/leaves/{leave}/reject', [HostelLeaveController::class, 'reject'])
        ->middleware(['role:Warden', 'permission:manage_hostel_leave'])
        ->whereNumber('leave')
        ->name('warden.hostel.leaves.reject');

    Route::post('/warden/hostel/leaves/{leave}/returned', [HostelLeaveController::class, 'returned'])
        ->middleware(['role:Warden', 'permission:manage_hostel_leave'])
        ->whereNumber('leave')
        ->name('warden.hostel.leaves.returned');

    Route::get('/warden/hostel/night-attendance', [HostelNightAttendanceController::class, 'index'])
        ->middleware(['role:Warden', 'permission:view_night_attendance'])
        ->name('warden.hostel.night-attendance.index');

    Route::get('/warden/hostel/night-attendance/create', [HostelNightAttendanceController::class, 'create'])
        ->middleware(['role:Warden', 'permission:mark_night_attendance'])
        ->name('warden.hostel.night-attendance.create');

    Route::post('/warden/hostel/night-attendance', [HostelNightAttendanceController::class, 'store'])
        ->middleware(['role:Warden', 'permission:mark_night_attendance'])
        ->name('warden.hostel.night-attendance.store');

    Route::get('/student/dashboard', StudentDashboardController::class)
        ->middleware('role:Student')
        ->name('student.dashboard');

    Route::get('/student/results', [StudentResultController::class, 'index'])
        ->middleware('role:Student')
        ->name('student.results.index');

    Route::get('/student/daily-diary', [StudentDailyDiaryController::class, 'index'])
        ->middleware(['role:Student', 'permission:view_student_daily_diary'])
        ->name('student.daily-diary.index');

    Route::prefix('student/assessments')
        ->name('student.assessments.')
        ->middleware(['role:Student'])
        ->group(function (): void {
            Route::get('/', [\App\Modules\Assessments\Controllers\StudentCognitiveAssessmentController::class, 'index'])
                ->middleware(['permission:take_cognitive_assessment,view_own_cognitive_results'])
                ->name('index');

            Route::prefix('cognitive-skills-level-4')
                ->name('cognitive-skills-level-4.')
                ->group(function (): void {
                    Route::get('/', [\App\Modules\Assessments\Controllers\StudentCognitiveAssessmentController::class, 'showLevelFour'])
                        ->middleware(['permission:take_cognitive_assessment,view_own_cognitive_results'])
                        ->name('index');

                    Route::post('/', [\App\Modules\Assessments\Controllers\StudentCognitiveAssessmentController::class, 'start'])
                        ->middleware(['permission:take_cognitive_assessment'])
                        ->name('start');

                    Route::get('/attempt/{attempt}', [\App\Modules\Assessments\Controllers\StudentCognitiveAssessmentController::class, 'attempt'])
                        ->middleware(['permission:take_cognitive_assessment'])
                        ->name('attempt');

                    Route::post('/attempt/{attempt}/responses', [\App\Modules\Assessments\Controllers\StudentCognitiveAssessmentController::class, 'saveResponses'])
                        ->middleware(['permission:take_cognitive_assessment'])
                        ->name('responses.store');

                    Route::post('/attempt/{attempt}/submit', [\App\Modules\Assessments\Controllers\StudentCognitiveAssessmentController::class, 'submit'])
                        ->middleware(['permission:take_cognitive_assessment'])
                        ->name('submit');

                    Route::get('/result/{attempt}', [\App\Modules\Assessments\Controllers\StudentCognitiveAssessmentController::class, 'result'])
                        ->middleware(['permission:view_own_cognitive_results'])
                        ->name('result');
                });
        });

    Route::get('/principal/assessments/cognitive-skills-level-4-reports', [\App\Modules\Assessments\Controllers\PrincipalCognitiveAssessmentReportController::class, 'index'])
        ->middleware(['role:Principal', 'permission:view_cognitive_assessment_reports'])
        ->name('principal.assessments.cognitive-skills-level-4-reports.index');

    Route::get('/principal/assessments/cognitive-skills-level-4/students', [\App\Modules\Assessments\Controllers\PrincipalCognitiveAssessmentAccessController::class, 'index'])
        ->middleware(['role:Admin,Principal', 'permission:manage_student_cognitive_assessment_access'])
        ->name('principal.assessments.cognitive-skills-level-4.students.index');

    Route::post('/principal/assessments/cognitive-skills-level-4/{assessment}/students/{student}/enable', [\App\Modules\Assessments\Controllers\PrincipalCognitiveAssessmentAccessController::class, 'enableStudent'])
        ->middleware(['role:Admin,Principal', 'permission:manage_student_cognitive_assessment_access'])
        ->name('principal.assessments.cognitive-skills-level-4.students.enable');

    Route::post('/principal/assessments/cognitive-skills-level-4/{assessment}/students/{student}/disable', [\App\Modules\Assessments\Controllers\PrincipalCognitiveAssessmentAccessController::class, 'disableStudent'])
        ->middleware(['role:Admin,Principal', 'permission:manage_student_cognitive_assessment_access'])
        ->name('principal.assessments.cognitive-skills-level-4.students.disable');

    Route::post('/principal/assessments/cognitive-skills-level-4/{assessment}/students/{student}/reset', [\App\Modules\Assessments\Controllers\PrincipalCognitiveAssessmentAccessController::class, 'resetStudent'])
        ->middleware(['role:Admin,Principal', 'permission:reset_student_cognitive_assessment'])
        ->name('principal.assessments.cognitive-skills-level-4.students.reset');

    Route::get('/principal/assessments/cognitive-skills-level-4/reports/{attempt}', [\App\Modules\Assessments\Controllers\PrincipalCognitiveAssessmentAccessController::class, 'report'])
        ->middleware(['role:Admin,Principal', 'permission:view_cognitive_profile_reports'])
        ->name('principal.assessments.cognitive-skills-level-4.reports.show');

    Route::get('/principal/assessments/cognitive-skills-level-4-reports/{attempt}', [\App\Modules\Assessments\Controllers\PrincipalCognitiveAssessmentReportController::class, 'show'])
        ->middleware(['role:Principal', 'permission:view_cognitive_assessment_reports'])
        ->name('principal.assessments.cognitive-skills-level-4-reports.show');

    Route::get('/admin/assessments/cognitive-skills-level-4-reports', [\App\Modules\Assessments\Controllers\AdminCognitiveAssessmentReportController::class, 'index'])
        ->middleware(['role:Admin', 'permission:view_cognitive_assessment_reports'])
        ->name('admin.assessments.cognitive-skills-level-4-reports.index');

    Route::get('/admin/assessments/cognitive-skills-level-4/reports/{attempt}', [\App\Modules\Assessments\Controllers\AdminCognitiveAssessmentReportController::class, 'show'])
        ->middleware(['role:Admin', 'permission:view_cognitive_profile_reports'])
        ->name('admin.assessments.cognitive-skills-level-4.reports.show');

    Route::get('/admin/assessments/cognitive-skills-level-4-reports/{attempt}', [\App\Modules\Assessments\Controllers\AdminCognitiveAssessmentReportController::class, 'show'])
        ->middleware(['role:Admin', 'permission:view_cognitive_assessment_reports'])
        ->name('admin.assessments.cognitive-skills-level-4-reports.show');

    Route::prefix('/admin/assessments/cognitive-skills-level-4')
        ->name('admin.assessments.cognitive-skills-level-4.')
        ->middleware(['role:Admin'])
        ->group(function (): void {
            Route::get('/question-banks', [\App\Modules\Assessments\Controllers\AdminCognitiveQuestionBankController::class, 'index'])
                ->middleware(['permission:manage_cognitive_question_banks'])
                ->name('question-banks.index');

            Route::get('/question-banks/create', [\App\Modules\Assessments\Controllers\AdminCognitiveQuestionBankController::class, 'create'])
                ->middleware(['permission:manage_cognitive_question_banks'])
                ->name('question-banks.create');

            Route::post('/question-banks', [\App\Modules\Assessments\Controllers\AdminCognitiveQuestionBankController::class, 'store'])
                ->middleware(['permission:manage_cognitive_question_banks'])
                ->name('question-banks.store');

            Route::get('/question-banks/{bank}', [\App\Modules\Assessments\Controllers\AdminCognitiveQuestionBankController::class, 'show'])
                ->middleware(['permission:manage_cognitive_question_banks'])
                ->name('question-banks.show');

            Route::get('/question-banks/{bank}/edit', [\App\Modules\Assessments\Controllers\AdminCognitiveQuestionBankController::class, 'edit'])
                ->middleware(['permission:manage_cognitive_question_banks'])
                ->name('question-banks.edit');

            Route::put('/question-banks/{bank}', [\App\Modules\Assessments\Controllers\AdminCognitiveQuestionBankController::class, 'update'])
                ->middleware(['permission:manage_cognitive_question_banks'])
                ->name('question-banks.update');

            Route::delete('/question-banks/{bank}', [\App\Modules\Assessments\Controllers\AdminCognitiveQuestionBankController::class, 'destroy'])
                ->middleware(['permission:manage_cognitive_question_banks'])
                ->name('question-banks.destroy');

            Route::get('/question-banks/{bank}/questions/create', [\App\Modules\Assessments\Controllers\AdminCognitiveBankQuestionController::class, 'create'])
                ->middleware(['permission:manage_cognitive_question_banks'])
                ->name('question-banks.questions.create');

            Route::post('/question-banks/{bank}/questions', [\App\Modules\Assessments\Controllers\AdminCognitiveBankQuestionController::class, 'store'])
                ->middleware(['permission:manage_cognitive_question_banks'])
                ->name('question-banks.questions.store');

            Route::get('/questions/{question}/edit', [\App\Modules\Assessments\Controllers\AdminCognitiveBankQuestionController::class, 'edit'])
                ->middleware(['permission:manage_cognitive_question_banks'])
                ->name('questions.edit');

            Route::put('/questions/{question}', [\App\Modules\Assessments\Controllers\AdminCognitiveBankQuestionController::class, 'update'])
                ->middleware(['permission:manage_cognitive_question_banks'])
                ->name('questions.update');

            Route::delete('/questions/{question}', [\App\Modules\Assessments\Controllers\AdminCognitiveBankQuestionController::class, 'destroy'])
                ->middleware(['permission:manage_cognitive_question_banks'])
                ->name('questions.destroy');

            Route::get('/sections/{section}/questions', [\App\Modules\Assessments\Controllers\AdminCognitiveAssessmentSectionQuestionController::class, 'edit'])
                ->middleware(['permission:manage_cognitive_assessment_setup'])
                ->name('sections.questions.edit');

            Route::put('/sections/{section}/questions', [\App\Modules\Assessments\Controllers\AdminCognitiveAssessmentSectionQuestionController::class, 'update'])
                ->middleware(['permission:manage_cognitive_assessment_setup'])
                ->name('sections.questions.update');

            Route::delete('/sections/{section}/questions/{bankQuestion}', [\App\Modules\Assessments\Controllers\AdminCognitiveAssessmentSectionQuestionController::class, 'remove'])
                ->middleware(['permission:manage_cognitive_assessment_setup'])
                ->name('sections.questions.remove');
        });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
