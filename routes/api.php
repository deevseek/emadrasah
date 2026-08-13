<?php
declare(strict_types=1);
use App\Http\Controllers\Api\{RfidAttendanceController,RfidDeviceCommandController}; use Illuminate\Support\Facades\Route;
Route::post('/rfid/attendance',RfidAttendanceController::class)->middleware(['rfid.device','throttle:60,1'])->name('api.rfid.attendance');
Route::middleware(['rfid.device','throttle:120,1'])->prefix('rfid/device')->group(function():void{Route::post('/heartbeat',[RfidDeviceCommandController::class,'heartbeat']);Route::get('/command',[RfidDeviceCommandController::class,'next']);Route::post('/command/{command}/complete',[RfidDeviceCommandController::class,'complete']);Route::post('/command/{command}/fail',[RfidDeviceCommandController::class,'fail']);});
