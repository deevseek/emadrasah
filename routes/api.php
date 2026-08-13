<?php

declare(strict_types=1);

use App\Http\Controllers\Api\RfidAttendanceController;
use Illuminate\Support\Facades\Route;

Route::post('/rfid/attendance', RfidAttendanceController::class)->middleware(['rfid.device', 'throttle:60,1'])->name('api.rfid.attendance');
