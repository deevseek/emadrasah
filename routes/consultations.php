<?php

declare(strict_types=1);

use App\Http\Controllers\Consultation\TeacherConsultationController;
use Illuminate\Support\Facades\Route;

Route::prefix('consultations')->name('consultations.')->middleware(['auth', 'active', 'force-password-change', 'permission:consultations.reply'])->group(function (): void {
    Route::get('/', [TeacherConsultationController::class, 'index'])->name('index');
    Route::get('/{consultation}', [TeacherConsultationController::class, 'show'])->name('show');
    Route::post('/{consultation}/messages', [TeacherConsultationController::class, 'store'])->name('store');
    Route::get('/{consultation}/messages', [TeacherConsultationController::class, 'messages'])->name('messages');
});
