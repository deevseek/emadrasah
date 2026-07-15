<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Foundation\DashboardController;
use App\Http\Controllers\Foundation\SchoolProfileController;
use App\Http\Controllers\Foundation\SettingController;
use App\Http\Controllers\Foundation\UserManagementController;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::redirect('/', '/dashboard');
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    Route::get('/forgot-password', fn () => view('auth.forgot-password'))->name('password.request');
    Route::post('/forgot-password', fn (Request $request) => back()->with('status', Password::sendResetLink($request->validate(['email'=>['required','email']]))))->name('password.email');
    Route::get('/reset-password/{token}', fn (string $token) => view('auth.reset-password', ['token'=>$token]))->name('password.reset');
    Route::post('/reset-password', function (Request $request) {
        $data = $request->validate(['token'=>['required'], 'email'=>['required','email'], 'password'=>['required','confirmed','min:8']]);
        return back()->with('status', Password::reset($data, function ($user, $password) { $user->forceFill(['password' => $password])->save(); }));
    })->name('password.store');
});
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth')->name('logout');
Route::middleware(['auth','active'])->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->middleware('permission:dashboard.view')->name('dashboard');
    Route::view('/password/change', 'auth.change-password')->name('password.change');
    Route::put('/password/change', function(Request $request){ $data=$request->validate(['password'=>['required','confirmed','min:8']]); $request->user()->update(['password'=>$data['password']]); return back()->with('status','Password diperbarui.'); })->name('password.change.update');
    Route::get('/school-profile', [SchoolProfileController::class,'edit'])->middleware('permission:school-profile.view')->name('school-profile.edit');
    Route::put('/school-profile', [SchoolProfileController::class,'update'])->name('school-profile.update');
    Route::get('/settings', [SettingController::class,'index'])->middleware('permission:settings.view')->name('settings.index');
    Route::put('/settings/{setting}', [SettingController::class,'update'])->name('settings.update');
    Route::resource('users', UserManagementController::class)->except('destroy')->middleware('permission:users.view');
    Route::patch('/users/{user}/toggle', [UserManagementController::class,'toggle'])->middleware('permission:users.deactivate')->name('users.toggle');
});
