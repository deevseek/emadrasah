<?php

declare(strict_types=1);

use App\Http\Controllers\Finance\FeeTypeController;
use App\Http\Controllers\Finance\FinanceDashboardController;
use App\Http\Controllers\Finance\FinanceResourceController;
use App\Http\Controllers\Finance\StudentInvoiceController;
use App\Http\Controllers\Finance\StudentPaymentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active'])->prefix('student-finance')->name('student-finance.')->group(function (): void {
    Route::get('/', [FinanceDashboardController::class, 'student'])->middleware('permission:student-finance-dashboard.view')->name('dashboard');
    Route::resource('fee-types', FeeTypeController::class)->except(['destroy'])->middleware(['index'=>'permission:student-fee-types.view','show'=>'permission:student-fee-types.view','create'=>'permission:student-fee-types.manage','store'=>'permission:student-fee-types.manage','edit'=>'permission:student-fee-types.manage','update'=>'permission:student-fee-types.manage']);
    Route::resource('bills', StudentInvoiceController::class)->only(['index','create','store','show','edit','update'])->middleware(['index'=>'permission:student-bills.view|student-arrears.view-own-class','show'=>'permission:student-bills.view|student-arrears.view-own-class','create'=>'permission:student-bills.create','store'=>'permission:student-bills.create','edit'=>'permission:student-bills.update-draft','update'=>'permission:student-bills.update-draft']);
    Route::get('bills/{studentInvoice}/print', [StudentInvoiceController::class, 'print'])->middleware('permission:student-bills.print')->name('bills.print');
    Route::patch('bills/{studentInvoice}/cancel', [StudentInvoiceController::class, 'cancel'])->middleware('permission:student-bills.cancel')->name('bills.cancel');
    Route::get('generate-spp', [StudentInvoiceController::class, 'bulk'])->middleware('permission:student-bills.generate-bulk')->name('generate-spp.create');
    Route::post('generate-spp/preview', [StudentInvoiceController::class, 'preview'])->middleware('permission:student-bills.generate-bulk')->name('generate-spp.preview');
    Route::post('generate-spp', [StudentInvoiceController::class, 'generate'])->middleware('permission:student-bills.generate-bulk')->name('generate-spp.store');
    Route::resource('payments', StudentPaymentController::class)->only(['index','create','store','show'])->middleware(['index'=>'permission:student-payments.view','show'=>'permission:student-payments.view','create'=>'permission:student-payments.create','store'=>'permission:student-payments.create']);
    Route::get('payments/{studentPayment}/receipt', [StudentPaymentController::class, 'receipt'])->middleware('permission:student-payments.print-receipt')->name('payments.receipt');
    Route::patch('payments/{studentPayment}/cancel', [StudentPaymentController::class, 'cancel'])->middleware('permission:student-payments.cancel')->name('payments.cancel');
    Route::get('discounts', [FinanceResourceController::class, 'discounts'])->middleware('permission:student-discounts.view')->name('discounts.index');
    Route::post('discounts', [FinanceResourceController::class, 'storeDiscount'])->middleware('permission:student-discounts.create')->name('discounts.store');
    Route::patch('discounts/{studentDiscount}/approve', [FinanceResourceController::class, 'approveDiscount'])->middleware('permission:student-discounts.approve')->name('discounts.approve');
    Route::patch('discounts/{studentDiscount}/cancel', [FinanceResourceController::class, 'cancelDiscount'])->middleware('permission:student-discounts.cancel')->name('discounts.cancel');
    Route::get('arrears', [FinanceResourceController::class, 'arrears'])->middleware('permission:student-arrears.view|student-arrears.view-own-class')->name('arrears.index');
    Route::get('arrears/export', [FinanceResourceController::class, 'exportArrears'])->middleware('permission:student-arrears.export')->name('arrears.export');
    Route::get('arrears/print', [FinanceResourceController::class, 'printArrears'])->middleware('permission:student-arrears.print|student-arrears.view-own-class')->name('arrears.print');
    Route::get('reports', [FinanceResourceController::class, 'studentReports'])->middleware('permission:student-finance-reports.view')->name('reports.index');
    Route::get('reports/export', [FinanceResourceController::class, 'exportStudentReports'])->middleware('permission:student-finance-reports.export')->name('reports.export');
    Route::get('reports/print', [FinanceResourceController::class, 'printStudentReports'])->middleware('permission:student-finance-reports.print')->name('reports.print');
});

Route::middleware(['auth', 'active'])->prefix('finance')->name('finance.')->group(function (): void {
    Route::get('/', [FinanceDashboardController::class, 'finance'])->middleware('permission:finance-dashboard.view')->name('dashboard');
    Route::get('/payments-dashboard', [FinanceDashboardController::class, 'payments'])->middleware('permission:student-payments.view')->name('payments.dashboard');
    Route::resource('fee-types', FeeTypeController::class)->except(['destroy'])->middleware(['index'=>'permission:fee-types.view','show'=>'permission:fee-types.view','create'=>'permission:fee-types.manage','store'=>'permission:fee-types.manage','edit'=>'permission:fee-types.manage','update'=>'permission:fee-types.manage']);
    Route::resource('student-invoices', StudentInvoiceController::class)->only(['index', 'create', 'store', 'show'])->middleware(['index'=>'permission:student-invoices.view','show'=>'permission:student-invoices.view','create'=>'permission:student-invoices.create','store'=>'permission:student-invoices.create']);
    Route::resource('student-payments', StudentPaymentController::class)->only(['index', 'create', 'store', 'show'])->middleware(['index'=>'permission:student-payments.view','show'=>'permission:student-payments.view','create'=>'permission:student-payments.create','store'=>'permission:student-payments.create']);
    Route::get('student-payments/{studentPayment}/receipt', [StudentPaymentController::class, 'receipt'])->middleware('permission:student-payments.print')->name('student-payments.receipt');
    Route::patch('student-payments/{studentPayment}/cancel', [StudentPaymentController::class, 'cancel'])->middleware('permission:student-payments.cancel')->name('student-payments.cancel');
    Route::get('reports', [FinanceResourceController::class, 'reports'])->middleware('permission:finance-reports.view')->name('reports.index');
    Route::post('billing-periods', [FinanceResourceController::class, 'storeBillingPeriod'])->middleware('permission:billing-periods.manage')->name('billing-periods.store');
});
