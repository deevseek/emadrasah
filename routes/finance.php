<?php

declare(strict_types=1);

use App\Http\Controllers\Finance\FeeTypeController;
use App\Http\Controllers\Finance\FinanceDashboardController;
use App\Http\Controllers\Finance\FinanceResourceController;
use App\Http\Controllers\Finance\StudentInvoiceController;
use App\Http\Controllers\Finance\StudentPaymentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active'])->prefix('finance')->name('finance.')->group(function (): void {
    Route::get('dashboard-pembayaran', [FinanceDashboardController::class, 'payments'])->middleware('permission:student-payments.view')->name('dashboard.payments');
    Route::get('dashboard-keuangan', [FinanceDashboardController::class, 'finance'])->middleware('permission:finance-reports.view')->name('dashboard.finance');
    Route::get('dashboard-penggajian', [FinanceDashboardController::class, 'payroll'])->middleware('permission:payrolls.view')->name('dashboard.payroll');
    Route::resource('fee-types', FeeTypeController::class)->except('destroy')->middleware(['index'=>'permission:fee-types.view','show'=>'permission:fee-types.view','create'=>'permission:fee-types.manage','store'=>'permission:fee-types.manage','edit'=>'permission:fee-types.manage','update'=>'permission:fee-types.manage']);
    Route::patch('fee-types/{feeType}/toggle', [FeeTypeController::class, 'toggle'])->middleware('permission:fee-types.manage')->name('fee-types.toggle');
    Route::resource('student-invoices', StudentInvoiceController::class)->only(['index', 'create', 'store', 'show'])->middleware(['index'=>'permission:student-invoices.view','show'=>'permission:student-invoices.view','create'=>'permission:student-invoices.create','store'=>'permission:student-invoices.create']);
    Route::resource('student-payments', StudentPaymentController::class)->only(['index', 'create', 'store', 'show'])->middleware(['index'=>'permission:student-payments.view','show'=>'permission:student-payments.view','create'=>'permission:student-payments.create','store'=>'permission:student-payments.create']);
    Route::get('student-payments/{studentPayment}/receipt', [StudentPaymentController::class, 'receipt'])->middleware('permission:student-payments.print')->name('student-payments.receipt');
    Route::patch('student-payments/{studentPayment}/cancel', [StudentPaymentController::class, 'cancel'])->middleware('permission:student-payments.cancel')->name('student-payments.cancel');
    Route::get('billing-periods', [FinanceResourceController::class, 'billingPeriods'])->middleware('permission:billing-periods.view')->name('billing-periods.index');
    Route::post('billing-periods', [FinanceResourceController::class, 'storeBillingPeriod'])->middleware('permission:billing-periods.manage')->name('billing-periods.store');
    Route::get('student-discounts', [FinanceResourceController::class, 'discounts'])->middleware('permission:student-discounts.view')->name('student-discounts.index');
    Route::post('student-discounts', [FinanceResourceController::class, 'storeDiscount'])->middleware('permission:student-discounts.manage')->name('student-discounts.store');
    Route::patch('student-discounts/{studentDiscount}/approve', [FinanceResourceController::class, 'approveDiscount'])->middleware('permission:student-discounts.approve')->name('student-discounts.approve');
    Route::patch('student-discounts/{studentDiscount}/reject', [FinanceResourceController::class, 'rejectDiscount'])->middleware('permission:student-discounts.approve')->name('student-discounts.reject');
    Route::get('chart-accounts', [FinanceResourceController::class, 'chartAccounts'])->middleware('permission:finance-accounts.view')->name('chart-accounts.index');
    Route::post('chart-accounts', [FinanceResourceController::class, 'storeChartAccount'])->middleware('permission:finance-accounts.manage')->name('chart-accounts.store');
    Route::get('cash-accounts', [FinanceResourceController::class, 'cashAccounts'])->middleware('permission:cash-accounts.view')->name('cash-accounts.index');
    Route::post('cash-accounts', [FinanceResourceController::class, 'storeCashAccount'])->middleware('permission:cash-accounts.manage')->name('cash-accounts.store');
    Route::get('transactions', [FinanceResourceController::class, 'transactions'])->middleware('permission:finance-transactions.view')->name('transactions.index');
    Route::post('transactions', [FinanceResourceController::class, 'storeTransaction'])->middleware('permission:finance-transactions.create')->name('transactions.store');
    Route::patch('transactions/{financialTransaction}/cancel', [FinanceResourceController::class, 'cancelTransaction'])->middleware('permission:finance-transactions.cancel')->name('transactions.cancel');
    Route::get('reports', [FinanceResourceController::class, 'reports'])->middleware('permission:finance-reports.view')->name('reports.index');
    Route::get('salary-components', [FinanceResourceController::class, 'salaryComponents'])->middleware('permission:salary-components.view')->name('salary-components.index');
    Route::post('salary-components', [FinanceResourceController::class, 'storeSalaryComponent'])->middleware('permission:salary-components.manage')->name('salary-components.store');
    Route::get('employee-salaries', [FinanceResourceController::class, 'employeeSalaries'])->middleware('permission:employee-salaries.view')->name('employee-salaries.index');
    Route::post('employee-salaries', [FinanceResourceController::class, 'storeEmployeeSalary'])->middleware('permission:employee-salaries.manage')->name('employee-salaries.store');
    Route::get('payroll-periods', [FinanceResourceController::class, 'payrollPeriods'])->middleware('permission:payroll-periods.view')->name('payroll-periods.index');
    Route::post('payroll-periods', [FinanceResourceController::class, 'storePayrollPeriod'])->middleware('permission:payroll-periods.create')->name('payroll-periods.store');
    Route::post('payroll-periods/{payrollPeriod}/calculate', [FinanceResourceController::class, 'calculatePayroll'])->middleware('permission:payrolls.calculate')->name('payroll-periods.calculate');
    Route::post('payroll-periods/{payrollPeriod}/review', [FinanceResourceController::class, 'reviewPayroll'])->middleware('permission:payrolls.review')->name('payroll-periods.review');
    Route::post('payroll-periods/{payrollPeriod}/approve', [FinanceResourceController::class, 'approvePayroll'])->middleware('permission:payrolls.approve')->name('payroll-periods.approve');
    Route::post('payroll-periods/{payrollPeriod}/pay', [FinanceResourceController::class, 'payPayroll'])->middleware('permission:payrolls.mark-paid')->name('payroll-periods.pay');
    Route::post('payroll-periods/{payrollPeriod}/close', [FinanceResourceController::class, 'closePayroll'])->middleware('permission:payrolls.close')->name('payroll-periods.close');
    Route::post('payroll-periods/{payrollPeriod}/reopen', [FinanceResourceController::class, 'reopenPayroll'])->middleware('permission:payrolls.reopen')->name('payroll-periods.reopen');
    Route::get('payrolls', [FinanceResourceController::class, 'payrolls'])->middleware('permission:payrolls.view')->name('payrolls.index');
    Route::get('payrolls/{employeePayroll}/slip', [FinanceResourceController::class, 'slip'])->middleware('permission:payrolls.view|payrolls.view-own')->name('payrolls.slip');
});
