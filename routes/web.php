<?php

declare(strict_types=1);

use App\Http\Controllers\Academic\AcademicResourceController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\PasswordUpdateController;
use App\Http\Controllers\Foundation\DashboardController;
use App\Http\Controllers\Foundation\SchoolProfileController;
use App\Http\Controllers\Foundation\SettingController;
use App\Http\Controllers\Foundation\UserManagementController;
use App\Http\Controllers\Attendance\EmployeeAttendanceController;
use App\Http\Controllers\Attendance\EmployeeLeaveController;
use App\Http\Controllers\Attendance\StudentAttendanceController;
use App\Http\Controllers\Attendance\TeachingJournalController;
use App\Http\Controllers\StudentAffairs\EnrollmentController;
use App\Http\Controllers\StudentAffairs\GuardianController;
use App\Http\Controllers\StudentAffairs\StudentController;
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

    Route::get('/employee-leaves', [EmployeeLeaveController::class, 'index'])->middleware('permission:employee-leaves.view-own')->name('employee-leaves.index');
    Route::get('/employee-leaves/create', [EmployeeLeaveController::class, 'create'])->middleware('permission:employee-leaves.create')->name('employee-leaves.create');
    Route::post('/employee-leaves', [EmployeeLeaveController::class, 'store'])->middleware('permission:employee-leaves.create')->name('employee-leaves.store');
    Route::get('/employee-leaves/{employeeLeave}', [EmployeeLeaveController::class, 'show'])->middleware('permission:employee-leaves.view-own')->name('employee-leaves.show');
    Route::patch('/employee-leaves/{employeeLeave}/cancel', [EmployeeLeaveController::class, 'cancel'])->middleware('permission:employee-leaves.cancel')->name('employee-leaves.cancel');
    Route::patch('/employee-leaves/{employeeLeave}/approve', [EmployeeLeaveController::class, 'approve'])->middleware('permission:employee-leaves.approve')->name('employee-leaves.approve');
    Route::patch('/employee-leaves/{employeeLeave}/reject', [EmployeeLeaveController::class, 'reject'])->middleware('permission:employee-leaves.reject')->name('employee-leaves.reject');
    Route::get('/employee-leaves/{employeeLeave}/download', [EmployeeLeaveController::class, 'download'])->middleware('permission:employee-leaves.view-own')->name('employee-leaves.download');

    Route::get('/student-attendances', [StudentAttendanceController::class, 'index'])->middleware('permission:student-attendances.view')->name('student-attendances.index');
    Route::get('/student-attendances/create', [StudentAttendanceController::class, 'create'])->middleware('permission:student-attendances.create')->name('student-attendances.create');
    Route::post('/student-attendances', [StudentAttendanceController::class, 'store'])->middleware('permission:student-attendances.create')->name('student-attendances.store');

    Route::get('/teaching-journals', [TeachingJournalController::class, 'index'])->middleware('permission:teaching-journals.view-own')->name('teaching-journals.index');
    Route::get('/teaching-journals/create', [TeachingJournalController::class, 'create'])->middleware('permission:teaching-journals.create')->name('teaching-journals.create');
    Route::post('/teaching-journals', [TeachingJournalController::class, 'store'])->middleware('permission:teaching-journals.create')->name('teaching-journals.store');
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

    Route::get('/students', [StudentController::class, 'index'])->middleware('permission:students.view')->name('students.index');
    Route::get('/students/create', [StudentController::class, 'create'])->middleware('permission:students.create')->name('students.create');
    Route::post('/students', [StudentController::class, 'store'])->middleware('permission:students.create')->name('students.store');
    Route::get('/students/{student}', [StudentController::class, 'show'])->middleware('permission:students.view')->name('students.show');
    Route::get('/students/{student}/edit', [StudentController::class, 'edit'])->middleware('permission:students.update')->name('students.edit');
    Route::put('/students/{student}', [StudentController::class, 'update'])->middleware('permission:students.update')->name('students.update');
    Route::delete('/students/{student}', [StudentController::class, 'destroy'])->middleware('permission:students.delete')->name('students.destroy');
    Route::post('/students/{student}/guardians', [StudentController::class, 'attachGuardian'])->middleware('permission:student-guardians.create')->name('students.guardians.store');
    Route::put('/students/{student}/guardians/{guardian}', [StudentController::class, 'updateGuardian'])->middleware('permission:student-guardians.update')->name('students.guardians.update');
    Route::delete('/students/{student}/guardians/{guardian}', [StudentController::class, 'detachGuardian'])->middleware('permission:student-guardians.delete')->name('students.guardians.destroy');
    Route::post('/students/{student}/status', [StudentController::class, 'changeStatus'])->middleware('permission:students.change-status')->name('students.status.store');
    Route::post('/students/{student}/documents', [StudentController::class, 'uploadDocument'])->middleware('permission:students.manage-documents')->name('students.documents.store');
    Route::get('/student-documents/{document}/download', [StudentController::class, 'downloadDocument'])->middleware('permission:students.manage-documents')->name('student-documents.download');
    Route::delete('/student-documents/{document}', [StudentController::class, 'deleteDocument'])->middleware('permission:students.manage-documents')->name('student-documents.destroy');
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
    Route::post('/student-enrollments/{enrollment}/transfer', [EnrollmentController::class, 'transfer'])->middleware('permission:student-enrollments.transfer')->name('student-enrollments.transfer');
    Route::delete('/student-enrollments/{enrollment}', [EnrollmentController::class, 'destroy'])->middleware('permission:student-enrollments.delete')->name('student-enrollments.destroy');

    Route::prefix('academic')->group(function (): void {
        Route::get('/grade-levels', [AcademicResourceController::class, 'gradeLevels'])->middleware('permission:grade-levels.view')->name('grade-levels.index');
        Route::get('/grade-levels/create', [AcademicResourceController::class, 'createGradeLevel'])->middleware('permission:grade-levels.create')->name('grade-levels.create');
        Route::post('/grade-levels', [AcademicResourceController::class, 'storeGradeLevel'])->middleware('permission:grade-levels.create')->name('grade-levels.store');
        Route::get('/grade-levels/{gradeLevel}/edit', [AcademicResourceController::class, 'editGradeLevel'])->middleware('permission:grade-levels.update')->name('grade-levels.edit');
        Route::put('/grade-levels/{gradeLevel}', [AcademicResourceController::class, 'updateGradeLevel'])->middleware('permission:grade-levels.update')->name('grade-levels.update');
        Route::delete('/grade-levels/{gradeLevel}', [AcademicResourceController::class, 'destroyGradeLevel'])->middleware('permission:grade-levels.delete')->name('grade-levels.destroy');
        Route::resource('classrooms', AcademicResourceController::class)->only([]);
        Route::get('/classrooms', [AcademicResourceController::class, 'classrooms'])->middleware('permission:classrooms.view')->name('classrooms.index');
        Route::get('/classrooms/create', [AcademicResourceController::class, 'createClassroom'])->middleware('permission:classrooms.create')->name('classrooms.create');
        Route::post('/classrooms', [AcademicResourceController::class, 'storeClassroom'])->middleware('permission:classrooms.create')->name('classrooms.store');
        Route::get('/classrooms/{classroom}/edit', [AcademicResourceController::class, 'editClassroom'])->middleware('permission:classrooms.update')->name('classrooms.edit');
        Route::put('/classrooms/{classroom}', [AcademicResourceController::class, 'updateClassroom'])->middleware('permission:classrooms.update')->name('classrooms.update');
        Route::delete('/classrooms/{classroom}', [AcademicResourceController::class, 'destroyClassroom'])->middleware('permission:classrooms.delete')->name('classrooms.destroy');
        Route::get('/subjects', [AcademicResourceController::class, 'subjects'])->middleware('permission:subjects.view')->name('subjects.index');
        Route::get('/subjects/create', [AcademicResourceController::class, 'createSubject'])->middleware('permission:subjects.create')->name('subjects.create');
        Route::post('/subjects', [AcademicResourceController::class, 'storeSubject'])->middleware('permission:subjects.create')->name('subjects.store');
        Route::get('/subjects/{subject}/edit', [AcademicResourceController::class, 'editSubject'])->middleware('permission:subjects.update')->name('subjects.edit');
        Route::put('/subjects/{subject}', [AcademicResourceController::class, 'updateSubject'])->middleware('permission:subjects.update')->name('subjects.update');
        Route::delete('/subjects/{subject}', [AcademicResourceController::class, 'destroySubject'])->middleware('permission:subjects.delete')->name('subjects.destroy');
        Route::get('/employees', [AcademicResourceController::class, 'employees'])->middleware('permission:employees.view')->name('employees.index');
        Route::get('/employees/create', [AcademicResourceController::class, 'createEmployee'])->middleware('permission:employees.create')->name('employees.create');
        Route::post('/employees', [AcademicResourceController::class, 'storeEmployee'])->middleware('permission:employees.create')->name('employees.store');
        Route::get('/employees/{employee}/edit', [AcademicResourceController::class, 'editEmployee'])->middleware('permission:employees.update')->name('employees.edit');
        Route::put('/employees/{employee}', [AcademicResourceController::class, 'updateEmployee'])->middleware('permission:employees.update')->name('employees.update');
        Route::delete('/employees/{employee}', [AcademicResourceController::class, 'destroyEmployee'])->middleware('permission:employees.delete')->name('employees.destroy');
        Route::get('/teaching-assignments', [AcademicResourceController::class, 'teachingAssignments'])->middleware('permission:teaching-assignments.view')->name('teaching-assignments.index');
        Route::get('/teaching-assignments/create', [AcademicResourceController::class, 'createTeachingAssignment'])->middleware('permission:teaching-assignments.create')->name('teaching-assignments.create');
        Route::post('/teaching-assignments', [AcademicResourceController::class, 'storeTeachingAssignment'])->middleware('permission:teaching-assignments.create')->name('teaching-assignments.store');
        Route::get('/teaching-assignments/{teachingAssignment}/edit', [AcademicResourceController::class, 'editTeachingAssignment'])->middleware('permission:teaching-assignments.update')->name('teaching-assignments.edit');
        Route::put('/teaching-assignments/{teachingAssignment}', [AcademicResourceController::class, 'updateTeachingAssignment'])->middleware('permission:teaching-assignments.update')->name('teaching-assignments.update');
        Route::delete('/teaching-assignments/{teachingAssignment}', [AcademicResourceController::class, 'destroyTeachingAssignment'])->middleware('permission:teaching-assignments.delete')->name('teaching-assignments.destroy');
        Route::get('/schedules', [AcademicResourceController::class, 'schedules'])->middleware('permission:schedules.view')->name('schedules.index');
        Route::get('/schedules/create', [AcademicResourceController::class, 'createSchedule'])->middleware('permission:schedules.create')->name('schedules.create');
        Route::post('/schedules', [AcademicResourceController::class, 'storeSchedule'])->middleware('permission:schedules.create')->name('schedules.store');
        Route::get('/schedules/{schedule}/edit', [AcademicResourceController::class, 'editSchedule'])->middleware('permission:schedules.update')->name('schedules.edit');
        Route::put('/schedules/{schedule}', [AcademicResourceController::class, 'updateSchedule'])->middleware('permission:schedules.update')->name('schedules.update');
        Route::delete('/schedules/{schedule}', [AcademicResourceController::class, 'destroySchedule'])->middleware('permission:schedules.delete')->name('schedules.destroy');
    });

    Route::patch('/users/{user}/toggle', [UserManagementController::class, 'toggle'])->middleware('permission:users.deactivate')->name('users.toggle');
});
