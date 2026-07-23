<?php

declare(strict_types=1);

use App\Http\Controllers\Academic\AcademicResourceController;
use App\Http\Controllers\Academic\SubjectController;
use App\Http\Controllers\Academic\TeachingAssignmentController;
use App\Http\Controllers\Academic\ScheduleController;
use App\Http\Controllers\Academic\ClassroomController;
use App\Http\Controllers\Academic\HomeroomAssignmentController;
use App\Http\Controllers\Academic\StudentPlacementController;
use App\Http\Controllers\Employee\EmployeeController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\PasswordUpdateController;
use App\Http\Controllers\Foundation\AcademicYearController;
use App\Http\Controllers\Foundation\DashboardController;
use App\Http\Controllers\Foundation\SchoolProfileController;
use App\Http\Controllers\Foundation\SemesterController;
use App\Http\Controllers\Foundation\SettingController;
use App\Http\Controllers\Foundation\UserManagementController;
use App\Http\Controllers\Attendance\EmployeeAttendanceController;
use App\Http\Controllers\Attendance\EmployeeLeaveController;
use App\Http\Controllers\Attendance\WorkScheduleController;
use App\Http\Controllers\Attendance\AttendanceReportController;
use App\Http\Controllers\Attendance\StudentAttendanceAttachmentController;
use App\Http\Controllers\Attendance\StudentAttendanceController;
use App\Http\Controllers\Attendance\StudentAttendanceCorrectionController;
use App\Http\Controllers\Attendance\StudentAttendanceReportController;
use App\Http\Controllers\Attendance\TeachingJournalController;
use App\Http\Controllers\StudentAffairs\EnrollmentController;
use App\Http\Controllers\StudentAffairs\GuardianController;
use App\Http\Controllers\StudentAffairs\StudentController;
use App\Http\Controllers\Btaq\BtaqGroupController;
use App\Http\Controllers\Btaq\BtaqJournalController;
use App\Http\Controllers\Btaq\BtaqLevelController;
use App\Http\Controllers\Btaq\BtaqMaterialController;
use App\Http\Controllers\Btaq\BtaqProgramController;
use App\Http\Controllers\Btaq\BtaqScheduleController;
use App\Http\Controllers\Btaq\BtaqSessionController;
use App\Http\Controllers\Btaq\BtaqVerificationController;
use App\Http\Controllers\Btaq\BtaqReportController;
use App\Http\Controllers\Assessment\AssessmentController;
use App\Http\Controllers\Assessment\Module10Controller;
use App\Http\Controllers\ReportCard\ReportCardController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Payroll\PayrollDashboardController;
use App\Http\Controllers\Payroll\PayrollComponentController;
use App\Http\Controllers\Payroll\EmployeeSalaryProfileController;
use App\Http\Controllers\Payroll\PayrollPeriodController;
use App\Http\Controllers\Payroll\PayrollRunController;
use App\Http\Controllers\Payroll\PayrollAdjustmentController;
use App\Http\Controllers\Payroll\PayrollApprovalController;
use App\Http\Controllers\Payroll\PayrollPaymentController;
use App\Http\Controllers\Payroll\PayslipController;
use App\Http\Controllers\Payroll\EmployeePayslipController;
use App\Http\Controllers\Payroll\PayrollReportController;

