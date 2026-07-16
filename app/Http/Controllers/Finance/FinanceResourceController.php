<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Enums\Finance\ApprovalStatus;
use App\Enums\Finance\PayrollStatus;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Finance\BillingPeriod;
use App\Models\Finance\CashAccount;
use App\Models\Finance\ChartAccount;
use App\Models\Finance\EmployeePayroll;
use App\Models\Finance\EmployeeSalaryComponent;
use App\Models\Finance\FinancialTransaction;
use App\Models\Finance\PayrollPeriod;
use App\Models\Finance\SalaryComponent;
use App\Models\Finance\StudentDiscount;
use App\Models\Finance\StudentInvoice;
use App\Models\Student;
use App\Services\Finance\FinancialTransactionService;
use App\Services\Finance\PayrollCalculationService;
use App\Services\Finance\PayrollWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class FinanceResourceController extends Controller
{
    public function billingPeriods(): View { return $this->page('Periode Tagihan', BillingPeriod::latest()->paginate(15)); }
    public function storeBillingPeriod(Request $request): RedirectResponse { BillingPeriod::updateOrCreate($request->validate(['academic_year_id'=>'required|exists:academic_years,id','semester_id'=>'nullable|exists:semesters,id','month'=>'nullable|integer|min:1|max:12','year'=>'required|integer|min:2000','name'=>'required|string','starts_on'=>'nullable|date','due_on'=>'nullable|date|after_or_equal:starts_on','is_active'=>'boolean']), $request->only('name') + ['is_active'=>$request->boolean('is_active', true)]); return back()->with('status','Periode tagihan disimpan.'); }
    public function discounts(): View { return $this->page('Potongan/Beasiswa', StudentDiscount::with('student','feeType')->latest()->paginate(15)); }
    public function storeDiscount(Request $request): RedirectResponse { StudentDiscount::create($request->validate(['student_id'=>'required|exists:students,id','fee_type_id'=>'nullable|exists:fee_types,id','academic_year_id'=>'required|exists:academic_years,id','semester_id'=>'nullable|exists:semesters,id','discount_type'=>'required|string','discount_value'=>'required|numeric|min:0','maximum_discount'=>'nullable|numeric|min:0','starts_on'=>'nullable|date','ends_on'=>'nullable|date|after_or_equal:starts_on','reason'=>'required|string']) + ['status'=>ApprovalStatus::Draft->value]); return back()->with('status','Potongan disimpan.'); }
    public function approveDiscount(StudentDiscount $studentDiscount): RedirectResponse { $studentDiscount->update(['status'=>ApprovalStatus::Approved->value,'approved_by'=>auth()->id()]); return back()->with('status','Potongan disetujui.'); }
    public function rejectDiscount(StudentDiscount $studentDiscount): RedirectResponse { $studentDiscount->update(['status'=>ApprovalStatus::Rejected->value]); return back()->with('status','Potongan ditolak.'); }
    public function chartAccounts(): View { return $this->page('Bagan Akun', ChartAccount::with('parent')->orderBy('code')->paginate(50)); }
    public function storeChartAccount(Request $request): RedirectResponse { ChartAccount::updateOrCreate(['code'=>$request->input('code')], $request->validate(['parent_id'=>'nullable|exists:chart_accounts,id','code'=>'required|string|max:50','name'=>'required|string','account_type'=>'required|string','normal_balance'=>'required|string','is_cash_account'=>'boolean','is_active'=>'boolean'])); return back()->with('status','Akun disimpan.'); }
    public function cashAccounts(): View { return $this->page('Kas dan Rekening', CashAccount::with('chartAccount')->paginate(15)); }
    public function storeCashAccount(Request $request): RedirectResponse { $data=$request->validate(['chart_account_id'=>'required|exists:chart_accounts,id','name'=>'required|string','account_number'=>'nullable|string','bank_name'=>'nullable|string','opening_balance'=>'nullable|numeric|min:0','is_active'=>'boolean']); $data['current_balance']=$data['opening_balance']??0; CashAccount::create($data); return back()->with('status','Kas disimpan.'); }
    public function transactions(): View { return $this->page('Jurnal Transaksi', FinancialTransaction::with('lines.account')->latest()->paginate(15)); }
    public function storeTransaction(Request $request, FinancialTransactionService $service): RedirectResponse { $data=$request->validate(['transaction_date'=>'required|date','transaction_type'=>'required|string','description'=>'required|string']); $lines=$request->validate(['lines'=>'required|array|min:2','lines.*.chart_account_id'=>'required|exists:chart_accounts,id','lines.*.cash_account_id'=>'nullable|exists:cash_accounts,id','lines.*.debit'=>'nullable|numeric|min:0','lines.*.credit'=>'nullable|numeric|min:0'])['lines']; $service->createAndPost($data+['created_by'=>$request->user()->id], $lines); return back()->with('status','Transaksi diposting.'); }
    public function cancelTransaction(Request $request, FinancialTransaction $financialTransaction, FinancialTransactionService $service): RedirectResponse { $service->reverse($financialTransaction, $request->validate(['reason'=>'required|string'])['reason']); return back()->with('status','Transaksi dibalik.'); }
    public function reports(Request $request): View { return view('finance.reports.index', ['title'=>'Laporan Keuangan', 'transactions'=>FinancialTransaction::with('lines.account')->when($request->status, fn($q,$s)=>$q->where('status',$s))->latest()->paginate(20), 'cashAccounts'=>CashAccount::all()]); }
    public function salaryComponents(): View { return $this->page('Komponen Gaji', SalaryComponent::paginate(15)); }
    public function storeSalaryComponent(Request $request): RedirectResponse { SalaryComponent::updateOrCreate(['code'=>$request->input('code')], $request->validate(['code'=>'required|string','name'=>'required|string','component_type'=>'required|string','calculation_type'=>'required|string','default_amount'=>'nullable|numeric|min:0','percentage'=>'nullable|numeric|min:0','taxable'=>'boolean','is_attendance_based'=>'boolean','is_active'=>'boolean','expense_account_id'=>'nullable|exists:chart_accounts,id','payable_account_id'=>'nullable|exists:chart_accounts,id'])); return back()->with('status','Komponen gaji disimpan.'); }
    public function employeeSalaries(): View { return $this->page('Struktur Gaji Pegawai', EmployeeSalaryComponent::with('employee','salaryComponent')->paginate(15)); }
    public function storeEmployeeSalary(Request $request): RedirectResponse { EmployeeSalaryComponent::create($request->validate(['employee_id'=>'required|exists:employees,id','salary_component_id'=>'required|exists:salary_components,id','amount'=>'nullable|numeric|min:0','percentage'=>'nullable|numeric|min:0','effective_from'=>'required|date','effective_until'=>'nullable|date|after_or_equal:effective_from','is_active'=>'boolean','notes'=>'nullable|string'])); return back()->with('status','Struktur gaji disimpan.'); }
    public function payrollPeriods(): View { return $this->page('Periode Penggajian', PayrollPeriod::latest()->paginate(15)); }
    public function storePayrollPeriod(Request $request): RedirectResponse { PayrollPeriod::create($request->validate(['name'=>'required|string','month'=>'required|integer|min:1|max:12','year'=>'required|integer|min:2000','starts_on'=>'required|date','ends_on'=>'required|date|after_or_equal:starts_on','payment_date'=>'nullable|date']) + ['status'=>PayrollStatus::Draft->value,'created_by'=>$request->user()->id]); return back()->with('status','Periode payroll dibuat.'); }
    public function calculatePayroll(PayrollPeriod $payrollPeriod, PayrollCalculationService $service): RedirectResponse { $service->calculate($payrollPeriod); return back()->with('status','Payroll dihitung.'); }
    public function reviewPayroll(PayrollPeriod $payrollPeriod, PayrollWorkflowService $service): RedirectResponse { $service->review($payrollPeriod); return back()->with('status','Payroll direview.'); }
    public function approvePayroll(PayrollPeriod $payrollPeriod, PayrollWorkflowService $service): RedirectResponse { $service->approve($payrollPeriod); return back()->with('status','Payroll disetujui.'); }
    public function payPayroll(Request $request, PayrollPeriod $payrollPeriod, PayrollWorkflowService $service): RedirectResponse { $service->markPaid($payrollPeriod, (int)$request->input('cash_account_id')); return back()->with('status','Payroll dibayar.'); }
    public function closePayroll(PayrollPeriod $payrollPeriod, PayrollWorkflowService $service): RedirectResponse { $service->close($payrollPeriod); return back()->with('status','Payroll ditutup.'); }
    public function reopenPayroll(Request $request, PayrollPeriod $payrollPeriod, PayrollWorkflowService $service): RedirectResponse { $service->reopen($payrollPeriod, $request->validate(['reason'=>'required|string'])['reason']); return back()->with('status','Payroll dibuka ulang.'); }
    public function payrolls(): View { return $this->page('Proses Penggajian', EmployeePayroll::with('employee','period')->paginate(15)); }
    public function slip(EmployeePayroll $employeePayroll): View { abort_if(auth()->user()->cannot('payrolls.view') && auth()->user()->cannot('payrolls.view-own'), 403); return view('finance.payrolls.slip', ['payroll'=>$employeePayroll->load('employee','period','items')]); }
    private function page(string $title, $items): View { return view('finance.generic.index', compact('title','items')); }
}
