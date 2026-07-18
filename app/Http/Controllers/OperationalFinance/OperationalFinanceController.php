<?php

declare(strict_types=1);

namespace App\Http\Controllers\OperationalFinance;

use App\Enums\OperationalFinance\OperationalTransactionType as Type;
use App\Http\Controllers\Controller;
use App\Http\Requests\OperationalFinance\FinanceApprovalRequest;
use App\Http\Requests\OperationalFinance\FinanceCancellationRequest;
use App\Http\Requests\OperationalFinance\FinanceRejectionRequest;
use App\Http\Requests\OperationalFinance\OperationalFinanceFilterRequest;
use App\Http\Requests\OperationalFinance\StoreBudgetPeriodRequest;
use App\Http\Requests\OperationalFinance\StoreCashAccountRequest;
use App\Http\Requests\OperationalFinance\StoreCashClosingRequest;
use App\Http\Requests\OperationalFinance\StoreCashReconciliationRequest;
use App\Http\Requests\OperationalFinance\StoreCashTransferRequest;
use App\Http\Requests\OperationalFinance\StoreFinanceCategoryRequest;
use App\Http\Requests\OperationalFinance\StoreOperationalExpenseRequest;
use App\Http\Requests\OperationalFinance\StoreOperationalIncomeRequest;
use App\Models\Finance\CashAccount;
use App\Models\Finance\ChartAccount;
use App\Models\OperationalFinance\BudgetAllocation;
use App\Models\OperationalFinance\BudgetPeriod;
use App\Models\OperationalFinance\CashClosing;
use App\Models\OperationalFinance\CashReconciliation;
use App\Models\OperationalFinance\CashTransfer;
use App\Models\OperationalFinance\FinanceCategory;
use App\Models\OperationalFinance\OperationalTransaction;
use App\Services\OperationalFinance\OperationalFinanceService;
use Illuminate\View\View;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OperationalFinanceController extends Controller
{
    public function dashboard(OperationalFinanceService $service): View { return view('operational-finance.dashboard', ['title'=>'Keuangan Operasional','summary'=>$service->summary()]); }
    public function cashAccounts(): View { return view('operational-finance.cash-accounts.index', ['title'=>'Akun Kas','accounts'=>CashAccount::latest()->paginate(15)]); }
    public function createCashAccount(): View { return view('operational-finance.cash-accounts.form', ['title'=>'Tambah Akun Kas','account'=>new CashAccount]); }
    public function storeCashAccount(StoreCashAccountRequest $r) { $data=$r->validated(); if(($data['is_default']??false)) CashAccount::query()->update(['is_default'=>false]); $data['current_balance']=$data['opening_balance']; $data['chart_account_id']=ChartAccount::query()->where('is_cash_account', true)->value('id') ?? ChartAccount::query()->value('id'); $data['created_by']=auth()->id(); $account=CashAccount::create($data); activity('operational-finance')->performedOn($account)->causedBy(auth()->user())->log('akun kas dibuat'); return redirect()->route('operational-finance.cash-accounts.index')->with('status','Akun kas berhasil disimpan.'); }
    public function categories(): View { return view('operational-finance.categories.index', ['title'=>'Kategori Transaksi','categories'=>FinanceCategory::with('parent')->orderBy('transaction_type')->orderBy('sort_order')->paginate(20)]); }
    public function createCategory(): View { return view('operational-finance.categories.form', ['title'=>'Tambah Kategori','category'=>new FinanceCategory,'parents'=>FinanceCategory::where('is_active',true)->get()]); }
    public function storeCategory(StoreFinanceCategoryRequest $r) { $data=$r->validated(); FinanceCategory::create($data); return redirect()->route('operational-finance.categories.index')->with('status','Kategori berhasil disimpan.'); }
    public function incomes(): View { return $this->transactions(Type::Income->value, 'operational-finance.transactions.index', 'Pemasukan Operasional'); }
    public function expenses(): View { return $this->transactions(Type::Expense->value, 'operational-finance.transactions.index', 'Pengeluaran Operasional'); }
    private function transactions(string $type, string $view, string $title): View { return view($view, ['title'=>$title,'type'=>$type,'transactions'=>OperationalTransaction::with('cashAccount','category','creator')->where('transaction_type',$type)->latest()->paginate(15)]); }
    public function createIncome(): View { return $this->transactionForm(Type::Income->value, 'Catat Pemasukan'); }
    public function createExpense(): View { return $this->transactionForm(Type::Expense->value, 'Catat Pengeluaran'); }
    private function transactionForm(string $type, string $title): View { return view('operational-finance.transactions.form', ['title'=>$title,'type'=>$type,'accounts'=>CashAccount::where('is_active',true)->get(),'categories'=>FinanceCategory::where('transaction_type',$type)->where('is_active',true)->get(),'allocations'=>BudgetAllocation::with('financeCategory')->get()]); }
    public function storeIncome(StoreOperationalIncomeRequest $r, OperationalFinanceService $s) { $s->storeTransaction($this->txData($r), Type::Income->value, $r->boolean('post_now')); return redirect()->route('operational-finance.incomes.index')->with('status','Pemasukan berhasil disimpan.'); }
    public function storeExpense(StoreOperationalExpenseRequest $r, OperationalFinanceService $s) { $s->storeTransaction($this->txData($r), Type::Expense->value, $r->boolean('post_now')); return redirect()->route('operational-finance.expenses.index')->with('status','Pengeluaran berhasil disimpan.'); }
    private function txData(StoreOperationalIncomeRequest|StoreOperationalExpenseRequest $r): array { return collect($r->validated())->except('post_now')->all(); }
    public function show(OperationalTransaction $transaction): View { return view('operational-finance.transactions.show', ['title'=>'Detail Transaksi','transaction'=>$transaction->load('cashAccount','category','creator','attachments','approvals')]); }
    public function submit(OperationalTransaction $transaction, OperationalFinanceService $s) { $s->submit($transaction); return back()->with('status','Transaksi berhasil diajukan.'); }
    public function approve(FinanceApprovalRequest $r, OperationalTransaction $transaction, OperationalFinanceService $s) { $s->approve($transaction, $r->input('approval_notes')); return back()->with('status','Transaksi disetujui.'); }
    public function reject(FinanceRejectionRequest $r, OperationalTransaction $transaction, OperationalFinanceService $s) { $data=$r->validated(); $s->reject($transaction, $data['rejection_reason']); return back()->with('status','Transaksi ditolak.'); }
    public function cancel(FinanceCancellationRequest $r, OperationalTransaction $transaction, OperationalFinanceService $s) { $data=$r->validated(); $s->cancel($transaction, $data['cancellation_reason']); return back()->with('status','Transaksi dibatalkan.'); }
    public function transfers(): View { return view('operational-finance.transfers.index', ['title'=>'Transfer Antar Kas','transfers'=>CashTransfer::with('sourceCashAccount','destinationCashAccount')->latest()->paginate(15),'accounts'=>CashAccount::where('is_active',true)->get()]); }
    public function storeTransfer(StoreCashTransferRequest $r, OperationalFinanceService $s) { $s->transfer($r->validated()); return back()->with('status','Transfer berhasil dibukukan.'); }
    public function approvals(): View { return view('operational-finance.approvals.index', ['title'=>'Persetujuan Keuangan','transactions'=>OperationalTransaction::with('cashAccount','category','creator')->where('status','submitted')->latest()->paginate(15)]); }
    public function budgets(): View { return view('operational-finance.budgets.index', ['title'=>'Anggaran','periods'=>BudgetPeriod::latest()->paginate(15),'categories'=>FinanceCategory::where('transaction_type','expense')->where('is_budgetable',true)->get()]); }
    public function storeBudget(StoreBudgetPeriodRequest $r) { BudgetPeriod::create($r->validated() + ['status'=>'draft','created_by'=>auth()->id()]); return back()->with('status','Periode anggaran disimpan.'); }
    public function realization(): View { return view('operational-finance.budgets.realization', ['title'=>'Realisasi Anggaran','allocations'=>BudgetAllocation::with('budgetPeriod','financeCategory')->paginate(20)]); }
    public function cashBook(OperationalFinanceService $s): View { return view('operational-finance.cash-book.index', ['title'=>'Buku Kas','transactions'=>OperationalTransaction::with('cashAccount','category')->where('status','posted')->orderBy('transaction_date')->paginate(20),'summary'=>$s->summary(),'accounts'=>CashAccount::all()]); }
    public function closings(): View { return view('operational-finance.closings.index', ['title'=>'Penutupan Kas','closings'=>CashClosing::latest()->paginate(15),'accounts'=>CashAccount::all()]); }
    public function storeClosing(StoreCashClosingRequest $r) { $d=$r->validated(); $cash=CashAccount::findOrFail($d['cash_account_id']); $diff=(float)$d['actual_balance']-(float)$cash->current_balance; if($diff != 0.0 && blank($d['notes']??null)) return back()->withErrors(['notes'=>'Catatan wajib diisi jika ada selisih.'])->withInput(); CashClosing::create($d+['opening_balance'=>$cash->opening_balance,'expected_balance'=>$cash->current_balance,'difference'=>$diff,'status'=>'closed','closed_by'=>auth()->id(),'closed_at'=>now()]); return back()->with('status','Penutupan kas disimpan.'); }
    public function reconciliations(): View { return view('operational-finance.reconciliations.index', ['title'=>'Rekonsiliasi Kas','reconciliations'=>CashReconciliation::latest()->paginate(15),'accounts'=>CashAccount::all()]); }
    public function storeReconciliation(StoreCashReconciliationRequest $r) { $d=$r->validated(); $cash=CashAccount::findOrFail($d['cash_account_id']); CashReconciliation::create($d+['system_balance'=>$cash->current_balance,'difference'=>(float)$d['actual_balance']-(float)$cash->current_balance,'created_by'=>auth()->id()]); return back()->with('status','Rekonsiliasi disimpan.'); }
    public function reports(OperationalFinanceFilterRequest $request, OperationalFinanceService $s): View { return view('operational-finance.reports.index', ['title'=>'Laporan Keuangan Operasional','summary'=>$s->summary(),'transactions'=>$this->reportQuery($request->validated())->paginate(20)->withQueryString()]); }
    public function export(OperationalFinanceFilterRequest $request): StreamedResponse { $filters=$request->validated(); return response()->streamDownload(function () use ($filters): void { $out=fopen('php://output','w'); fputcsv($out, ['Nomor','Tanggal','Jenis','Akun Kas','Kategori','Uraian','Pemasukan','Pengeluaran','Status']); $income=0; $expense=0; $this->reportQuery($filters)->chunk(500, function ($rows) use ($out, &$income, &$expense): void { foreach ($rows as $t) { $isIncome=in_array($t->transaction_type, ['income','transfer_in'], true); $amount=(float) $t->amount; $income += $isIncome ? $amount : 0; $expense += $isIncome ? 0 : $amount; fputcsv($out, [$t->transaction_number, optional($t->transaction_date)->format('d/m/Y'), $t->transaction_type, $t->cashAccount?->name, $t->category?->name, $t->description, $isIncome ? number_format($amount, 2, ',', '.') : '0,00', $isIncome ? '0,00' : number_format($amount, 2, ',', '.'), $t->status]); } }); fputcsv($out, []); fputcsv($out, ['TOTAL','','','','','',number_format($income, 2, ',', '.'),number_format($expense, 2, ',', '.'), '']); fclose($out); }, 'laporan-keuangan-operasional-'.now('Asia/Jakarta')->format('Ymd-His').'.csv', ['Content-Type'=>'text/csv']); }
    private function reportQuery(array $filters): Builder { return OperationalTransaction::with('cashAccount','category')->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('transaction_date', '>=', $v))->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('transaction_date', '<=', $v))->when($filters['cash_account_id'] ?? null, fn ($q, $v) => $q->where('cash_account_id', $v))->when($filters['finance_category_id'] ?? null, fn ($q, $v) => $q->where('finance_category_id', $v))->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))->when($filters['transaction_type'] ?? null, fn ($q, $v) => $q->where('transaction_type', $v))->when($filters['search'] ?? null, fn ($q, $v) => $q->where(fn ($qq) => $qq->where('transaction_number', 'like', '%'.$v.'%')->orWhere('description', 'like', '%'.$v.'%')->orWhere('reference_number', 'like', '%'.$v.'%')))->latest('transaction_date')->latest('id'); }
    public function print(OperationalTransaction $transaction): View { return view('operational-finance.print.transaction', ['title'=>'Cetak Bukti Kas','transaction'=>$transaction->load('cashAccount','category')]); }
}
