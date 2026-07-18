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
    Route::patch('fee-types/{feeType}/toggle', [FeeTypeController::class, 'toggle'])->middleware('permission:fee-types.manage')->name('fee-types.toggle');
    Route::resource('student-invoices', StudentInvoiceController::class)->only(['index', 'create', 'store', 'show'])->middleware(['index'=>'permission:student-invoices.view','show'=>'permission:student-invoices.view','create'=>'permission:student-invoices.create','store'=>'permission:student-invoices.create']);
    Route::resource('student-payments', StudentPaymentController::class)->only(['index', 'create', 'store', 'show'])->middleware(['index'=>'permission:student-payments.view','show'=>'permission:student-payments.view','create'=>'permission:student-payments.create','store'=>'permission:student-payments.create']);
    Route::get('student-payments/{studentPayment}/receipt', [StudentPaymentController::class, 'receipt'])->middleware('permission:student-payments.print')->name('student-payments.receipt');
    Route::patch('student-payments/{studentPayment}/cancel', [StudentPaymentController::class, 'cancel'])->middleware('permission:student-payments.cancel')->name('student-payments.cancel');
    Route::get('reports', [FinanceResourceController::class, 'reports'])->middleware('permission:finance-reports.view')->name('reports.index');
    Route::post('billing-periods', [FinanceResourceController::class, 'storeBillingPeriod'])->middleware('permission:billing-periods.manage')->name('billing-periods.store');
});

