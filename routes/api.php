<?php
declare(strict_types=1);
use App\Http\Controllers\Api\{RfidAttendanceController,RfidDeviceCommandController}; use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\BriCallbackController;
Route::prefix('bri/snap-bi')->middleware(['bri.callback','throttle:60,1'])->group(function (): void {
    Route::post('briva/inquiry', [BriCallbackController::class, 'inquiry'])->name('api.bri.briva.inquiry');
    Route::post('briva/payment', [BriCallbackController::class, 'brivaPayment'])->name('api.bri.briva.payment');
    Route::post('qris/payment', [BriCallbackController::class, 'qrisPayment'])->name('api.bri.qris.payment');
});
Route::post('/rfid/attendance',RfidAttendanceController::class)->middleware(['rfid.device','throttle:60,1'])->name('api.rfid.attendance');
Route::middleware(['rfid.device','throttle:120,1'])->prefix('rfid/device')->group(function():void{Route::post('/heartbeat',[RfidDeviceCommandController::class,'heartbeat']);Route::get('/command',[RfidDeviceCommandController::class,'next']);Route::post('/command/{command}/complete',[RfidDeviceCommandController::class,'complete']);Route::post('/command/{command}/fail',[RfidDeviceCommandController::class,'fail']);});
