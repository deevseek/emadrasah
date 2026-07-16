<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Enums\Finance\PayrollStatus;
use App\Enums\Finance\TransactionType;
use App\Http\Controllers\Controller;
use App\Models\Finance\CashAccount;
use App\Models\Finance\FinancialTransaction;
use App\Models\Finance\PayrollPeriod;
use App\Models\Finance\StudentInvoice;
use App\Models\Finance\StudentPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class FinanceDashboardController extends Controller
{
    public function payments(): View
    {
        return view('finance.dashboards.payments', [
            'todayPayments' => StudentPayment::whereDate('payment_date', today())->sum('total_amount'),
            'arrears' => StudentInvoice::where('outstanding_amount', '>', 0)->sum('outstanding_amount'),
        ]);
    }

    public function finance(Request $request): View
    {
        $year = (int) $request->integer('year', now()->year);
        $periodId = $request->integer('billing_period_id') ?: null;
        $today = today()->toDateString();

        $invoiceQuery = StudentInvoice::query()->when($periodId, fn ($query) => $query->where('billing_period_id', $periodId));
        $paymentQuery = StudentPayment::query()->where('status', 'posted');

        $monthly = collect(range(1, 12))->map(function (int $month) use ($year): array {
            $income = FinancialTransaction::query()
                ->whereYear('transaction_date', $year)
                ->whereMonth('transaction_date', $month)
                ->where('transaction_type', TransactionType::CashIn->value)
                ->where('status', 'posted')
                ->withSum('lines as amount', 'debit')
                ->get()
                ->sum('amount');
            $expense = FinancialTransaction::query()
                ->whereYear('transaction_date', $year)
                ->whereMonth('transaction_date', $month)
                ->where('transaction_type', TransactionType::CashOut->value)
                ->where('status', 'posted')
                ->withSum('lines as amount', 'credit')
                ->get()
                ->sum('amount');

            return ['month' => Carbon::create($year, $month)->translatedFormat('M'), 'income' => $income, 'expense' => $expense];
        });

        return view('finance.dashboards.finance', [
            'year' => $year,
            'periodId' => $periodId,
            'todayIncome' => FinancialTransaction::whereDate('transaction_date', $today)->where('transaction_type', TransactionType::CashIn->value)->where('status', 'posted')->withSum('lines as amount', 'debit')->get()->sum('amount'),
            'todayExpense' => FinancialTransaction::whereDate('transaction_date', $today)->where('transaction_type', TransactionType::CashOut->value)->where('status', 'posted')->withSum('lines as amount', 'credit')->get()->sum('amount'),
            'cashBalance' => CashAccount::sum('current_balance'),
            'activeInvoiceTotal' => (clone $invoiceQuery)->whereNotIn('status', ['cancelled', 'paid'])->sum('final_amount'),
            'paymentTotal' => (clone $paymentQuery)->sum('total_amount'),
            'arrearsTotal' => (clone $invoiceQuery)->where('outstanding_amount', '>', 0)->sum('outstanding_amount'),
            'studentsInArrears' => (clone $invoiceQuery)->where('outstanding_amount', '>', 0)->distinct('student_id')->count('student_id'),
            'activePayrollPeriod' => PayrollPeriod::whereIn('status', [PayrollStatus::Draft->value, PayrollStatus::Calculated->value, PayrollStatus::Reviewed->value, PayrollStatus::Approved->value])->latest()->first(),
            'payrollWaitingApproval' => PayrollPeriod::where('status', PayrollStatus::Reviewed->value)->count(),
            'recentTransactions' => FinancialTransaction::with('lines.account')->latest('transaction_date')->latest()->limit(8)->get(),
            'monthly' => $monthly,
            'billingPeriods' => \App\Models\Finance\BillingPeriod::latest()->limit(24)->get(),
        ]);
    }

    public function payroll(): View
    {
        return view('finance.dashboards.payroll', [
            'pendingPayrolls' => \App\Models\Finance\EmployeePayroll::whereIn('status', ['calculated', 'reviewed'])->count(),
            'unpaidSalary' => \App\Models\Finance\EmployeePayroll::where('status', 'approved')->sum('net_salary'),
        ]);
    }
}
