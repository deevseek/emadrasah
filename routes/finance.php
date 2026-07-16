<?php

declare(strict_types=1);

use App\Http\Controllers\Finance\BillingPeriodController;
use App\Http\Controllers\Finance\CashAccountController;
use App\Http\Controllers\Finance\ChartAccountController;
use App\Http\Controllers\Finance\EmployeePayrollController;
use App\Http\Controllers\Finance\EmployeeSalaryComponentController;
use App\Http\Controllers\Finance\FeeTypeController;
use App\Http\Controllers\Finance\FinanceDashboardController;
use App\Http\Controllers\Finance\FinanceReportController;
use App\Http\Controllers\Finance\FinancialTransactionController;
use App\Http\Controllers\Finance\PayrollPeriodController;
use App\Http\Controllers\Finance\SalaryComponentController;
use App\Http\Controllers\Finance\StudentDiscountController;
use App\Http\Controllers\Finance\StudentInvoiceController;
use App\Http\Controllers\Finance\StudentPaymentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active'])
    ->prefix('finance')
    ->name('finance.')
    ->group(function (): void {
        Route::get('dashboard-pembayaran', [FinanceDashboardController::class, 'payments'])
            ->middleware('permission:student-payments.view')
            ->name('dashboard.payments');
        Route::get('dashboard-keuangan', [FinanceDashboardController::class, 'finance'])
            ->middleware('permission:finance-reports.view')
            ->name('dashboard.finance');
        Route::get('dashboard-penggajian', [FinanceDashboardController::class, 'payroll'])
            ->middleware('permission:payrolls.view')
            ->name('dashboard.payroll');

        Route::get('fee-types', [FeeTypeController::class, 'index'])
            ->middleware('permission:fee-types.view')
            ->name('fee-types.index');
        Route::get('fee-types/create', [FeeTypeController::class, 'create'])
            ->middleware('permission:fee-types.manage')
            ->name('fee-types.create');
        Route::post('fee-types', [FeeTypeController::class, 'store'])
            ->middleware('permission:fee-types.manage')
            ->name('fee-types.store');
        Route::get('fee-types/{feeType}', [FeeTypeController::class, 'show'])
            ->middleware('permission:fee-types.view')
            ->name('fee-types.show');
        Route::get('fee-types/{feeType}/edit', [FeeTypeController::class, 'edit'])
            ->middleware('permission:fee-types.manage')
            ->name('fee-types.edit');
        Route::put('fee-types/{feeType}', [FeeTypeController::class, 'update'])
            ->middleware('permission:fee-types.manage')
            ->name('fee-types.update');
        Route::patch('fee-types/{feeType}/toggle', [FeeTypeController::class, 'toggle'])
            ->middleware('permission:fee-types.manage')
            ->name('fee-types.toggle');
        Route::delete('fee-types/{feeType}', [FeeTypeController::class, 'destroy'])
            ->middleware('permission:fee-types.manage')
            ->name('fee-types.destroy');

        Route::get('student-invoices', [StudentInvoiceController::class, 'index'])
            ->middleware('permission:student-invoices.view')
            ->name('student-invoices.index');
        Route::get('student-invoices/create', [StudentInvoiceController::class, 'create'])
            ->middleware('permission:student-invoices.create')
            ->name('student-invoices.create');
        Route::post('student-invoices', [StudentInvoiceController::class, 'store'])
            ->middleware('permission:student-invoices.create')
            ->name('student-invoices.store');
        Route::get('student-invoices/{studentInvoice}', [StudentInvoiceController::class, 'show'])
            ->middleware('permission:student-invoices.view')
            ->name('student-invoices.show');
        Route::get('student-invoices/{studentInvoice}/edit', [StudentInvoiceController::class, 'edit'])
            ->middleware('permission:student-invoices.update')
            ->name('student-invoices.edit');
        Route::put('student-invoices/{studentInvoice}', [StudentInvoiceController::class, 'update'])
            ->middleware('permission:student-invoices.update')
            ->name('student-invoices.update');
        Route::patch('student-invoices/{studentInvoice}/cancel', [StudentInvoiceController::class, 'cancel'])
            ->middleware('permission:student-invoices.cancel')
            ->name('student-invoices.cancel');

        Route::get('student-payments', [StudentPaymentController::class, 'index'])
            ->middleware('permission:student-payments.view')
            ->name('student-payments.index');
        Route::get('student-payments/create', [StudentPaymentController::class, 'create'])
            ->middleware('permission:student-payments.create')
            ->name('student-payments.create');
        Route::post('student-payments', [StudentPaymentController::class, 'store'])
            ->middleware('permission:student-payments.create')
            ->name('student-payments.store');
        Route::get('student-payments/{studentPayment}', [StudentPaymentController::class, 'show'])
            ->middleware('permission:student-payments.view')
            ->name('student-payments.show');
        Route::get('student-payments/{studentPayment}/receipt', [StudentPaymentController::class, 'receipt'])
            ->middleware('permission:student-payments.print')
            ->name('student-payments.receipt');
        Route::patch('student-payments/{studentPayment}/cancel', [StudentPaymentController::class, 'cancel'])
            ->middleware('permission:student-payments.cancel')
            ->name('student-payments.cancel');

        Route::get('billing-periods', [BillingPeriodController::class, 'index'])
            ->middleware('permission:billing-periods.view')
            ->name('billing-periods.index');
        Route::get('billing-periods/create', [BillingPeriodController::class, 'create'])
            ->middleware('permission:billing-periods.manage')
            ->name('billing-periods.create');
        Route::post('billing-periods', [BillingPeriodController::class, 'store'])
            ->middleware('permission:billing-periods.manage')
            ->name('billing-periods.store');
        Route::get('billing-periods/{billingPeriod}', [BillingPeriodController::class, 'show'])
            ->middleware('permission:billing-periods.view')
            ->name('billing-periods.show');
        Route::get('billing-periods/{billingPeriod}/edit', [BillingPeriodController::class, 'edit'])
            ->middleware('permission:billing-periods.manage')
            ->name('billing-periods.edit');
        Route::put('billing-periods/{billingPeriod}', [BillingPeriodController::class, 'update'])
            ->middleware('permission:billing-periods.manage')
            ->name('billing-periods.update');
        Route::patch('billing-periods/{billingPeriod}/toggle', [BillingPeriodController::class, 'toggle'])
            ->middleware('permission:billing-periods.manage')
            ->name('billing-periods.toggle');
        Route::delete('billing-periods/{billingPeriod}', [BillingPeriodController::class, 'destroy'])
            ->middleware('permission:billing-periods.manage')
            ->name('billing-periods.destroy');

        Route::get('student-discounts', [StudentDiscountController::class, 'index'])
            ->middleware('permission:student-discounts.view')
            ->name('student-discounts.index');
        Route::get('student-discounts/create', [StudentDiscountController::class, 'create'])
            ->middleware('permission:student-discounts.manage')
            ->name('student-discounts.create');
        Route::post('student-discounts', [StudentDiscountController::class, 'store'])
            ->middleware('permission:student-discounts.manage')
            ->name('student-discounts.store');
        Route::get('student-discounts/{studentDiscount}', [StudentDiscountController::class, 'show'])
            ->middleware('permission:student-discounts.view')
            ->name('student-discounts.show');
        Route::get('student-discounts/{studentDiscount}/edit', [StudentDiscountController::class, 'edit'])
            ->middleware('permission:student-discounts.manage')
            ->name('student-discounts.edit');
        Route::put('student-discounts/{studentDiscount}', [StudentDiscountController::class, 'update'])
            ->middleware('permission:student-discounts.manage')
            ->name('student-discounts.update');
        Route::patch('student-discounts/{studentDiscount}/approve', [StudentDiscountController::class, 'approve'])
            ->middleware('permission:student-discounts.approve')
            ->name('student-discounts.approve');
        Route::patch('student-discounts/{studentDiscount}/reject', [StudentDiscountController::class, 'reject'])
            ->middleware('permission:student-discounts.approve')
            ->name('student-discounts.reject');
        Route::delete('student-discounts/{studentDiscount}', [StudentDiscountController::class, 'destroy'])
            ->middleware('permission:student-discounts.manage')
            ->name('student-discounts.destroy');

        Route::get('chart-accounts', [ChartAccountController::class, 'index'])
            ->middleware('permission:finance-accounts.view')
            ->name('chart-accounts.index');
        Route::get('chart-accounts/create', [ChartAccountController::class, 'create'])
            ->middleware('permission:finance-accounts.manage')
            ->name('chart-accounts.create');
        Route::post('chart-accounts', [ChartAccountController::class, 'store'])
            ->middleware('permission:finance-accounts.manage')
            ->name('chart-accounts.store');
        Route::get('chart-accounts/{chartAccount}', [ChartAccountController::class, 'show'])
            ->middleware('permission:finance-accounts.view')
            ->name('chart-accounts.show');
        Route::get('chart-accounts/{chartAccount}/edit', [ChartAccountController::class, 'edit'])
            ->middleware('permission:finance-accounts.manage')
            ->name('chart-accounts.edit');
        Route::put('chart-accounts/{chartAccount}', [ChartAccountController::class, 'update'])
            ->middleware('permission:finance-accounts.manage')
            ->name('chart-accounts.update');
        Route::patch('chart-accounts/{chartAccount}/toggle', [ChartAccountController::class, 'toggle'])
            ->middleware('permission:finance-accounts.manage')
            ->name('chart-accounts.toggle');
        Route::delete('chart-accounts/{chartAccount}', [ChartAccountController::class, 'destroy'])
            ->middleware('permission:finance-accounts.manage')
            ->name('chart-accounts.destroy');

        Route::get('cash-accounts', [CashAccountController::class, 'index'])
            ->middleware('permission:finance-accounts.view')
            ->name('cash-accounts.index');
        Route::get('cash-accounts/create', [CashAccountController::class, 'create'])
            ->middleware('permission:finance-accounts.manage')
            ->name('cash-accounts.create');
        Route::post('cash-accounts', [CashAccountController::class, 'store'])
            ->middleware('permission:finance-accounts.manage')
            ->name('cash-accounts.store');
        Route::get('cash-accounts/{cashAccount}', [CashAccountController::class, 'show'])
            ->middleware('permission:finance-accounts.view')
            ->name('cash-accounts.show');
        Route::get('cash-accounts/{cashAccount}/edit', [CashAccountController::class, 'edit'])
            ->middleware('permission:finance-accounts.manage')
            ->name('cash-accounts.edit');
        Route::put('cash-accounts/{cashAccount}', [CashAccountController::class, 'update'])
            ->middleware('permission:finance-accounts.manage')
            ->name('cash-accounts.update');
        Route::patch('cash-accounts/{cashAccount}/toggle', [CashAccountController::class, 'toggle'])
            ->middleware('permission:finance-accounts.manage')
            ->name('cash-accounts.toggle');
        Route::delete('cash-accounts/{cashAccount}', [CashAccountController::class, 'destroy'])
            ->middleware('permission:finance-accounts.manage')
            ->name('cash-accounts.destroy');

        Route::get('transactions', [FinancialTransactionController::class, 'index'])
            ->middleware('permission:finance-transactions.view')
            ->name('transactions.index');
        Route::get('transactions/create', [FinancialTransactionController::class, 'create'])
            ->middleware('permission:finance-transactions.create')
            ->name('transactions.create');
        Route::post('transactions', [FinancialTransactionController::class, 'store'])
            ->middleware('permission:finance-transactions.create')
            ->name('transactions.store');
        Route::get('transactions/{transaction}', [FinancialTransactionController::class, 'show'])
            ->middleware('permission:finance-transactions.view')
            ->name('transactions.show');
        Route::patch('transactions/{transaction}/cancel', [FinancialTransactionController::class, 'cancel'])
            ->middleware('permission:finance-transactions.cancel')
            ->name('transactions.cancel');

        Route::get('reports', [FinanceReportController::class, 'index'])
            ->middleware('permission:finance-reports.view')
            ->name('reports.index');

        Route::get('salary-components', [SalaryComponentController::class, 'index'])
            ->middleware('permission:salary-components.view')
            ->name('salary-components.index');
        Route::get('salary-components/create', [SalaryComponentController::class, 'create'])
            ->middleware('permission:salary-components.manage')
            ->name('salary-components.create');
        Route::post('salary-components', [SalaryComponentController::class, 'store'])
            ->middleware('permission:salary-components.manage')
            ->name('salary-components.store');
        Route::get('salary-components/{salaryComponent}', [SalaryComponentController::class, 'show'])
            ->middleware('permission:salary-components.view')
            ->name('salary-components.show');
        Route::get('salary-components/{salaryComponent}/edit', [SalaryComponentController::class, 'edit'])
            ->middleware('permission:salary-components.manage')
            ->name('salary-components.edit');
        Route::put('salary-components/{salaryComponent}', [SalaryComponentController::class, 'update'])
            ->middleware('permission:salary-components.manage')
            ->name('salary-components.update');
        Route::patch('salary-components/{salaryComponent}/toggle', [SalaryComponentController::class, 'toggle'])
            ->middleware('permission:salary-components.manage')
            ->name('salary-components.toggle');
        Route::delete('salary-components/{salaryComponent}', [SalaryComponentController::class, 'destroy'])
            ->middleware('permission:salary-components.manage')
            ->name('salary-components.destroy');

        Route::get('employee-salaries', [EmployeeSalaryComponentController::class, 'index'])
            ->middleware('permission:employee-salaries.view')
            ->name('employee-salaries.index');
        Route::get('employee-salaries/create', [EmployeeSalaryComponentController::class, 'create'])
            ->middleware('permission:employee-salaries.manage')
            ->name('employee-salaries.create');
        Route::post('employee-salaries', [EmployeeSalaryComponentController::class, 'store'])
            ->middleware('permission:employee-salaries.manage')
            ->name('employee-salaries.store');
        Route::get('employee-salaries/{employeeSalary}', [EmployeeSalaryComponentController::class, 'show'])
            ->middleware('permission:employee-salaries.view')
            ->name('employee-salaries.show');
        Route::get('employee-salaries/{employeeSalary}/edit', [EmployeeSalaryComponentController::class, 'edit'])
            ->middleware('permission:employee-salaries.manage')
            ->name('employee-salaries.edit');
        Route::put('employee-salaries/{employeeSalary}', [EmployeeSalaryComponentController::class, 'update'])
            ->middleware('permission:employee-salaries.manage')
            ->name('employee-salaries.update');
        Route::patch('employee-salaries/{employeeSalary}/toggle', [EmployeeSalaryComponentController::class, 'toggle'])
            ->middleware('permission:employee-salaries.manage')
            ->name('employee-salaries.toggle');
        Route::delete('employee-salaries/{employeeSalary}', [EmployeeSalaryComponentController::class, 'destroy'])
            ->middleware('permission:employee-salaries.manage')
            ->name('employee-salaries.destroy');

        Route::get('payroll-periods', [PayrollPeriodController::class, 'index'])
            ->middleware('permission:payroll-periods.view')
            ->name('payroll-periods.index');
        Route::get('payroll-periods/create', [PayrollPeriodController::class, 'create'])
            ->middleware('permission:payroll-periods.create')
            ->name('payroll-periods.create');
        Route::post('payroll-periods', [PayrollPeriodController::class, 'store'])
            ->middleware('permission:payroll-periods.create')
            ->name('payroll-periods.store');
        Route::get('payroll-periods/{payrollPeriod}', [PayrollPeriodController::class, 'show'])
            ->middleware('permission:payroll-periods.view')
            ->name('payroll-periods.show');
        Route::get('payroll-periods/{payrollPeriod}/edit', [PayrollPeriodController::class, 'edit'])
            ->middleware('permission:payroll-periods.manage')
            ->name('payroll-periods.edit');
        Route::put('payroll-periods/{payrollPeriod}', [PayrollPeriodController::class, 'update'])
            ->middleware('permission:payroll-periods.manage')
            ->name('payroll-periods.update');
        Route::delete('payroll-periods/{payrollPeriod}', [PayrollPeriodController::class, 'destroy'])
            ->middleware('permission:payroll-periods.manage')
            ->name('payroll-periods.destroy');
        Route::post('payroll-periods/{payrollPeriod}/calculate', [PayrollPeriodController::class, 'calculate'])
            ->middleware('permission:payrolls.calculate')
            ->name('payroll-periods.calculate');
        Route::patch('payroll-periods/{payrollPeriod}/review', [PayrollPeriodController::class, 'review'])
            ->middleware('permission:payrolls.review')
            ->name('payroll-periods.review');
        Route::patch('payroll-periods/{payrollPeriod}/approve', [PayrollPeriodController::class, 'approve'])
            ->middleware('permission:payrolls.approve')
            ->name('payroll-periods.approve');
        Route::patch('payroll-periods/{payrollPeriod}/pay', [PayrollPeriodController::class, 'pay'])
            ->middleware('permission:payrolls.mark-paid')
            ->name('payroll-periods.pay');
        Route::patch('payroll-periods/{payrollPeriod}/close', [PayrollPeriodController::class, 'close'])
            ->middleware('permission:payrolls.close')
            ->name('payroll-periods.close');
        Route::patch('payroll-periods/{payrollPeriod}/reopen', [PayrollPeriodController::class, 'reopen'])
            ->middleware('permission:payrolls.reopen')
            ->name('payroll-periods.reopen');

        Route::get('payrolls', [EmployeePayrollController::class, 'index'])
            ->middleware('permission:payrolls.view|payrolls.view-own')
            ->name('payrolls.index');
        Route::get('payrolls/{employeePayroll}/slip', [EmployeePayrollController::class, 'slip'])
            ->middleware('permission:payrolls.print|payrolls.view-own')
            ->name('payrolls.slip');
        Route::get('payrolls/{employeePayroll}', [EmployeePayrollController::class, 'show'])
            ->middleware('permission:payrolls.view|payrolls.view-own')
            ->name('payrolls.show');
    });