use App\Http\Controllers\Inventory\InventoryCrudController;
use App\Http\Controllers\Inventory\InventoryDashboardController;
use App\Http\Controllers\Inventory\InventoryReportController;
use App\Http\Controllers\Inventory\InventoryStockOpnameController;
use App\Http\Controllers\Inventory\InventoryTransactionController;


Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.store');


});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->middleware('permission:dashboard.view')->name('dashboard');
    Route::get('/password/change', [PasswordUpdateController::class, 'edit'])->name('password.change');
    Route::put('/password/change', [PasswordUpdateController::class, 'update'])->name('password.change.update');


    Route::get('/employee-attendances/mine', [EmployeeAttendanceController::class, 'mine'])->middleware('permission:employee-attendances.view-own')->name('employee-attendances.mine');
    Route::post('/employee-attendances/check-in', [EmployeeAttendanceController::class, 'checkIn'])->middleware('permission:employee-attendances.check-in')->name('employee-attendances.check-in');
    Route::post('/employee-attendances/check-out', [EmployeeAttendanceController::class, 'checkOut'])->middleware('permission:employee-attendances.check-out')->name('employee-attendances.check-out');
    Route::get('/employee-attendances/export', [EmployeeAttendanceController::class, 'export'])->middleware('permission:employee-attendances.export')->name('employee-attendances.export');
    Route::get('/employee-attendances', [EmployeeAttendanceController::class, 'index'])->middleware('permission:employee-attendances.view')->name('employee-attendances.index');
    Route::get('/employee-attendances/{employeeAttendance}', [EmployeeAttendanceController::class, 'show'])->middleware('permission:employee-attendances.view')->name('employee-attendances.show');
    Route::patch('/employee-attendances/{employeeAttendance}/verify', [EmployeeAttendanceController::class, 'verify'])->middleware('permission:employee-attendances.verify')->name('employee-attendances.verify');
    Route::patch('/employee-attendances/{employeeAttendance}/correct', [EmployeeAttendanceController::class, 'correct'])->middleware('permission:employee-attendances.correct')->name('employee-attendances.correct');

    Route::get('/employee-leaves/approvals', [EmployeeLeaveController::class, 'approvals'])->middleware('permission:employee-leaves.approve|employee-leaves.reject')->name('employee-leaves.approvals');
    Route::get('/employee-leaves/export', [EmployeeLeaveController::class, 'export'])->middleware('permission:employee-leaves.export')->name('employee-leaves.export');
    Route::get('/employee-leaves', [EmployeeLeaveController::class, 'index'])->middleware('permission:employee-leaves.view-own')->name('employee-leaves.index');
    Route::get('/employee-leaves/create', [EmployeeLeaveController::class, 'create'])->middleware('permission:employee-leaves.create')->name('employee-leaves.create');
    Route::post('/employee-leaves', [EmployeeLeaveController::class, 'store'])->middleware('permission:employee-leaves.create')->name('employee-leaves.store');
    Route::get('/employee-leaves/{employeeLeave}', [EmployeeLeaveController::class, 'show'])->middleware('permission:employee-leaves.view-own|employee-leaves.view')->name('employee-leaves.show');
    Route::patch('/employee-leaves/{employeeLeave}/cancel', [EmployeeLeaveController::class, 'cancel'])->middleware('permission:employee-leaves.cancel')->name('employee-leaves.cancel');
    Route::patch('/employee-leaves/{employeeLeave}/approve', [EmployeeLeaveController::class, 'approve'])->middleware('permission:employee-leaves.approve')->name('employee-leaves.approve');
    Route::patch('/employee-leaves/{employeeLeave}/reject', [EmployeeLeaveController::class, 'reject'])->middleware('permission:employee-leaves.reject')->name('employee-leaves.reject');
    Route::get('/employee-leaves/{employeeLeave}/download', [EmployeeLeaveController::class, 'download'])->middleware('permission:employee-leaves.view-own|employee-leaves.view')->name('employee-leaves.download');


    Route::get('/work-schedules', [WorkScheduleController::class, 'index'])->middleware('permission:work-schedules.view')->name('work-schedules.index');
    Route::get('/work-schedules/create', [WorkScheduleController::class, 'create'])->middleware('permission:work-schedules.manage')->name('work-schedules.create');
    Route::post('/work-schedules', [WorkScheduleController::class, 'store'])->middleware('permission:work-schedules.manage')->name('work-schedules.store');
    Route::get('/work-schedules/{workSchedule}', [WorkScheduleController::class, 'show'])->middleware('permission:work-schedules.view')->name('work-schedules.show');
    Route::get('/work-schedules/{workSchedule}/edit', [WorkScheduleController::class, 'edit'])->middleware('permission:work-schedules.manage')->name('work-schedules.edit');
    Route::put('/work-schedules/{workSchedule}', [WorkScheduleController::class, 'update'])->middleware('permission:work-schedules.manage')->name('work-schedules.update');
    Route::patch('/work-schedules/{workSchedule}/toggle', [WorkScheduleController::class, 'toggle'])->middleware('permission:work-schedules.manage')->name('work-schedules.toggle');
    Route::get('/attendance-reports/export', [AttendanceReportController::class, 'export'])->middleware('permission:employee-attendances.export')->name('attendance-reports.export');
    Route::get('/attendance-reports', [AttendanceReportController::class, 'index'])->middleware('permission:employee-attendances.view')->name('attendance-reports.index');

    Route::get('/employees/mine', [EmployeeController::class, 'mine'])->middleware('permission:employees.view-own')->name('employees.mine');
    Route::get('/employees/export', [EmployeeController::class, 'export'])->middleware('permission:employees.export')->name('employees.export');
    Route::get('/employees/import', [EmployeeController::class, 'importForm'])->middleware('permission:employees.create')->name('employees.import.form');
    Route::post('/employees/import', [EmployeeController::class, 'import'])->middleware('permission:employees.create')->name('employees.import');
    Route::get('/employees', [EmployeeController::class, 'index'])->middleware('permission:employees.view')->name('employees.index');
    Route::get('/employees/create', [EmployeeController::class, 'create'])->middleware('permission:employees.create')->name('employees.create');
    Route::post('/employees', [EmployeeController::class, 'store'])->middleware('permission:employees.create')->name('employees.store');
    Route::get('/employees/{employee}', [EmployeeController::class, 'show'])->middleware('permission:employees.view|employees.view-own')->name('employees.show');
    Route::get('/employees/{employee}/edit', [EmployeeController::class, 'edit'])->middleware('permission:employees.update')->name('employees.edit');
    Route::put('/employees/{employee}', [EmployeeController::class, 'update'])->middleware('permission:employees.update')->name('employees.update');
    Route::patch('/employees/{employee}/activate', [EmployeeController::class, 'activate'])->middleware('permission:employees.activate')->name('employees.activate');
    Route::patch('/employees/{employee}/deactivate', [EmployeeController::class, 'deactivate'])->middleware('permission:employees.activate')->name('employees.deactivate');
    Route::post('/employees/{employee}/documents', [EmployeeController::class, 'storeDocument'])->middleware('permission:employees.manage-documents')->name('employees.documents.store');
    Route::post('/employees/{employee}/link-account', [EmployeeController::class, 'linkAccount'])->middleware('permission:employees.link-account')->name('employees.link-account');
    Route::post('/employees/{employee}/create-account', [EmployeeController::class, 'createAccount'])->middleware('permission:employees.link-account')->name('employees.create-account');
    Route::put('/employee-documents/{document}', [EmployeeController::class, 'updateDocument'])->middleware('permission:employees.manage-documents')->name('employee-documents.update');
    Route::get('/employee-documents/{document}/download', [EmployeeController::class, 'downloadDocument'])->middleware('permission:employees.manage-documents|employees.view-own')->name('employee-documents.download');
    Route::delete('/employee-documents/{document}', [EmployeeController::class, 'destroyDocument'])->middleware('permission:employees.manage-documents')->name('employee-documents.destroy');

    Route::get('/student-attendances/mine', [StudentAttendanceController::class, 'own'])->middleware('permission:student-attendances.view-own-class|student-attendances.view')->name('student-attendances.mine');
    Route::get('/student-attendances/missing-classes', [StudentAttendanceController::class, 'missing'])->middleware('permission:student-attendances.view-missing-classes')->name('student-attendances.missing');
    Route::get('/student-attendances/reports/export', [StudentAttendanceReportController::class, 'export'])->middleware('permission:student-attendances.export')->name('student-attendances.reports.export');
    Route::get('/student-attendances/reports/print', [StudentAttendanceReportController::class, 'print'])->middleware('permission:student-attendances.print')->name('student-attendances.reports.print');
    Route::get('/student-attendances/reports', [StudentAttendanceReportController::class, 'index'])->middleware('permission:student-attendances.report')->name('student-attendances.reports.index');
    Route::get('/student-attendances/create', [StudentAttendanceController::class, 'create'])->middleware('permission:student-attendances.create')->name('student-attendances.create');
    Route::post('/student-attendances', [StudentAttendanceController::class, 'store'])->middleware('permission:student-attendances.create')->name('student-attendances.store');
    Route::get('/student-attendances/{studentAttendance}/edit', [StudentAttendanceController::class, 'edit'])->middleware('permission:student-attendances.update-draft')->name('student-attendances.edit');
    Route::patch('/student-attendances/{studentAttendance}/finalize', [StudentAttendanceController::class, 'finalize'])->middleware('permission:student-attendances.finalize')->name('student-attendances.finalize');
    Route::post('/student-attendance-records/{attendance}/corrections', [StudentAttendanceCorrectionController::class, 'store'])->middleware('permission:student-attendances.correct')->name('student-attendances.corrections.store');
    Route::get('/student-attendance-records/{attendance}/attachment', StudentAttendanceAttachmentController::class)->middleware('permission:student-attendances.view-attachment|student-attendances.view-own-class')->name('student-attendances.attachments.show');
    Route::get('/student-attendances/{studentAttendance}', [StudentAttendanceController::class, 'show'])->middleware('permission:student-attendances.view|student-attendances.view-own-class')->name('student-attendances.show');
    Route::get('/student-attendances', [StudentAttendanceController::class, 'index'])->middleware('permission:student-attendances.view|student-attendances.view-own-class')->name('student-attendances.index');

    Route::get('/teaching-journals/export', [TeachingJournalController::class, 'export'])->middleware('permission:teaching-journals.export')->name('teaching-journals.export');
    Route::get('/teaching-journals', [TeachingJournalController::class, 'index'])->middleware('permission:teaching-journals.view-own|teaching-journals.view')->name('teaching-journals.index');
    Route::get('/teaching-journals/create', [TeachingJournalController::class, 'create'])->middleware('permission:teaching-journals.create')->name('teaching-journals.create');
    Route::post('/teaching-journals', [TeachingJournalController::class, 'store'])->middleware('permission:teaching-journals.create')->name('teaching-journals.store');
    Route::get('/teaching-journals/{teachingJournal}/edit', [TeachingJournalController::class, 'edit'])->middleware('permission:teaching-journals.update-own')->name('teaching-journals.edit');
    Route::put('/teaching-journals/{teachingJournal}', [TeachingJournalController::class, 'update'])->middleware('permission:teaching-journals.update-own')->name('teaching-journals.update');
    Route::get('/teaching-journals/{teachingJournal}/print', [TeachingJournalController::class, 'print'])->middleware('permission:teaching-journals.print-own|teaching-journals.print')->name('teaching-journals.print');
    Route::get('/teaching-journals/{teachingJournal}', [TeachingJournalController::class, 'show'])->middleware('permission:teaching-journals.view-own|teaching-journals.view')->name('teaching-journals.show');
    Route::patch('/teaching-journals/{teachingJournal}/submit', [TeachingJournalController::class, 'submit'])->middleware('permission:teaching-journals.submit')->name('teaching-journals.submit');
    Route::patch('/teaching-journals/{teachingJournal}/verify', [TeachingJournalController::class, 'verify'])->middleware('permission:teaching-journals.verify')->name('teaching-journals.verify');
    Route::patch('/teaching-journals/{teachingJournal}/reject', [TeachingJournalController::class, 'reject'])->middleware('permission:teaching-journals.reject')->name('teaching-journals.reject');

    Route::get('/school-profile', [SchoolProfileController::class, 'edit'])
        ->middleware('permission:school-profile.view')
        ->name('school-profile.edit');
    Route::put('/school-profile', [SchoolProfileController::class, 'update'])
        ->middleware('permission:school-profile.update')
        ->name('school-profile.update');

    Route::get('/academic-years', [AcademicYearController::class, 'index'])->middleware('permission:academic-years.view')->name('academic-years.index');
    Route::get('/academic-years/create', [AcademicYearController::class, 'create'])->middleware('permission:academic-years.create')->name('academic-years.create');
    Route::post('/academic-years', [AcademicYearController::class, 'store'])->middleware('permission:academic-years.create')->name('academic-years.store');
    Route::get('/academic-years/{academic_year}', [AcademicYearController::class, 'show'])->middleware('permission:academic-years.view')->name('academic-years.show');
    Route::get('/academic-years/{academic_year}/edit', [AcademicYearController::class, 'edit'])->middleware('permission:academic-years.update')->name('academic-years.edit');
    Route::put('/academic-years/{academic_year}', [AcademicYearController::class, 'update'])->middleware('permission:academic-years.update')->name('academic-years.update');
    Route::patch('/academic-years/{academic_year}/activate', [AcademicYearController::class, 'activate'])->middleware('permission:academic-years.activate')->name('academic-years.activate');
    Route::patch('/academic-years/{academic_year}/deactivate', [AcademicYearController::class, 'deactivate'])->middleware('permission:academic-years.activate')->name('academic-years.deactivate');

    Route::get('/semesters', [SemesterController::class, 'index'])->middleware('permission:semesters.view')->name('semesters.index');
    Route::get('/semesters/create', [SemesterController::class, 'create'])->middleware('permission:semesters.create')->name('semesters.create');
    Route::post('/semesters', [SemesterController::class, 'store'])->middleware('permission:semesters.create')->name('semesters.store');
    Route::get('/semesters/{semester}', [SemesterController::class, 'show'])->middleware('permission:semesters.view')->name('semesters.show');
    Route::get('/semesters/{semester}/edit', [SemesterController::class, 'edit'])->middleware('permission:semesters.update')->name('semesters.edit');
    Route::put('/semesters/{semester}', [SemesterController::class, 'update'])->middleware('permission:semesters.update')->name('semesters.update');
    Route::patch('/semesters/{semester}/activate', [SemesterController::class, 'activate'])->middleware('permission:semesters.activate')->name('semesters.activate');
    Route::patch('/semesters/{semester}/deactivate', [SemesterController::class, 'deactivate'])->middleware('permission:semesters.activate')->name('semesters.deactivate');

    Route::get('/settings', [SettingController::class, 'index'])
        ->middleware('permission:settings.view')
        ->name('settings.index');
    Route::put('/settings/{setting}', [SettingController::class, 'update'])
        ->middleware('permission:settings.update')
        ->name('settings.update');

    Route::get('/users', [UserManagementController::class, 'index'])->middleware('permission:users.view')->name('users.index');
    Route::get('/users/create', [UserManagementController::class, 'create'])->middleware('permission:users.create')->name('users.create');
    Route::post('/users', [UserManagementController::class, 'store'])->middleware('permission:users.create')->name('users.store');
    Route::get('/users/{user}', [UserManagementController::class, 'show'])->middleware('permission:users.view')->name('users.show');
    Route::get('/users/{user}/edit', [UserManagementController::class, 'edit'])->middleware('permission:users.update')->name('users.edit');
    Route::put('/users/{user}', [UserManagementController::class, 'update'])->middleware('permission:users.update')->name('users.update');
    Route::patch('/users/{user}/reset-password', [UserManagementController::class, 'resetPassword'])->middleware('permission:users.reset-password')->name('users.reset-password');

    Route::get('/students/export', [StudentController::class, 'export'])->middleware('permission:students.export')->name('students.export');
    Route::get('/students/import', [StudentController::class, 'importForm'])->middleware('permission:students.create')->name('students.import.form');
    Route::post('/students/import', [StudentController::class, 'import'])->middleware('permission:students.create')->name('students.import');
    Route::get('/students', [StudentController::class, 'index'])->middleware('permission:students.view')->name('students.index');
    Route::get('/students/create', [StudentController::class, 'create'])->middleware('permission:students.create')->name('students.create');
    Route::post('/students', [StudentController::class, 'store'])->middleware('permission:students.create')->name('students.store');
    Route::get('/students/{student}', [StudentController::class, 'show'])->middleware('permission:students.view')->name('students.show');
    Route::get('/students/{student}/edit', [StudentController::class, 'edit'])->middleware('permission:students.update')->name('students.edit');
    Route::put('/students/{student}', [StudentController::class, 'update'])->middleware('permission:students.update')->name('students.update');
    Route::delete('/students/{student}', [StudentController::class, 'destroy'])->middleware('permission:students.delete')->name('students.destroy');
    Route::post('/students/{student}/guardians', [StudentController::class, 'attachGuardian'])->middleware('permission:guardians.link-student')->name('students.guardians.store');
    Route::put('/students/{student}/guardians/{guardian}', [StudentController::class, 'updateGuardian'])->middleware('permission:guardians.link-student')->name('students.guardians.update');
    Route::delete('/students/{student}/guardians/{guardian}', [StudentController::class, 'detachGuardian'])->middleware('permission:guardians.unlink-student')->name('students.guardians.destroy');
    Route::post('/students/{student}/status', [StudentController::class, 'changeStatus'])->middleware('permission:students.change-status')->name('students.status.store');
    Route::post('/students/{student}/documents', [StudentController::class, 'uploadDocument'])->middleware('permission:students.manage-documents')->name('students.documents.store');
    Route::get('/student-documents/{document}/download', [StudentController::class, 'downloadDocument'])->middleware('permission:students.manage-documents')->name('student-documents.download');
    Route::delete('/student-documents/{document}', [StudentController::class, 'deleteDocument'])->middleware('permission:students.manage-documents')->name('student-documents.destroy');
    Route::get('/guardians/export', [GuardianController::class, 'export'])->middleware('permission:guardians.export')->name('guardians.export');
    Route::get('/guardians', [GuardianController::class, 'index'])->middleware('permission:guardians.view')->name('guardians.index');
    Route::get('/guardians/create', [GuardianController::class, 'create'])->middleware('permission:guardians.create')->name('guardians.create');
    Route::post('/guardians', [GuardianController::class, 'store'])->middleware('permission:guardians.create')->name('guardians.store');
    Route::get('/guardians/{guardian}', [GuardianController::class, 'show'])->middleware('permission:guardians.view')->name('guardians.show');
    Route::get('/guardians/{guardian}/edit', [GuardianController::class, 'edit'])->middleware('permission:guardians.update')->name('guardians.edit');
    Route::put('/guardians/{guardian}', [GuardianController::class, 'update'])->middleware('permission:guardians.update')->name('guardians.update');
    Route::delete('/guardians/{guardian}', [GuardianController::class, 'destroy'])->middleware('permission:guardians.delete')->name('guardians.destroy');
    Route::get('/student-enrollments', [EnrollmentController::class, 'index'])->middleware('permission:student-enrollments.view')->name('student-enrollments.index');
    Route::get('/student-enrollments/create', [EnrollmentController::class, 'index'])->middleware('permission:student-enrollments.create')->name('student-enrollments.create');
    Route::post('/student-enrollments', [EnrollmentController::class, 'store'])->middleware('permission:student-enrollments.create')->name('student-enrollments.store');
    Route::delete('/student-enrollments/{enrollment}', [EnrollmentController::class, 'destroy'])->middleware('permission:student-enrollments.delete')->name('student-enrollments.destroy');

    Route::prefix('academic')->group(function (): void {
        Route::get('/grade-levels', [AcademicResourceController::class, 'gradeLevels'])->middleware('permission:grade-levels.view')->name('grade-levels.index');
        Route::get('/grade-levels/create', [AcademicResourceController::class, 'createGradeLevel'])->middleware('permission:grade-levels.create')->name('grade-levels.create');
        Route::post('/grade-levels', [AcademicResourceController::class, 'storeGradeLevel'])->middleware('permission:grade-levels.create')->name('grade-levels.store');
        Route::get('/grade-levels/{gradeLevel}/edit', [AcademicResourceController::class, 'editGradeLevel'])->middleware('permission:grade-levels.update')->name('grade-levels.edit');
        Route::put('/grade-levels/{gradeLevel}', [AcademicResourceController::class, 'updateGradeLevel'])->middleware('permission:grade-levels.update')->name('grade-levels.update');
        Route::delete('/grade-levels/{gradeLevel}', [AcademicResourceController::class, 'destroyGradeLevel'])->middleware('permission:grade-levels.delete')->name('grade-levels.destroy');
        Route::get('/classrooms/export', [ClassroomController::class, 'export'])->middleware('permission:classrooms.export')->name('classrooms.export');
        Route::get('/classrooms', [ClassroomController::class, 'index'])->middleware('permission:classrooms.view')->name('classrooms.index');
        Route::get('/classrooms/create', [ClassroomController::class, 'create'])->middleware('permission:classrooms.create')->name('classrooms.create');
        Route::post('/classrooms', [ClassroomController::class, 'store'])->middleware('permission:classrooms.create')->name('classrooms.store');
        Route::get('/classrooms/{classroom}', [ClassroomController::class, 'show'])->middleware('permission:classrooms.view')->name('classrooms.show');
        Route::get('/classrooms/{classroom}/edit', [ClassroomController::class, 'edit'])->middleware('permission:classrooms.update')->name('classrooms.edit');
        Route::put('/classrooms/{classroom}', [ClassroomController::class, 'update'])->middleware('permission:classrooms.update')->name('classrooms.update');
        Route::patch('/classrooms/{classroom}/toggle', [ClassroomController::class, 'toggle'])->middleware('permission:classrooms.activate')->name('classrooms.toggle');
        Route::delete('/classrooms/{classroom}', [ClassroomController::class, 'destroy'])->middleware('permission:classrooms.update')->name('classrooms.destroy');
        Route::get('/classrooms/{classroom}/students/export', [ClassroomController::class, 'exportStudents'])->middleware('permission:student-enrollments.export')->name('classrooms.students.export');
        Route::get('/classrooms/{classroom}/placements/create', [StudentPlacementController::class, 'create'])->middleware('permission:student-enrollments.create')->name('classrooms.placements.create');
        Route::post('/classrooms/{classroom}/placements', [StudentPlacementController::class, 'store'])->middleware('permission:student-enrollments.create')->name('classrooms.placements.store');
        Route::get('/student-enrollments/{enrollment}/transfer', [StudentPlacementController::class, 'editTransfer'])->middleware('permission:student-enrollments.transfer')->name('student-enrollments.transfer.edit');
        Route::post('/student-enrollments/{enrollment}/transfer', [StudentPlacementController::class, 'transfer'])->middleware('permission:student-enrollments.transfer')->name('student-enrollments.transfer');
        Route::get('/classrooms/{classroom}/homeroom', [HomeroomAssignmentController::class, 'edit'])->middleware('permission:homeroom-assignments.manage')->name('classrooms.homeroom.edit');
        Route::put('/classrooms/{classroom}/homeroom', [HomeroomAssignmentController::class, 'update'])->middleware('permission:homeroom-assignments.manage')->name('classrooms.homeroom.update');
        Route::delete('/classrooms/{classroom}/homeroom', [HomeroomAssignmentController::class, 'destroy'])->middleware('permission:homeroom-assignments.manage')->name('classrooms.homeroom.destroy');
        Route::get('/subjects/export', [SubjectController::class, 'export'])->middleware('permission:subjects.export')->name('subjects.export');
        Route::get('/subjects', [SubjectController::class, 'index'])->middleware('permission:subjects.view')->name('subjects.index');
        Route::get('/subjects/create', [SubjectController::class, 'create'])->middleware('permission:subjects.create')->name('subjects.create');
        Route::post('/subjects', [SubjectController::class, 'store'])->middleware('permission:subjects.create')->name('subjects.store');
        Route::get('/subjects/{subject}', [SubjectController::class, 'show'])->middleware('permission:subjects.view')->name('subjects.show');
        Route::get('/subjects/{subject}/edit', [SubjectController::class, 'edit'])->middleware('permission:subjects.update')->name('subjects.edit');
        Route::put('/subjects/{subject}', [SubjectController::class, 'update'])->middleware('permission:subjects.update')->name('subjects.update');
        Route::patch('/subjects/{subject}/toggle', [SubjectController::class, 'toggle'])->middleware('permission:subjects.activate')->name('subjects.toggle');
        Route::delete('/subjects/{subject}', [SubjectController::class, 'destroy'])->middleware('permission:subjects.activate')->name('subjects.destroy');
        Route::get('/teaching-assignments/export', [TeachingAssignmentController::class, 'export'])->middleware('permission:teaching-assignments.export')->name('teaching-assignments.export');
        Route::get('/teaching-assignments', [TeachingAssignmentController::class, 'index'])->middleware('permission:teaching-assignments.view|teaching-assignments.view-own')->name('teaching-assignments.index');
        Route::get('/teaching-assignments/create', [TeachingAssignmentController::class, 'create'])->middleware('permission:teaching-assignments.create')->name('teaching-assignments.create');
        Route::post('/teaching-assignments', [TeachingAssignmentController::class, 'store'])->middleware('permission:teaching-assignments.create')->name('teaching-assignments.store');
        Route::get('/teaching-assignments/{teachingAssignment}', [TeachingAssignmentController::class, 'show'])->middleware('permission:teaching-assignments.view|teaching-assignments.view-own')->name('teaching-assignments.show');
        Route::get('/teaching-assignments/{teachingAssignment}/edit', [TeachingAssignmentController::class, 'edit'])->middleware('permission:teaching-assignments.update')->name('teaching-assignments.edit');
        Route::put('/teaching-assignments/{teachingAssignment}', [TeachingAssignmentController::class, 'update'])->middleware('permission:teaching-assignments.update')->name('teaching-assignments.update');
        Route::patch('/teaching-assignments/{teachingAssignment}/toggle', [TeachingAssignmentController::class, 'toggle'])->middleware('permission:teaching-assignments.activate')->name('teaching-assignments.toggle');
        Route::delete('/teaching-assignments/{teachingAssignment}', [TeachingAssignmentController::class, 'destroy'])->middleware('permission:teaching-assignments.activate')->name('teaching-assignments.destroy');
        Route::get('/schedules/export', [ScheduleController::class, 'export'])->middleware('permission:schedules.export')->name('schedules.export');
        Route::get('/schedules/print', [ScheduleController::class, 'print'])->middleware('permission:schedules.print')->name('schedules.print');
        Route::get('/schedules', [ScheduleController::class, 'index'])->middleware('permission:schedules.view|schedules.view-own')->name('schedules.index');
        Route::get('/schedules/create', [ScheduleController::class, 'create'])->middleware('permission:schedules.create')->name('schedules.create');
        Route::post('/schedules', [ScheduleController::class, 'store'])->middleware('permission:schedules.create')->name('schedules.store');
        Route::get('/schedules/{schedule}', [ScheduleController::class, 'show'])->middleware('permission:schedules.view|schedules.view-own')->name('schedules.show');
        Route::get('/schedules/{schedule}/edit', [ScheduleController::class, 'edit'])->middleware('permission:schedules.update')->name('schedules.edit');
        Route::put('/schedules/{schedule}', [ScheduleController::class, 'update'])->middleware('permission:schedules.update')->name('schedules.update');
        Route::patch('/schedules/{schedule}/toggle', [ScheduleController::class, 'toggle'])->middleware('permission:schedules.activate')->name('schedules.toggle');
        Route::delete('/schedules/{schedule}', [ScheduleController::class, 'destroy'])->middleware('permission:schedules.activate')->name('schedules.destroy');
    });



    Route::get('/btaq', [BtaqReportController::class, 'index'])->middleware('permission:btaq-reports.view|btaq-sessions.view-own')->name('btaq.index');
    Route::get('/btaq/saya', [BtaqSessionController::class, 'mine'])->middleware('permission:btaq-sessions.view-own')->name('btaq.mine');
    Route::resource('btaq-programs', BtaqProgramController::class)->except(['destroy'])->middleware(['permission:btaq-programs.view|btaq-programs.manage']);
    Route::patch('/btaq-programs/{btaqProgram}/toggle', [BtaqProgramController::class, 'toggle'])->middleware('permission:btaq-programs.manage')->name('btaq-programs.toggle');
    Route::resource('btaq-schedules', BtaqScheduleController::class)->except(['destroy'])->middleware(['permission:btaq-schedules.view|btaq-schedules.manage']);
    Route::patch('/btaq-schedules/{btaqSchedule}/toggle', [BtaqScheduleController::class, 'toggle'])->middleware('permission:btaq-schedules.manage')->name('btaq-schedules.toggle');
    Route::resource('btaq-sessions', BtaqSessionController::class)->except(['destroy'])->middleware(['permission:btaq-sessions.view|btaq-sessions.view-own|btaq-sessions.create']);
    Route::patch('/btaq-sessions/{btaqSession}/submit', [BtaqSessionController::class, 'submit'])->middleware('permission:btaq-sessions.submit')->name('btaq-sessions.submit');
    Route::get('/btaq-sessions/{btaqSession}/print', [BtaqSessionController::class, 'print'])->middleware('permission:btaq-sessions.print|btaq-sessions.print-own')->name('btaq-sessions.print');
    Route::get('/btaq/verifikasi', [BtaqVerificationController::class, 'index'])->middleware('permission:btaq-sessions.verify|btaq-sessions.reject')->name('btaq-verifications.index');
    Route::patch('/btaq/verifikasi/{btaqSession}/verify', [BtaqVerificationController::class, 'verify'])->middleware('permission:btaq-sessions.verify')->name('btaq-verifications.verify');
    Route::patch('/btaq/verifikasi/{btaqSession}/reject', [BtaqVerificationController::class, 'reject'])->middleware('permission:btaq-sessions.reject')->name('btaq-verifications.reject');
    Route::get('/btaq/laporan', [BtaqReportController::class, 'index'])->middleware('permission:btaq-reports.view')->name('btaq-reports.index');
    Route::get('/btaq/laporan/export', [BtaqReportController::class, 'export'])->middleware('permission:btaq-reports.export')->name('btaq-reports.export');
    Route::get('/btaq/laporan/cetak', [BtaqReportController::class, 'print'])->middleware('permission:btaq-reports.print')->name('btaq-reports.print');
    Route::get('/btaq/sesi-belum-diisi', [BtaqReportController::class, 'missingSessions'])->middleware('permission:btaq-reports.view')->name('btaq-reports.missing-sessions');
    Route::get('/btaq/siswa-belum-berkelompok', [BtaqReportController::class, 'unassignedStudents'])->middleware('permission:btaq-groups.assign-students|btaq-reports.view')->name('btaq-reports.unassigned-students');

    Route::get('/btaq/dashboard', [BtaqJournalController::class, 'dashboard'])->middleware('permission:btaq-reports.view')->name('btaq.dashboard');
    Route::get('/btaq-levels', [BtaqLevelController::class, 'index'])->middleware('permission:btaq-levels.view')->name('btaq-levels.index');
    Route::get('/btaq-levels/create', [BtaqLevelController::class, 'create'])->middleware('permission:btaq-levels.manage')->name('btaq-levels.create');
    Route::post('/btaq-levels', [BtaqLevelController::class, 'store'])->middleware('permission:btaq-levels.manage')->name('btaq-levels.store');
    Route::get('/btaq-levels/{btaqLevel}', [BtaqLevelController::class, 'show'])->middleware('permission:btaq-levels.view')->name('btaq-levels.show');
    Route::get('/btaq-levels/{btaqLevel}/edit', [BtaqLevelController::class, 'edit'])->middleware('permission:btaq-levels.manage')->name('btaq-levels.edit');
    Route::put('/btaq-levels/{btaqLevel}', [BtaqLevelController::class, 'update'])->middleware('permission:btaq-levels.manage')->name('btaq-levels.update');
    Route::patch('/btaq-levels/{btaqLevel}/toggle', [BtaqLevelController::class, 'toggle'])->middleware('permission:btaq-levels.manage')->name('btaq-levels.toggle');

    Route::get('/btaq-materials', [BtaqMaterialController::class, 'index'])->middleware('permission:btaq-materials.view')->name('btaq-materials.index');
    Route::get('/btaq-materials/create', [BtaqMaterialController::class, 'create'])->middleware('permission:btaq-materials.manage')->name('btaq-materials.create');
    Route::post('/btaq-materials', [BtaqMaterialController::class, 'store'])->middleware('permission:btaq-materials.manage')->name('btaq-materials.store');
    Route::get('/btaq-materials/{btaqMaterial}', [BtaqMaterialController::class, 'show'])->middleware('permission:btaq-materials.view')->name('btaq-materials.show');
    Route::get('/btaq-materials/{btaqMaterial}/edit', [BtaqMaterialController::class, 'edit'])->middleware('permission:btaq-materials.manage')->name('btaq-materials.edit');
    Route::put('/btaq-materials/{btaqMaterial}', [BtaqMaterialController::class, 'update'])->middleware('permission:btaq-materials.manage')->name('btaq-materials.update');

    Route::get('/btaq-groups', [BtaqGroupController::class, 'index'])->middleware('permission:btaq-groups.view')->name('btaq-groups.index');
    Route::get('/btaq-groups/create', [BtaqGroupController::class, 'create'])->middleware('permission:btaq-groups.manage')->name('btaq-groups.create');
    Route::post('/btaq-groups', [BtaqGroupController::class, 'store'])->middleware('permission:btaq-groups.manage')->name('btaq-groups.store');
    Route::get('/btaq-groups/{btaqGroup}', [BtaqGroupController::class, 'show'])->middleware('permission:btaq-groups.view')->name('btaq-groups.show');
    Route::get('/btaq-groups/{btaqGroup}/edit', [BtaqGroupController::class, 'edit'])->middleware('permission:btaq-groups.manage')->name('btaq-groups.edit');
    Route::put('/btaq-groups/{btaqGroup}', [BtaqGroupController::class, 'update'])->middleware('permission:btaq-groups.manage')->name('btaq-groups.update');
    Route::post('/btaq-groups/{btaqGroup}/members', [BtaqGroupController::class, 'addMembers'])->middleware('permission:btaq-groups.manage')->name('btaq-groups.members.store');
    Route::patch('/btaq-members/{member}/complete', [BtaqGroupController::class, 'complete'])->middleware('permission:btaq-groups.manage')->name('btaq-members.complete');
    Route::patch('/btaq-members/{member}/transfer', [BtaqGroupController::class, 'transfer'])->middleware('permission:btaq-groups.manage')->name('btaq-members.transfer');
    Route::get('/btaq/progress', [BtaqJournalController::class, 'progress'])->middleware('permission:btaq-reports.view')->name('btaq.progress');
    Route::get('/btaq/recap', [BtaqJournalController::class, 'recap'])->middleware('permission:btaq-reports.view')->name('btaq.recap');
    Route::get('/btaq-journals', [BtaqJournalController::class, 'index'])->middleware('permission:btaq-journals.view-own')->name('btaq-journals.index');
    Route::get('/btaq-journals/create', [BtaqJournalController::class, 'create'])->middleware('permission:btaq-journals.create')->name('btaq-journals.create');
    Route::post('/btaq-journals', [BtaqJournalController::class, 'store'])->middleware('permission:btaq-journals.create')->name('btaq-journals.store');
    Route::get('/btaq-journals/{btaqJournal}', [BtaqJournalController::class, 'show'])->middleware('permission:btaq-journals.view-own')->name('btaq-journals.show');
    Route::get('/btaq-journals/{btaqJournal}/edit', [BtaqJournalController::class, 'edit'])->middleware('permission:btaq-journals.update')->name('btaq-journals.edit');
    Route::put('/btaq-journals/{btaqJournal}', [BtaqJournalController::class, 'update'])->middleware('permission:btaq-journals.update')->name('btaq-journals.update');
    Route::patch('/btaq-journals/{btaqJournal}/submit', [BtaqJournalController::class, 'submit'])->middleware('permission:btaq-journals.submit')->name('btaq-journals.submit');
    Route::patch('/btaq-journals/{btaqJournal}/verify', [BtaqJournalController::class, 'verify'])->middleware('permission:btaq-journals.verify')->name('btaq-journals.verify');
    Route::patch('/btaq-journals/{btaqJournal}/reject', [BtaqJournalController::class, 'reject'])->middleware('permission:btaq-journals.reject')->name('btaq-journals.reject');



    Route::prefix('penilaian-rapor')->name('assessments.')->group(function (): void {
        Route::get('/', [Module10Controller::class, 'index'])->middleware('permission:grades.view-own|grade-books.view-own-class|assessments.view-configuration|assessment-reports.view')->name('index');
        Route::get('/konfigurasi', [Module10Controller::class, 'configuration'])->middleware('permission:assessments.view-configuration')->name('configuration');
        Route::get('/komponen', [Module10Controller::class, 'components'])->middleware('permission:assessments.manage-components|assessments.view-configuration')->name('components');
        Route::get('/kkm', [Module10Controller::class, 'minimumCriteria'])->middleware('permission:assessments.manage-minimum-criteria')->name('minimum-criteria');
        Route::get('/periode', [Module10Controller::class, 'periods'])->middleware('permission:assessments.manage-periods')->name('periods');
        Route::get('/nilai-saya', [Module10Controller::class, 'myGrades'])->middleware('permission:grades.view-own')->name('my-grades');
        Route::get('/input-nilai', [Module10Controller::class, 'input'])->middleware('permission:grades.create|grades.update-own')->name('input');
        Route::get('/verifikasi-nilai', [Module10Controller::class, 'verification'])->middleware('permission:grades.verify|grades.reject')->name('grade-verification');
        Route::get('/leger', [Module10Controller::class, 'leger'])->middleware('permission:grade-books.view|grade-books.view-own-class')->name('leger');
        Route::get('/leger/export', [Module10Controller::class, 'exportLeger'])->middleware('permission:grade-books.export')->name('leger.export');
        Route::get('/leger/print', [Module10Controller::class, 'printLeger'])->middleware('permission:grade-books.print|grade-books.print')->name('leger.print');
        Route::get('/rapor-kelas-saya', [Module10Controller::class, 'reportClass'])->middleware('permission:report-cards.view-own-class')->name('report-class');
        Route::get('/rapor/detail/{reportCard?}', [Module10Controller::class, 'reportDetail'])->middleware('permission:report-cards.view|report-cards.view-own-class')->name('report-detail');
        Route::get('/verifikasi-rapor', [Module10Controller::class, 'reportVerification'])->middleware('permission:report-cards.verify|report-cards.finalize')->name('report-verification');
        Route::get('/laporan', [Module10Controller::class, 'reports'])->middleware('permission:assessment-reports.view')->name('reports');
        Route::get('/rapor/{reportCard?}/print', [Module10Controller::class, 'printReport'])->middleware('permission:report-cards.print|report-cards.print-own-class')->name('report.print');
    });

    Route::get('/assessments/dashboard', [AssessmentController::class, 'dashboard'])->middleware('permission:assessment-reports.view')->name('assessments.dashboard');
    Route::get('/assessment-components', [AssessmentController::class, 'index'])->middleware('permission:assessments.view-own')->name('assessment-components.index');
    Route::get('/assessment-components/create', [AssessmentController::class, 'create'])->middleware('permission:assessments.create')->name('assessment-components.create');
    Route::post('/assessment-components', [AssessmentController::class, 'store'])->middleware('permission:assessments.create')->name('assessment-components.store');
    Route::get('/assessment-components/{assessmentComponent}', [AssessmentController::class, 'show'])->middleware('permission:assessments.view-own')->name('assessment-components.show');
    Route::get('/assessment-components/{assessmentComponent}/edit', [AssessmentController::class, 'edit'])->middleware('permission:assessments.update')->name('assessment-components.edit');
    Route::put('/assessment-components/{assessmentComponent}', [AssessmentController::class, 'update'])->middleware('permission:assessments.update')->name('assessment-components.update');
    Route::get('/assessment-components/{assessmentComponent}/scores', [AssessmentController::class, 'scores'])->middleware('permission:assessments.update')->name('assessment-components.scores');
    Route::post('/assessment-components/{assessmentComponent}/scores', [AssessmentController::class, 'storeScores'])->middleware('permission:assessments.update')->name('assessment-components.scores.store');
    Route::patch('/assessment-components/{assessmentComponent}/publish', [AssessmentController::class, 'publish'])->middleware('permission:assessments.publish')->name('assessment-components.publish');
    Route::get('/assessment-recap', [AssessmentController::class, 'recap'])->middleware('permission:assessment-reports.view')->name('assessments.recap');
    Route::get('/predicate-ranges', [AssessmentController::class, 'predicates'])->middleware('permission:predicate-ranges.manage')->name('predicate-ranges.index');
    Route::put('/predicate-ranges', [AssessmentController::class, 'savePredicates'])->middleware('permission:predicate-ranges.manage')->name('predicate-ranges.update');

    Route::get('/report-cards/dashboard', [ReportCardController::class, 'dashboard'])->middleware('permission:report-cards.view-class')->name('report-cards.dashboard');
    Route::get('/report-cards/classes', [ReportCardController::class, 'classes'])->middleware('permission:report-cards.view-class')->name('report-cards.classes');
    Route::get('/report-cards/classes/{classroom}/students', [ReportCardController::class, 'students'])->middleware('permission:report-cards.view-class')->name('report-cards.students');
    Route::post('/report-cards/enrollments/{enrollment}/generate', [ReportCardController::class, 'generate'])->middleware('permission:report-cards.generate')->name('report-cards.generate');
    Route::get('/report-cards/verification', [ReportCardController::class, 'verification'])->middleware('permission:report-cards.approve')->name('report-cards.verification');
    Route::get('/report-cards/{reportCard}', [ReportCardController::class, 'show'])->middleware('permission:report-cards.view')->name('report-cards.show');
    Route::put('/report-cards/{reportCard}', [ReportCardController::class, 'update'])->middleware('permission:report-cards.update')->name('report-cards.update');
    Route::patch('/report-cards/{reportCard}/submit', [ReportCardController::class, 'submit'])->middleware('permission:report-cards.submit')->name('report-cards.submit');
    Route::patch('/report-cards/{reportCard}/approve', [ReportCardController::class, 'approve'])->middleware('permission:report-cards.approve')->name('report-cards.approve');
    Route::patch('/report-cards/{reportCard}/lock', [ReportCardController::class, 'lock'])->middleware('permission:report-cards.lock')->name('report-cards.lock');
    Route::patch('/report-cards/{reportCard}/reopen', [ReportCardController::class, 'reopen'])->middleware('permission:report-cards.reopen')->name('report-cards.reopen');
    Route::get('/report-cards/{reportCard}/print', [ReportCardController::class, 'print'])->middleware('permission:report-cards.print')->name('report-cards.print');

    Route::prefix('payroll-pegawai')->name('payroll.')->group(function (): void {
        Route::get('/', PayrollDashboardController::class)->middleware('permission:payroll-dashboard.view')->name('dashboard');
        Route::resource('components', PayrollComponentController::class)->parameters(['components' => 'component'])->except(['destroy'])->middleware(['permission:payroll-components.view']);
        Route::patch('components/{component}/toggle', [PayrollComponentController::class, 'toggle'])->middleware('permission:payroll-components.manage')->name('components.toggle');
        Route::get('salary-profiles/missing', [EmployeeSalaryProfileController::class, 'missing'])->middleware('permission:salary-profiles.view')->name('salary-profiles.missing');
        Route::get('salary-profiles/{employee}/history', [EmployeeSalaryProfileController::class, 'history'])->middleware('permission:salary-profiles.view')->name('salary-profiles.history');
        Route::get('salary-profiles/create', [EmployeeSalaryProfileController::class, 'create'])->middleware('permission:salary-profiles.manage')->name('salary-profiles.create');
        Route::post('salary-profiles', [EmployeeSalaryProfileController::class, 'store'])->middleware('permission:salary-profiles.manage')->name('salary-profiles.store');
        Route::resource('salary-profiles', EmployeeSalaryProfileController::class)->only(['index','show'])->middleware('permission:salary-profiles.view');
        Route::get('periods/create', [PayrollPeriodController::class, 'create'])->middleware('permission:payroll-periods.manage')->name('periods.create');
        Route::post('periods', [PayrollPeriodController::class, 'store'])->middleware('permission:payroll-periods.manage')->name('periods.store');
        Route::resource('periods', PayrollPeriodController::class)->only(['index','show'])->middleware('permission:payroll-periods.view');
        Route::get('periods/{period}/generate', [PayrollRunController::class, 'generate'])->middleware('permission:payroll-runs.generate')->name('runs.generate');
        Route::post('periods/{period}/generate', [PayrollRunController::class, 'store'])->middleware('permission:payroll-runs.generate')->name('runs.store');
        Route::get('runs', [PayrollRunController::class, 'index'])->middleware('permission:payroll-runs.view')->name('runs.index');
        Route::get('runs/{run}', [PayrollRunController::class, 'show'])->middleware('permission:payroll-runs.view')->name('runs.show');
        Route::get('runs/{run}/preview', [PayrollRunController::class, 'preview'])->middleware('permission:payroll-runs.view')->name('runs.preview');
        Route::get('items/{item}', [PayrollRunController::class, 'item'])->middleware('permission:payroll-runs.view')->name('runs.items.show');
        Route::post('items/{item}/adjustments', [PayrollAdjustmentController::class, 'store'])->middleware('permission:payroll-adjustments.create')->name('adjustments.store');
        Route::get('adjustments', [PayrollAdjustmentController::class, 'index'])->middleware('permission:payroll-adjustments.view')->name('adjustments.index');
        Route::get('approvals', [PayrollApprovalController::class, 'index'])->middleware('permission:payroll-runs.approve|payroll-runs.reject|payroll-runs.finalize')->name('approvals.index');
        Route::patch('runs/{run}/submit', [PayrollApprovalController::class, 'submit'])->middleware('permission:payroll-runs.submit')->name('runs.submit');
        Route::patch('runs/{run}/approve', [PayrollApprovalController::class, 'approve'])->middleware('permission:payroll-runs.approve')->name('runs.approve');
        Route::patch('runs/{run}/reject', [PayrollApprovalController::class, 'reject'])->middleware('permission:payroll-runs.reject')->name('runs.reject');
        Route::patch('runs/{run}/finalize', [PayrollApprovalController::class, 'finalize'])->middleware('permission:payroll-runs.finalize')->name('runs.finalize');
        Route::get('payments', [PayrollPaymentController::class, 'index'])->middleware('permission:payroll-payments.view')->name('payments.index');
        Route::post('runs/{run}/payments', [PayrollPaymentController::class, 'store'])->middleware('permission:payroll-payments.create')->name('payments.store');
        Route::patch('payments/{payment}/cancel', [PayrollPaymentController::class, 'cancel'])->middleware('permission:payroll-payments.cancel')->name('payments.cancel');
        Route::get('payslips/mine', [EmployeePayslipController::class, 'index'])->middleware('permission:payslips.view-own')->name('payslips.mine');
        Route::get('payslips/mine/{item}', [EmployeePayslipController::class, 'show'])->middleware('permission:payslips.view-own')->name('payslips.mine.show');
        Route::get('payslips/{item}', [PayslipController::class, 'show'])->middleware('permission:payslips.view')->name('payslips.show');
        Route::get('payslips/{item}/print', [PayslipController::class, 'print'])->middleware('permission:payslips.print')->name('payslips.print');
        Route::get('reports', [PayrollReportController::class, 'index'])->middleware('permission:payroll-reports.view')->name('reports.index');
        Route::get('reports/export/csv', [PayrollReportController::class, 'export'])->middleware('permission:payroll-reports.export')->name('reports.export');
        Route::get('reports/print/recap', [PayrollReportController::class, 'print'])->middleware('permission:payroll-reports.print')->name('reports.print');
    });

    Route::prefix('inventory')->name('inventory.')->group(function (): void {
        Route::get('/dashboard', InventoryDashboardController::class)->middleware('permission:inventory.view')->name('dashboard');
        Route::get('/categories', [InventoryCrudController::class, 'categories'])->middleware('permission:inventory.manage-master')->name('categories.index');
        Route::post('/categories', [InventoryCrudController::class, 'storeCategory'])->middleware('permission:inventory.manage-master')->name('categories.store');
        Route::put('/categories/{category}', [InventoryCrudController::class, 'updateCategory'])->middleware('permission:inventory.manage-master')->name('categories.update');
        Route::get('/locations', [InventoryCrudController::class, 'locations'])->middleware('permission:inventory.manage-master')->name('locations.index');
        Route::post('/locations', [InventoryCrudController::class, 'storeLocation'])->middleware('permission:inventory.manage-master')->name('locations.store');
        Route::put('/locations/{location}', [InventoryCrudController::class, 'updateLocation'])->middleware('permission:inventory.manage-master')->name('locations.update');
        Route::get('/conditions', [InventoryCrudController::class, 'conditions'])->middleware('permission:inventory.manage-master')->name('conditions.index');
        Route::put('/conditions/{condition}', [InventoryCrudController::class, 'updateCondition'])->middleware('permission:inventory.manage-master')->name('conditions.update');
        Route::get('/items', [InventoryCrudController::class, 'items'])->middleware('permission:inventory.view')->name('items.index');
        Route::get('/items/create', [InventoryCrudController::class, 'createItem'])->middleware('permission:inventory.create')->name('items.create');
        Route::post('/items', [InventoryCrudController::class, 'storeItem'])->middleware('permission:inventory.create')->name('items.store');
        Route::get('/items/{item}', [InventoryCrudController::class, 'showItem'])->middleware('permission:inventory.view')->name('items.show');
        Route::get('/items/{item}/edit', [InventoryCrudController::class, 'editItem'])->middleware('permission:inventory.update')->name('items.edit');
        Route::put('/items/{item}', [InventoryCrudController::class, 'updateItem'])->middleware('permission:inventory.update')->name('items.update');
        Route::get('/transactions', [InventoryTransactionController::class, 'index'])->middleware('permission:inventory.view')->name('transactions.index');
        Route::get('/transactions/create', [InventoryTransactionController::class, 'create'])->middleware('permission:inventory.adjust|inventory.transfer|inventory.change-condition')->name('transactions.create');
        Route::post('/transactions', [InventoryTransactionController::class, 'store'])->middleware('permission:inventory.adjust|inventory.transfer|inventory.change-condition')->name('transactions.store');
        Route::patch('/transactions/{transaction}/reverse', [InventoryTransactionController::class, 'reverse'])->middleware('permission:inventory.reverse')->name('transactions.reverse');
        Route::resource('stock-opnames', InventoryStockOpnameController::class)->only(['index','create','store','show','update'])->middleware('permission:inventory.stock-opname');
        Route::patch('/stock-opnames/{stockOpname}/post', [InventoryStockOpnameController::class, 'post'])->middleware('permission:inventory.stock-opname')->name('stock-opnames.post');
        Route::get('/reports', [InventoryReportController::class, 'index'])->middleware('permission:inventory.report')->name('reports.index');
        Route::get('/reports/print', [InventoryReportController::class, 'print'])->middleware('permission:inventory.print')->name('reports.print');
        Route::get('/reports/pdf', [InventoryReportController::class, 'pdf'])->middleware('permission:inventory.export')->name('reports.pdf');
        Route::get('/reports/export', [InventoryReportController::class, 'export'])->middleware('permission:inventory.export')->name('reports.export');
    });

    Route::patch('/users/{user}/toggle', [UserManagementController::class, 'toggle'])->middleware('permission:users.activate')->name('users.toggle');
});

require __DIR__.'/finance.php';
