<?php

declare(strict_types=1);

use App\Http\Controllers\Finance\FeeTypeController;
use App\Http\Controllers\Finance\FinanceDashboardController;
use App\Http\Controllers\Finance\StudentInvoiceController;
use App\Http\Controllers\Finance\StudentPaymentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('finance')->name('finance.')->group(function (): void {
    Route::get('dashboard-pembayaran', [FinanceDashboardController::class, 'payments'])->name('dashboard.payments');
    Route::get('dashboard-keuangan', [FinanceDashboardController::class, 'finance'])->name('dashboard.finance');
    Route::get('dashboard-penggajian', [FinanceDashboardController::class, 'payroll'])->name('dashboard.payroll');
    Route::resource('fee-types', FeeTypeController::class)->except('destroy');
    Route::patch('fee-types/{feeType}/toggle', [FeeTypeController::class, 'toggle'])->name('fee-types.toggle');
    Route::resource('student-invoices', StudentInvoiceController::class)->only(['index', 'create', 'store', 'show']);
    Route::resource('student-payments', StudentPaymentController::class)->only(['index', 'create', 'store', 'show']);
    Route::get('student-payments/{studentPayment}/receipt', [StudentPaymentController::class, 'receipt'])->name('student-payments.receipt');
    Route::patch('student-payments/{studentPayment}/cancel', [StudentPaymentController::class, 'cancel'])->name('student-payments.cancel');
});
