<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Enums\Finance\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\PaymentCancellationRequest;
use App\Http\Requests\Finance\StudentPaymentRequest;
use App\Models\Finance\CashAccount;
use App\Models\Finance\StudentInvoice;
use App\Models\Finance\StudentPayment;
use App\Models\Student;
use App\Services\Finance\StudentPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class StudentPaymentController extends Controller
{
    public function index(Request $request): View
    {
        $payments = StudentPayment::query()
            ->with(['student', 'receiver'])
            ->when($request->string('search')->toString(), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('payment_number', 'like', "%{$search}%")
                        ->orWhere('reference_number', 'like', "%{$search}%")
                        ->orWhereHas('student', fn ($query) => $query->where('name', 'like', "%{$search}%"));
                });
            })
            ->when(
                $request->string('status')->toString(),
                fn ($query, string $status) => $query->where('status', $status),
            )
            ->when(
                $request->integer('student_id'),
                fn ($query, int $studentId) => $query->where('student_id', $studentId),
            )
            ->latest('payment_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $students = Student::query()->orderBy('name')->get(['id', 'name']);

        return view('finance.payments.index', compact('payments', 'students'));
    }

    public function create(): View
    {
        $students = Student::query()->orderBy('name')->get(['id', 'name']);
        $invoices = StudentInvoice::query()
            ->with(['student', 'feeType'])
            ->where('outstanding_amount', '>', 0)
            ->whereNotIn('status', [
                InvoiceStatus::Paid->value,
                InvoiceStatus::Cancelled->value,
            ])
            ->oldest('due_on')
            ->get();
        $cashAccounts = CashAccount::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('finance.payments.form', compact(
            'students',
            'invoices',
            'cashAccounts',
        ));
    }

    public function store(
        StudentPaymentRequest $request,
        StudentPaymentService $service,
    ): RedirectResponse {
        $validated = $request->validated();
        $allocations = $validated['allocations'];
        unset($validated['allocations']);

        $payment = $service->post([
            ...$validated,
            'received_by' => $request->user()->getKey(),
        ], $allocations);

        return redirect()
            ->route('finance.student-payments.show', $payment)
            ->with('status', 'Pembayaran berhasil diposting.');
    }

    public function show(StudentPayment $studentPayment): View
    {
        $payment = $this->loadPayment($studentPayment);

        return view('finance.payments.show', compact('payment'));
    }

    public function receipt(StudentPayment $studentPayment): View
    {
        $payment = $this->loadPayment($studentPayment);

        return view('finance.payments.receipt', compact('payment'));
    }

    public function cancel(
        PaymentCancellationRequest $request,
        StudentPayment $studentPayment,
        StudentPaymentService $service,
    ): RedirectResponse {
        $service->cancel($studentPayment, $request->validated('reason'));

        return redirect()
            ->route('finance.student-payments.show', $studentPayment)
            ->with('status', 'Pembayaran dibatalkan dan jurnal reversal dibuat.');
    }

    private function loadPayment(StudentPayment $studentPayment): StudentPayment
    {
        return $studentPayment->load([
            'student',
            'receiver',
            'canceller',
            'allocations.invoice.feeType',
            'financialTransaction.reversalTransaction',
        ]);
    }
}
