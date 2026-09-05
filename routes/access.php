<?php

declare(strict_types=1);
use App\Http\Controllers\Access\RoleController;use App\Http\Controllers\Access\UserController;use App\Http\Controllers\Access\UserPasswordController;use App\Http\Controllers\Access\UserStatusController;use Illuminate\Support\Facades\Route;
Route::middleware(['auth','active','force-password-change'])->group(function():void{
 Route::get('/users',[UserController::class,'index'])->middleware('permission:users.view')->name('users.index');
 Route::get('/users/create',[UserController::class,'create'])->middleware('permission:users.create')->name('users.create');
 Route::post('/users',[UserController::class,'store'])->middleware('permission:users.create')->name('users.store');
 Route::get('/users/{user}',[UserController::class,'show'])->middleware('permission:users.view')->name('users.show');
 Route::get('/users/{user}/edit',[UserController::class,'edit'])->middleware('permission:users.update')->name('users.edit');
 Route::put('/users/{user}',[UserController::class,'update'])->middleware('permission:users.update')->name('users.update');
 Route::delete('/users/{user}',[UserController::class,'destroy'])->middleware('permission:users.delete')->name('users.destroy');
 Route::patch('/users/{user}/activate',[UserStatusController::class,'activate'])->middleware('permission:users.activate')->name('users.activate');
 Route::patch('/users/{user}/deactivate',[UserStatusController::class,'deactivate'])->middleware('permission:users.activate')->name('users.deactivate');
 Route::get('/users/{user}/reset-password',[UserPasswordController::class,'edit'])->middleware('permission:users.reset-password')->name('users.reset-password.edit');
 Route::patch('/users/{user}/reset-password',[UserPasswordController::class,'update'])->middleware('permission:users.reset-password')->name('users.reset-password.update');
 Route::get('/roles',[RoleController::class,'index'])->middleware('permission:roles.view')->name('roles.index');
 Route::get('/roles/create',[RoleController::class,'create'])->middleware('permission:roles.create')->name('roles.create');
 Route::post('/roles',[RoleController::class,'store'])->middleware(['permission:roles.create','permission:roles.manage-permissions'])->name('roles.store');
 Route::get('/roles/{role}',[RoleController::class,'show'])->middleware('permission:roles.view')->name('roles.show');
 Route::get('/roles/{role}/edit',[RoleController::class,'edit'])->middleware('permission:roles.update')->name('roles.edit');
 Route::put('/roles/{role}',[RoleController::class,'update'])->middleware(['permission:roles.update','permission:roles.manage-permissions'])->name('roles.update');
 Route::delete('/roles/{role}',[RoleController::class,'destroy'])->middleware('permission:roles.delete')->name('roles.destroy');
});