Route::middleware(['auth', 'active'])->prefix('operational-finance')->name('operational-finance.')->group(function (): void {
    Route::get('/', [\App\Http\Controllers\OperationalFinance\OperationalFinanceController::class, 'dashboard'])->middleware('permission:operational-finance-dashboard.view')->name('dashboard');
    Route::get('cash-accounts', [\App\Http\Controllers\OperationalFinance\OperationalFinanceController::class, 'cashAccounts'])->middleware('permission:cash-accounts.view')->name('cash-accounts.index');
    Route::get('cash-accounts/create', [\App\Http\Controllers\OperationalFinance\OperationalFinanceController::class, 'createCashAccount'])->middleware('permission:cash-accounts.manage')->name('cash-accounts.create');
    Route::post('cash-accounts', [\App\Http\Controllers\OperationalFinance\OperationalFinanceController::class, 'storeCashAccount'])->middleware('permission:cash-accounts.manage')->name('cash-accounts.store');
    Route::get('categories', [\App\Http\Controllers\OperationalFinance\OperationalFinanceController::class, 'categories'])->middleware('permission:finance-categories.view')->name('categories.index');
    Route::get('categories/create', [\App\Http\Controllers\OperationalFinance\OperationalFinanceController::class, 'createCategory'])->middleware('permission:finance-categories.manage')->name('categories.create');
    Route::post('categories', [\App\Http\Controllers\OperationalFinance\OperationalFinanceController::class, 'storeCategory'])->middleware('permission:finance-categories.manage')->name('categories.store');
    Route::get('incomes', [\App\Http\Controllers\OperationalFinance\OperationalFinanceController::class, 'incomes'])->middleware('permission:operational-incomes.view')->name('incomes.index');
    Route::get('incomes/create', [\App\Http\Controllers\OperationalFinance\OperationalFinanceController::class, 'createIncome'])->middleware('permission:operational-incomes.create')->name('incomes.create');
    Route::post('incomes', [\App\Http\Controllers\OperationalFinance\OperationalFinanceController::class, 'storeIncome'])->middleware('permission:operational-incomes.create')->name('incomes.store');
    Route::get('expenses', [\App\Http\Controllers\OperationalFinance\OperationalFinanceController::class, 'expenses'])->middleware('permission:operational-expenses.view')->name('expenses.index');
    Route::get('expenses/create', [\App\Http\Controllers\OperationalFinance\OperationalFinanceController::class, 'createExpense'])->middleware('permission:operational-expenses.create')->name('expenses.create');
    Route::post('expenses', [\App\Http\Controllers\OperationalFinance\OperationalFinanceController::class, 'storeExpense'])->middleware('permission:operational-expenses.create')->name('expenses.store');
    Route::get('transactions/{transaction}', [\App\Http\Controllers\OperationalFinance\OperationalFinanceController::class, 'show'])->middleware('permission:operational-incomes.view|operational-expenses.view')->name('transactions.show');
    Route::patch('transactions/{transaction}/submit', [\App\Http\Controllers\OperationalFinance\OperationalFinanceController::class, 'submit'])->middleware('permission:operational-incomes.submit|operational-expenses.submit')->name('transactions.submit');
    Route::patch('transactions/{transaction}/approve', [\App\Http\Controllers\OperationalFinance\OperationalFinanceController::class, 'approve'])->middleware('permission:finance-approvals.approve')->name('transactions.approve');
    Route::patch('transactions/{transaction}/reject', [\App\Http\Controllers\OperationalFinance\OperationalFinanceController::class, 'reject'])->middleware('permission:finance-approvals.reject')->name('transactions.reject');
    Route::patch('transactions/{transaction}/cancel', [\App\Http\Controllers\OperationalFinance\OperationalFinanceController::class, 'cancel'])->middleware('permission:operational-incomes.cancel|operational-expenses.cancel')->name('transactions.cancel');
    Route::get('transactions/{transaction}/print', [\App\Http\Controllers\OperationalFinance\OperationalFinanceController::class, 'print'])->middleware('permission:operational-incomes.print|operational-expenses.print')->name('transactions.print');
    Route::get('transfers', [\App\Http\Controllers\OperationalFinance\OperationalFinanceController::class, 'transfers'])->middleware('permission:cash-transfers.view')->name('transfers.index');
    Route::post('transfers', [\App\Http\Controllers\OperationalFinance\OperationalFinanceController::class, 'storeTransfer'])->middleware('permission:cash-transfers.create')->name('transfers.store');
    Route::get('approvals', [\App\Http\Controllers\OperationalFinance\OperationalFinanceController::class, 'approvals'])->middleware('permission:finance-approvals.view')->name('approvals.index');
    Route::get('budgets', [\App\Http\Controllers\OperationalFinance\OperationalFinanceController::class, 'budgets'])->middleware('permission:budgets.view')->name('budgets.index');
    Route::post('budgets', [\App\Http\Controllers\OperationalFinance\OperationalFinanceController::class, 'storeBudget'])->middleware('permission:budgets.manage')->name('budgets.store');
    Route::get('budgets/realization', [\App\Http\Controllers\OperationalFinance\OperationalFinanceController::class, 'realization'])->middleware('permission:budgets.view')->name('budgets.realization');
    Route::get('cash-book', [\App\Http\Controllers\OperationalFinance\OperationalFinanceController::class, 'cashBook'])->middleware('permission:cash-books.view')->name('cash-book.index');
    Route::get('cash-book/export', [\App\Http\Controllers\OperationalFinance\OperationalFinanceController::class, 'export'])->middleware('permission:cash-books.export')->name('cash-book.export');
    Route::get('closings', [\App\Http\Controllers\OperationalFinance\OperationalFinanceController::class, 'closings'])->middleware('permission:cash-closings.view')->name('closings.index');
    Route::post('closings', [\App\Http\Controllers\OperationalFinance\OperationalFinanceController::class, 'storeClosing'])->middleware('permission:cash-closings.create')->name('closings.store');
    Route::get('reconciliations', [\App\Http\Controllers\OperationalFinance\OperationalFinanceController::class, 'reconciliations'])->middleware('permission:cash-reconciliations.view')->name('reconciliations.index');
    Route::post('reconciliations', [\App\Http\Controllers\OperationalFinance\OperationalFinanceController::class, 'storeReconciliation'])->middleware('permission:cash-reconciliations.create')->name('reconciliations.store');
    Route::get('reports', [\App\Http\Controllers\OperationalFinance\OperationalFinanceController::class, 'reports'])->middleware('permission:operational-finance-reports.view')->name('reports.index');
    Route::get('reports/export', [\App\Http\Controllers\OperationalFinance\OperationalFinanceController::class, 'export'])->middleware('permission:operational-finance-reports.export')->name('reports.export');
});
