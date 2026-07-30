<?php

declare(strict_types=1);

use App\Http\Controllers\Foundation\AcademicPeriodActivationController;
use App\Http\Controllers\Foundation\AcademicPeriodController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'force-password-change'])->group(function (): void {
    Route::get('/academic-periods', [AcademicPeriodController::class, 'index'])->middleware('permission:academic-periods.view')->name('academic-periods.index');
    Route::get('/academic-periods/create', [AcademicPeriodController::class, 'create'])->middleware('permission:academic-periods.create')->name('academic-periods.create');
    Route::post('/academic-periods', [AcademicPeriodController::class, 'store'])->middleware('permission:academic-periods.create')->name('academic-periods.store');
    Route::get('/academic-periods/{academicYear}/edit', [AcademicPeriodController::class, 'edit'])->middleware('permission:academic-periods.update')->name('academic-periods.edit');
    Route::put('/academic-periods/{academicYear}', [AcademicPeriodController::class, 'update'])->middleware('permission:academic-periods.update')->name('academic-periods.update');
    Route::patch('/academic-periods/semesters/{semester}/activate', [AcademicPeriodActivationController::class, 'update'])->middleware('permission:academic-periods.activate')->name('academic-periods.activate');
    Route::delete('/academic-periods/{academicYear}', [AcademicPeriodController::class, 'destroy'])->middleware('permission:academic-periods.delete')->name('academic-periods.destroy');
});
