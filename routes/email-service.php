<?php

declare(strict_types=1);

use App\Http\Controllers\EmailService\OutgoingEmailController;
use Illuminate\Support\Facades\Route;

Route::prefix('pelayanan/email')->name('email-service.')->middleware(['auth', 'active', 'force-password-change'])->group(function (): void {
    Route::get('/', [OutgoingEmailController::class, 'index'])->middleware('permission:email-service.view')->name('index');
    Route::get('/tulis', [OutgoingEmailController::class, 'create'])->middleware('permission:email-service.send')->name('create');
    Route::post('/', [OutgoingEmailController::class, 'store'])->middleware('permission:email-service.send')->name('store');
    Route::get('/{outgoingEmail}', [OutgoingEmailController::class, 'show'])->middleware('permission:email-service.view')->name('show');
    Route::get('/{outgoingEmail}/lampiran/{attachment}', [OutgoingEmailController::class, 'attachment'])->whereNumber('attachment')->middleware('permission:email-service.view')->name('attachment');
    Route::post('/{outgoingEmail}/kirim', [OutgoingEmailController::class, 'sendDraft'])->middleware('permission:email-service.send')->name('send');
    Route::post('/{outgoingEmail}/kirim-ulang', [OutgoingEmailController::class, 'resend'])->middleware('permission:email-service.send')->name('resend');
});
