<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\StudentInvoice;
use App\Models\Finance\StudentPayment;
use App\Models\Student;
use App\Services\Finance\StudentPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentPaymentController extends Controller
{
    public function index(): View { return view('finance.payments.index', ['payments' => StudentPayment::with('student')->latest()->paginate(15)]); }
    public function create(Request $request): View { return view('finance.payments.form', ['students' => Student::orderBy('name')->get(), 'invoices' => StudentInvoice::with('student')->where('outstanding_amount', '>', 0)->oldest('due_on')->get()]); }
    public function show(StudentPayment $studentPayment): View { return view('finance.payments.show', ['payment' => $studentPayment->load('student', 'allocations.invoice.feeType')]); }
    public function receipt(StudentPayment $studentPayment): View { return view('finance.payments.receipt', ['payment' => $studentPayment->load('student', 'allocations.invoice.feeType')]); }
    public function store(Request $request, StudentPaymentService $service): RedirectResponse
    {
        $data = $request->validate(['payment_date' => ['required','date'], 'student_id' => ['required','exists:students,id'], 'payment_method' => ['required','string'], 'reference_number' => ['nullable','string'], 'total_amount' => ['required','numeric','min:1'], 'notes' => ['nullable','string']]);
        $payment = $service->post($data + ['received_by' => $request->user()->id], $request->validate(['allocations' => ['required','array'], 'allocations.*.student_invoice_id' => ['required','exists:student_invoices,id'], 'allocations.*.amount' => ['required','numeric','min:1']])['allocations']);

        return redirect()->route('finance.student-payments.show', $payment)->with('status', 'Pembayaran diposting.');
    }
    public function cancel(Request $request, StudentPayment $studentPayment, StudentPaymentService $service): RedirectResponse { $service->cancel($studentPayment, $request->validate(['reason' => ['required','string']])['reason']); return back()->with('status', 'Pembayaran dibatalkan.'); }
}
