<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\CashAccount;
use App\Models\Finance\FinancialTransaction;
use App\Models\Finance\StudentInvoice;
use App\Models\Finance\StudentPayment;
use Illuminate\View\View;

class FinanceDashboardController extends Controller
{
    public function payments(): View { return view('finance.dashboards.payments', ['todayPayments' => StudentPayment::whereDate('payment_date', today())->sum('total_amount'), 'arrears' => StudentInvoice::where('outstanding_amount', '>', 0)->sum('outstanding_amount')]); }
    public function finance(): View { return view('finance.dashboards.finance', ['cashBalance' => CashAccount::sum('current_balance'), 'draftTransactions' => FinancialTransaction::where('status', 'draft')->count()]); }
    public function payroll(): View { return view('finance.dashboards.payroll', ['pendingPayrolls' => \App\Models\Finance\EmployeePayroll::whereIn('status', ['calculated','reviewed'])->count(), 'unpaidSalary' => \App\Models\Finance\EmployeePayroll::where('status', 'approved')->sum('net_salary')]); }
}
