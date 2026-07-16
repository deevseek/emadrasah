<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Enums\Finance\PayrollStatus;
use App\Enums\Finance\TransactionType;
use App\Models\Finance\CashAccount;
use App\Models\Finance\ChartAccount;
use App\Models\Finance\PayrollPeriod;
use Illuminate\Support\Facades\DB;

final class PayrollWorkflowService
{
    public function review(PayrollPeriod $period): void { $period->update(['status'=>PayrollStatus::Reviewed->value]); activity('payroll')->performedOn($period)->event('payroll.reviewed')->log('Payroll direview'); }
    public function approve(PayrollPeriod $period): void { DB::transaction(fn() => [$period->payrolls()->update(['status'=>PayrollStatus::Approved->value,'approved_by'=>auth()->id(),'approved_at'=>now()]), $period->update(['status'=>PayrollStatus::Approved->value])]); }
    public function markPaid(PayrollPeriod $period, ?int $cashAccountId = null): void
    {
        DB::transaction(function () use ($period, $cashAccountId): void {
            $cash = $cashAccountId ? CashAccount::findOrFail($cashAccountId) : CashAccount::where('is_active', true)->firstOrFail();
            $expense = ChartAccount::where('account_type', 'expense')->firstOrFail();
            foreach ($period->payrolls as $payroll) {
                $trx = app(FinancialTransactionService::class)->createAndPost(['transaction_date'=>now()->toDateString(),'transaction_type'=>TransactionType::CashOut->value,'description'=>'Pembayaran payroll '.$period->name,'reference_type'=>$payroll::class,'reference_id'=>$payroll->id,'created_by'=>auth()->id()], [['chart_account_id'=>$expense->id,'debit'=>$payroll->net_salary,'credit'=>0], ['chart_account_id'=>$cash->chart_account_id,'cash_account_id'=>$cash->id,'debit'=>0,'credit'=>$payroll->net_salary]]);
                $payroll->update(['status'=>PayrollStatus::Paid->value,'paid_at'=>now(),'financial_transaction_id'=>$trx->id]);
            }
            $period->update(['status'=>PayrollStatus::Paid->value,'payment_date'=>now()->toDateString()]);
        });
    }
    public function close(PayrollPeriod $period): void { $period->update(['status'=>PayrollStatus::Closed->value]); }
    public function reopen(PayrollPeriod $period, string $reason): void { $period->update(['status'=>PayrollStatus::Reviewed->value]); activity('payroll')->performedOn($period)->withProperties(['reason'=>$reason])->event('payroll.reopened')->log('Payroll dibuka ulang'); }
}
