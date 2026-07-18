<?php

declare(strict_types=1);

namespace App\Http\Controllers\OperationalFinance;

use App\Enums\OperationalFinance\OperationalTransactionType as Type;
use App\Http\Controllers\Controller;
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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class OperationalFinanceController extends Controller
{
    public function dashboard(OperationalFinanceService $service): View { return view('operational-finance.dashboard', ['title'=>'Keuangan Operasional','summary'=>$service->summary()]); }
    public function cashAccounts(): View { return view('operational-finance.cash-accounts.index', ['title'=>'Akun Kas','accounts'=>CashAccount::latest()->paginate(15)]); }
    public function createCashAccount(): View { return view('operational-finance.cash-accounts.form', ['title'=>'Tambah Akun Kas','account'=>new CashAccount]); }
    public function storeCashAccount(Request $r) { $data=$r->validate(['name'=>'required|string|max:255','code'=>'nullable|string|max:50','account_type'=>'required|string','institution_name'=>'nullable|string','account_number'=>'nullable|string','account_holder'=>'nullable|string','opening_balance'=>'required|numeric|min:0','opening_balance_date'=>'nullable|date','allow_negative_balance'=>'boolean','is_active'=>'boolean','is_default'=>'boolean','notes'=>'nullable|string']); if(($data['is_default']??false)) CashAccount::query()->update(['is_default'=>false]); $data['current_balance']=$data['opening_balance']; $data['chart_account_id']=ChartAccount::query()->where('is_cash_account', true)->value('id') ?? ChartAccount::query()->value('id'); $data['created_by']=auth()->id(); $account=CashAccount::create($data); activity('operational-finance')->performedOn($account)->causedBy(auth()->user())->log('akun kas dibuat'); return redirect()->route('operational-finance.cash-accounts.index')->with('status','Akun kas berhasil disimpan.'); }
    public function categories(): View { return view('operational-finance.categories.index', ['title'=>'Kategori Transaksi','categories'=>FinanceCategory::with('parent')->orderBy('transaction_type')->orderBy('sort_order')->paginate(20)]); }
    public function createCategory(): View { return view('operational-finance.categories.form', ['title'=>'Tambah Kategori','category'=>new FinanceCategory,'parents'=>FinanceCategory::where('is_active',true)->get()]); }
    public function storeCategory(Request $r) { $data=$r->validate(['code'=>'required|string|max:50|unique:finance_categories,code','name'=>'required|string|max:255','transaction_type'=>'required|in:income,expense','parent_id'=>'nullable|exists:finance_categories,id','description'=>'nullable|string','is_budgetable'=>'boolean','requires_approval'=>'boolean','approval_threshold'=>'nullable|numeric|min:0','is_active'=>'boolean','sort_order'=>'nullable|integer|min:0','notes'=>'nullable|string']); FinanceCategory::create($data); return redirect()->route('operational-finance.categories.index')->with('status','Kategori berhasil disimpan.'); }
    public function incomes(): View { return $this->transactions(Type::Income->value, 'operational-finance.transactions.index', 'Pemasukan Operasional'); }
    public function expenses(): View { return $this->transactions(Type::Expense->value, 'operational-finance.transactions.index', 'Pengeluaran Operasional'); }
    private function transactions(string $type, string $view, string $title): View { return view($view, ['title'=>$title,'type'=>$type,'transactions'=>OperationalTransaction::with('cashAccount','category','creator')->where('transaction_type',$type)->latest()->paginate(15)]); }
    public function createIncome(): View { return $this->transactionForm(Type::Income->value, 'Catat Pemasukan'); }
    public function createExpense(): View { return $this->transactionForm(Type::Expense->value, 'Catat Pengeluaran'); }
    private function transactionForm(string $type, string $title): View { return view('operational-finance.transactions.form', ['title'=>$title,'type'=>$type,'accounts'=>CashAccount::where('is_active',true)->get(),'categories'=>FinanceCategory::where('transaction_type',$type)->where('is_active',true)->get(),'allocations'=>BudgetAllocation::with('financeCategory')->get()]); }
    public function storeIncome(Request $r, OperationalFinanceService $s) { $s->storeTransaction($this->txData($r), Type::Income->value, $r->boolean('post_now')); return redirect()->route('operational-finance.incomes.index')->with('status','Pemasukan berhasil disimpan.'); }
    public function storeExpense(Request $r, OperationalFinanceService $s) { $s->storeTransaction($this->txData($r), Type::Expense->value, $r->boolean('post_now')); return redirect()->route('operational-finance.expenses.index')->with('status','Pengeluaran berhasil disimpan.'); }
    private function txData(Request $r): array { return $r->validate(['transaction_date'=>'required|date','cash_account_id'=>'required|exists:cash_accounts,id','finance_category_id'=>'required|exists:finance_categories,id','amount'=>'required|numeric|min:1','description'=>'required|string','reference_number'=>'nullable|string','budget_allocation_id'=>'nullable|exists:budget_allocations,id','notes'=>'nullable|string']); }
    public function show(OperationalTransaction $transaction): View { return view('operational-finance.transactions.show', ['title'=>'Detail Transaksi','transaction'=>$transaction->load('cashAccount','category','creator','attachments','approvals')]); }
    public function submit(OperationalTransaction $transaction, OperationalFinanceService $s) { $s->submit($transaction); return back()->with('status','Transaksi berhasil diajukan.'); }
    public function approve(Request $r, OperationalTransaction $transaction, OperationalFinanceService $s) { $s->approve($transaction, $r->input('approval_notes')); return back()->with('status','Transaksi disetujui.'); }
    public function reject(Request $r, OperationalTransaction $transaction, OperationalFinanceService $s) { $data=$r->validate(['rejection_reason'=>'required|string|min:5']); $s->reject($transaction, $data['rejection_reason']); return back()->with('status','Transaksi ditolak.'); }
    public function cancel(Request $r, OperationalTransaction $transaction, OperationalFinanceService $s) { $data=$r->validate(['cancellation_reason'=>'required|string|min:5']); $s->cancel($transaction, $data['cancellation_reason']); return back()->with('status','Transaksi dibatalkan.'); }
    public function transfers(): View { return view('operational-finance.transfers.index', ['title'=>'Transfer Antar Kas','transfers'=>CashTransfer::with('sourceCashAccount','destinationCashAccount')->latest()->paginate(15),'accounts'=>CashAccount::where('is_active',true)->get()]); }
    public function storeTransfer(Request $r, OperationalFinanceService $s) { $s->transfer($r->validate(['transfer_date'=>'required|date','source_cash_account_id'=>'required|exists:cash_accounts,id','destination_cash_account_id'=>'required|exists:cash_accounts,id|different:source_cash_account_id','amount'=>'required|numeric|min:1','description'=>'required|string','reference_number'=>'nullable|string'])); return back()->with('status','Transfer berhasil dibukukan.'); }
    public function approvals(): View { return view('operational-finance.approvals.index', ['title'=>'Persetujuan Keuangan','transactions'=>OperationalTransaction::with('cashAccount','category','creator')->where('status','submitted')->latest()->paginate(15)]); }
    public function budgets(): View { return view('operational-finance.budgets.index', ['title'=>'Anggaran','periods'=>BudgetPeriod::latest()->paginate(15),'categories'=>FinanceCategory::where('transaction_type','expense')->where('is_budgetable',true)->get()]); }
    public function storeBudget(Request $r) { BudgetPeriod::create($r->validate(['name'=>'required|string','fiscal_year'=>'required|integer','start_date'=>'required|date','end_date'=>'required|date|after_or_equal:start_date','total_budget'=>'required|numeric|min:0','notes'=>'nullable|string']) + ['status'=>'draft','created_by'=>auth()->id()]); return back()->with('status','Periode anggaran disimpan.'); }
    public function realization(): View { return view('operational-finance.budgets.realization', ['title'=>'Realisasi Anggaran','allocations'=>BudgetAllocation::with('budgetPeriod','financeCategory')->paginate(20)]); }
    public function cashBook(OperationalFinanceService $s): View { return view('operational-finance.cash-book.index', ['title'=>'Buku Kas','transactions'=>OperationalTransaction::with('cashAccount','category')->where('status','posted')->orderBy('transaction_date')->paginate(20),'summary'=>$s->summary(),'accounts'=>CashAccount::all()]); }
    public function closings(): View { return view('operational-finance.closings.index', ['title'=>'Penutupan Kas','closings'=>CashClosing::latest()->paginate(15),'accounts'=>CashAccount::all()]); }
    public function storeClosing(Request $r) { $d=$r->validate(['cash_account_id'=>'required|exists:cash_accounts,id','closing_date'=>'required|date','actual_balance'=>'required|numeric','notes'=>'nullable|string']); $cash=CashAccount::findOrFail($d['cash_account_id']); $diff=(float)$d['actual_balance']-(float)$cash->current_balance; if($diff != 0.0 && blank($d['notes']??null)) return back()->withErrors(['notes'=>'Catatan wajib diisi jika ada selisih.'])->withInput(); CashClosing::create($d+['opening_balance'=>$cash->opening_balance,'expected_balance'=>$cash->current_balance,'difference'=>$diff,'status'=>'closed','closed_by'=>auth()->id(),'closed_at'=>now()]); return back()->with('status','Penutupan kas disimpan.'); }
    public function reconciliations(): View { return view('operational-finance.reconciliations.index', ['title'=>'Rekonsiliasi Kas','reconciliations'=>CashReconciliation::latest()->paginate(15),'accounts'=>CashAccount::all()]); }
    public function storeReconciliation(Request $r) { $d=$r->validate(['cash_account_id'=>'required|exists:cash_accounts,id','reconciliation_date'=>'required|date','actual_balance'=>'required|numeric','notes'=>'required|string']); $cash=CashAccount::findOrFail($d['cash_account_id']); CashReconciliation::create($d+['system_balance'=>$cash->current_balance,'difference'=>(float)$d['actual_balance']-(float)$cash->current_balance,'created_by'=>auth()->id()]); return back()->with('status','Rekonsiliasi disimpan.'); }
    public function reports(OperationalFinanceService $s): View { return view('operational-finance.reports.index', ['title'=>'Laporan Keuangan Operasional','summary'=>$s->summary(),'transactions'=>OperationalTransaction::with('cashAccount','category')->latest()->paginate(20)]); }
    public function export() { return response("Nomor,Tanggal,Jenis,Nominal\n",200,['Content-Type'=>'text/csv','Content-Disposition'=>'attachment; filename=laporan-keuangan-operasional.csv']); }
    public function print(OperationalTransaction $transaction): View { return view('operational-finance.print.transaction', ['title'=>'Cetak Bukti Kas','transaction'=>$transaction->load('cashAccount','category')]); }
}
