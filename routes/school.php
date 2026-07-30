<?php

declare(strict_types=1);

use App\Http\Controllers\Foundation\SchoolProfileController;
use App\Http\Controllers\Foundation\SchoolProfileLeaderController;
use App\Http\Controllers\Foundation\SchoolProfileLogoController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'force-password-change'])->group(function (): void {
    Route::get('/school-profile', [SchoolProfileController::class, 'show'])->middleware('permission:school-profile.view')->name('school-profile.show');
    Route::put('/school-profile', [SchoolProfileController::class, 'update'])->middleware('permission:school-profile.update')->name('school-profile.update');
    Route::post('/school-profile/logo', [SchoolProfileLogoController::class, 'update'])->middleware('permission:school-profile.update-logo')->name('school-profile.logo.update');
    Route::delete('/school-profile/logo', [SchoolProfileLogoController::class, 'destroy'])->middleware('permission:school-profile.update-logo')->name('school-profile.logo.destroy');
    Route::put('/school-profile/leader', [SchoolProfileLeaderController::class, 'update'])->middleware('permission:school-profile.update-leader')->name('school-profile.leader.update');
});
