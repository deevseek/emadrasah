<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\FeeType;
use App\Models\Finance\StudentInvoice;
use App\Models\Student;
use App\Services\Finance\StudentInvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentInvoiceController extends Controller
{
    public function index(): View { return view('finance.invoices.index', ['invoices' => StudentInvoice::with('student', 'feeType')->latest()->paginate(15)]); }
    public function create(): View { return view('finance.invoices.form', ['students' => Student::orderBy('name')->get(), 'feeTypes' => FeeType::where('is_active', true)->get()]); }
    public function show(StudentInvoice $studentInvoice): View { return view('finance.invoices.show', ['invoice' => $studentInvoice->load('student', 'feeType')]); }
    public function store(Request $request, StudentInvoiceService $service): RedirectResponse
    {
        $invoice = $service->create($request->validate(['student_id' => ['required','exists:students,id'], 'academic_year_id' => ['required','exists:academic_years,id'], 'semester_id' => ['nullable','exists:semesters,id'], 'billing_period_id' => ['nullable','exists:billing_periods,id'], 'fee_type_id' => ['required','exists:fee_types,id'], 'original_amount' => ['required','numeric','min:0'], 'discount_amount' => ['nullable','numeric','min:0'], 'penalty_amount' => ['nullable','numeric','min:0'], 'due_on' => ['nullable','date'], 'description' => ['nullable','string']]), (int) $request->user()->id);

        return redirect()->route('finance.student-invoices.show', $invoice)->with('status', 'Tagihan dibuat.');
    }
}
