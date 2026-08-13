<?php

declare(strict_types=1);

use App\Http\Controllers\Settings\ApplicationSettingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'force-password-change'])->prefix('pengaturan')->group(function (): void {
    Route::get('/aplikasi', [ApplicationSettingController::class, 'edit'])->middleware('permission:application-settings.view')->name('application-settings.edit');
    Route::put('/aplikasi', [ApplicationSettingController::class, 'update'])->middleware('permission:application-settings.update')->name('application-settings.update');
});
