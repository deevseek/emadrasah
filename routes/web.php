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
use App\Http\Controllers\Attendance\StudentAttendanceController;
use App\Http\Controllers\Attendance\TeachingJournalController;
use App\Http\Controllers\StudentAffairs\EnrollmentController;
use App\Http\Controllers\StudentAffairs\GuardianController;
use App\Http\Controllers\StudentAffairs\StudentController;
use App\Http\Controllers\Btaq\BtaqGroupController;
use App\Http\Controllers\Btaq\BtaqJournalController;
use App\Http\Controllers\Btaq\BtaqLevelController;
use App\Http\Controllers\Btaq\BtaqMaterialController;
use App\Http\Controllers\Assessment\AssessmentController;
use App\Http\Controllers\ReportCard\ReportCardController;
use Illuminate\Support\Facades\Route;

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
    Route::get('/employee-attendances', [EmployeeAttendanceController::class, 'index'])->middleware('permission:employee-attendances.view')->name('employee-attendances.index');
    Route::get('/employee-attendances/{employeeAttendance}', [EmployeeAttendanceController::class, 'show'])->middleware('permission:employee-attendances.view')->name('employee-attendances.show');
    Route::patch('/employee-attendances/{employeeAttendance}/verify', [EmployeeAttendanceController::class, 'verify'])->middleware('permission:employee-attendances.verify')->name('employee-attendances.verify');
    Route::patch('/employee-attendances/{employeeAttendance}/correct', [EmployeeAttendanceController::class, 'correct'])->middleware('permission:employee-attendances.correct')->name('employee-attendances.correct');

    Route::get('/employee-leaves', [EmployeeLeaveController::class, 'index'])->middleware('permission:employee-leaves.view-own')->name('employee-leaves.index');
    Route::get('/employee-leaves/create', [EmployeeLeaveController::class, 'create'])->middleware('permission:employee-leaves.create')->name('employee-leaves.create');
    Route::post('/employee-leaves', [EmployeeLeaveController::class, 'store'])->middleware('permission:employee-leaves.create')->name('employee-leaves.store');
    Route::get('/employee-leaves/{employeeLeave}', [EmployeeLeaveController::class, 'show'])->middleware('permission:employee-leaves.view-own|employee-leaves.view')->name('employee-leaves.show');
    Route::patch('/employee-leaves/{employeeLeave}/cancel', [EmployeeLeaveController::class, 'cancel'])->middleware('permission:employee-leaves.cancel')->name('employee-leaves.cancel');
    Route::patch('/employee-leaves/{employeeLeave}/approve', [EmployeeLeaveController::class, 'approve'])->middleware('permission:employee-leaves.approve')->name('employee-leaves.approve');
    Route::patch('/employee-leaves/{employeeLeave}/reject', [EmployeeLeaveController::class, 'reject'])->middleware('permission:employee-leaves.reject')->name('employee-leaves.reject');
    Route::get('/employee-leaves/{employeeLeave}/download', [EmployeeLeaveController::class, 'download'])->middleware('permission:employee-leaves.view-own|employee-leaves.view')->name('employee-leaves.download');


    Route::get('/employees/mine', [EmployeeController::class, 'mine'])->middleware('permission:employees.view-own')->name('employees.mine');
    Route::get('/employees/export', [EmployeeController::class, 'export'])->middleware('permission:employees.export')->name('employees.export');
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

    Route::get('/student-attendances', [StudentAttendanceController::class, 'index'])->middleware('permission:student-attendances.view')->name('student-attendances.index');
    Route::get('/student-attendances/create', [StudentAttendanceController::class, 'create'])->middleware('permission:student-attendances.create')->name('student-attendances.create');
    Route::post('/student-attendances', [StudentAttendanceController::class, 'store'])->middleware('permission:student-attendances.create')->name('student-attendances.store');

    Route::get('/teaching-journals', [TeachingJournalController::class, 'index'])->middleware('permission:teaching-journals.view-own')->name('teaching-journals.index');
    Route::get('/teaching-journals/create', [TeachingJournalController::class, 'create'])->middleware('permission:teaching-journals.create')->name('teaching-journals.create');
    Route::post('/teaching-journals', [TeachingJournalController::class, 'store'])->middleware('permission:teaching-journals.create')->name('teaching-journals.store');
    Route::get('/teaching-journals/{teachingJournal}/edit', [TeachingJournalController::class, 'edit'])->middleware('permission:teaching-journals.update')->name('teaching-journals.edit');
    Route::put('/teaching-journals/{teachingJournal}', [TeachingJournalController::class, 'update'])->middleware('permission:teaching-journals.update')->name('teaching-journals.update');
    Route::get('/teaching-journals/{teachingJournal}/print', [TeachingJournalController::class, 'print'])->middleware('permission:teaching-journals.view-own')->name('teaching-journals.print');
    Route::get('/teaching-journals/{teachingJournal}', [TeachingJournalController::class, 'show'])->middleware('permission:teaching-journals.view-own')->name('teaching-journals.show');
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
    Route::patch('/users/{user}/toggle', [UserManagementController::class, 'toggle'])->middleware('permission:users.activate')->name('users.toggle');
});

require __DIR__.'/finance.php';
