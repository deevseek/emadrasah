<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\PasswordUpdateController;
use App\Http\Controllers\Foundation\DashboardController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Public\PaymentVerificationController;


Route::get('/verify/payment/{token}', PaymentVerificationController::class)->where('token','[A-Za-z0-9]{48}')->name('payment.verify');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    Route::get('/parent/login', [AuthenticatedSessionController::class, 'createParent'])->name('parent.login');
    Route::post('/parent/login', [AuthenticatedSessionController::class, 'store'])->name('parent.login.store');
    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'active', 'force-password-change'])->group(function (): void {
    Route::get('/dashboard', DashboardController::class)
        ->middleware('permission:dashboard.view')
        ->name('dashboard');
    Route::get('/password/change', [PasswordUpdateController::class, 'edit'])->name('password.change');
    Route::put('/password/change', [PasswordUpdateController::class, 'update'])->name('password.change.update');
});

require __DIR__.'/access.php';
require __DIR__.'/settings.php';
require __DIR__.'/school.php';

require __DIR__.'/academic-periods.php';
require __DIR__.'/personnel.php';
require __DIR__.'/students.php';

require __DIR__.'/classrooms.php';
require __DIR__.'/academic.php';

require __DIR__.'/hrd.php';

require __DIR__.'/parent.php';
require __DIR__.'/consultations.php';
require __DIR__.'/finance.php';

require __DIR__.'/website.php';
