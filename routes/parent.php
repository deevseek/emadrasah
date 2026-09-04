<?php
declare(strict_types=1); use App\Http\Controllers\ParentPortal\{ConsultationController,DashboardController,PaymentReceiptController}; use Illuminate\Support\Facades\Route;
Route::prefix('parent')->name('parent.')->middleware(['auth','active','force-password-change'])->group(function():void{
 Route::get('/',DashboardController::class)->middleware('permission:parent.dashboard.view')->name('dashboard');
 Route::get('/children',DashboardController::class)->middleware('permission:parent.children.view')->name('children');
 Route::get('/schedule',DashboardController::class)->middleware('permission:parent.schedule.view')->name('schedule');
 Route::get('/attendance',DashboardController::class)->middleware('permission:parent.attendance.view')->name('attendance');
 Route::get('/finance',DashboardController::class)->middleware('permission:parent.finance.view')->name('finance');
 Route::get('/consultation',[ConsultationController::class,'show'])->middleware('permission:parent.consultation.use')->name('consultation.show');
 Route::post('/consultation/{student}',[ConsultationController::class,'store'])->middleware('permission:parent.consultation.use')->name('consultation.store');
 Route::get('/consultation/messages/{consultation}',[ConsultationController::class,'messages'])->middleware('permission:parent.consultation.use')->name('consultation.messages');
 Route::get('/payments/{payment}/receipt/pdf',PaymentReceiptController::class)->middleware('permission:parent.finance.view')->name('payments.receipt.pdf');
 Route::get('/profile',DashboardController::class)->name('profile');
});
