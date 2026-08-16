<?php
declare(strict_types=1); use Illuminate\Support\Facades\Route;
Route::prefix('finance')->name('finance.')->middleware(['auth','active','force-password-change'])->group(function():void{Route::view('/','finance.dashboard')->middleware('permission:finance.dashboard.view')->name('dashboard');});
