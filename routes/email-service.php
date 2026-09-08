<?php

declare(strict_types=1);

use App\Http\Controllers\EmailService\OutgoingEmailController;
use App\Http\Controllers\EmailService\IncomingEmailController;
use Illuminate\Support\Facades\Route;

Route::prefix('pelayanan/email')->name('email-service.')->middleware(['auth', 'active', 'force-password-change'])->group(function (): void {
    Route::get('/', [IncomingEmailController::class, 'index'])->middleware('permission:email-service.view')->name('index');
    Route::get('/terkirim', [OutgoingEmailController::class, 'sent'])->middleware('permission:email-service.view')->name('sent');
    Route::get('/tulis', [OutgoingEmailController::class, 'create'])->middleware('permission:email-service.send')->name('create');
    Route::post('/', [OutgoingEmailController::class, 'store'])->middleware('permission:email-service.send')->name('store');
    Route::get('/masuk/{incomingEmail}', [IncomingEmailController::class, 'show'])->middleware('permission:email-service.view')->name('incoming.show');
    Route::get('/masuk/{incomingEmail}/lampiran/{attachment}', [IncomingEmailController::class, 'attachment'])->whereNumber('attachment')->middleware('permission:email-service.view')->name('incoming.attachment');
    Route::get('/keluar/{outgoingEmail}', [OutgoingEmailController::class, 'show'])->middleware('permission:email-service.view')->name('show');
    Route::get('/keluar/{outgoingEmail}/lampiran/{attachment}', [OutgoingEmailController::class, 'attachment'])->whereNumber('attachment')->middleware('permission:email-service.view')->name('attachment');
    Route::post('/keluar/{outgoingEmail}/kirim', [OutgoingEmailController::class, 'sendDraft'])->middleware('permission:email-service.send')->name('send');
    Route::post('/keluar/{outgoingEmail}/kirim-ulang', [OutgoingEmailController::class, 'resend'])->middleware('permission:email-service.send')->name('resend');
});
