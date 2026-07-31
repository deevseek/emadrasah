<?php

declare(strict_types=1);

use App\Http\Controllers\Subjects\{SubjectController, SubjectExportController, SubjectGradeLoadController};
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'force-password-change'])->prefix('subjects')->name('subjects.')->group(function (): void {
    Route::get('/export', SubjectExportController::class)->middleware('permission:subjects.export')->name('export');
    Route::get('/', [SubjectController::class, 'index'])->middleware('permission:subjects.view')->name('index');
    Route::get('/create', [SubjectController::class, 'create'])->middleware('permission:subjects.create')->name('create');
    Route::post('/', [SubjectController::class, 'store'])->middleware('permission:subjects.create')->name('store');
    Route::redirect('/loads', '/subjects/grade-loads')->name('loads');
    Route::get('/grade-loads', [SubjectGradeLoadController::class, 'edit'])->middleware('permission:subjects.view-loads')->name('loads.edit');
    Route::put('/grade-loads', [SubjectGradeLoadController::class, 'update'])->middleware('permission:subjects.manage-loads')->name('loads.update');
    Route::get('/{subject}/edit', [SubjectController::class, 'edit'])->middleware('permission:subjects.update')->name('edit');
    Route::put('/{subject}', [SubjectController::class, 'update'])->middleware('permission:subjects.update')->name('update');
});
