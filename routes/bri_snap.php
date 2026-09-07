<?php

declare(strict_types=1);

use App\Http\Controllers\Api\BriCallbackController;
use App\Http\Controllers\Api\BriCallbackTokenController;
use Illuminate\Support\Facades\Route;

Route::post('/snap/v1.0/access-token/b2b', BriCallbackTokenController::class)
    ->middleware('throttle:30,1')
    ->name('snap.bri.callback-token');

Route::post('/snap/v1.1/qr/qr-mpm-notify', [BriCallbackController::class, 'qrisPayment'])
    ->middleware(['bri.callback', 'throttle:60,1'])
    ->name('snap.bri.qris.notify');
