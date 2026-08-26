<?php

declare(strict_types=1);

use App\Http\Controllers\Settings\ApplicationSettingController;
use App\Http\Controllers\Settings\RfidDeviceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'force-password-change'])->prefix('pengaturan')->group(function (): void {
    Route::get('/aplikasi', [ApplicationSettingController::class, 'edit'])->middleware('permission:application-settings.view|hrd-settings.view|finance.bri.configure')->name('application-settings.edit');
    Route::post('/aplikasi/face-recognition/status', [ApplicationSettingController::class, 'faceRecognitionStatus'])->middleware('permission:hrd-settings.view')->name('application-settings.face-recognition.status');
    Route::put('/aplikasi', [ApplicationSettingController::class, 'update'])->middleware('permission:application-settings.update')->name('application-settings.update');
    Route::put('/aplikasi/general', [ApplicationSettingController::class, 'update'])->middleware('permission:application-settings.update')->name('application-settings.general.update');
    Route::put('/aplikasi/hrd', [ApplicationSettingController::class, 'updateHrd'])->middleware('permission:hrd-settings.update')->name('application-settings.hrd.update');
    Route::put('/aplikasi/bri', [ApplicationSettingController::class, 'updateBri'])->middleware('permission:finance.bri.configure')->name('application-settings.bri.update');
    Route::post('/aplikasi/bri/test', [ApplicationSettingController::class, 'testBri'])->middleware('permission:finance.bri.configure')->name('application-settings.bri.test');
    Route::middleware('permission:rfid-device.manage')->prefix('perangkat-rfid')->name('rfid-devices.')->group(function (): void {
        Route::get('/', [RfidDeviceController::class, 'index'])->name('index');
        Route::post('/', [RfidDeviceController::class, 'store'])->name('store');
        Route::post('/{device}/ganti-token', [RfidDeviceController::class, 'rotateToken'])->name('rotate-token');
        Route::patch('/{device}/status', [RfidDeviceController::class, 'toggle'])->name('toggle');
    });
});
