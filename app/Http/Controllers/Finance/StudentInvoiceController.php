<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StudentInvoiceCancellationRequest;
use App\Http\Requests\Finance\StudentInvoiceRequest;
use App\Models\AcademicYear;
use App\Models\Finance\BillingPeriod;
use App\Models\Finance\FeeType;
use App\Models\Finance\StudentInvoice;
use App\Models\Semester;
use App\Models\Student;
use App\Services\Finance\StudentInvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class StudentInvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $invoices = StudentInvoice::query()
            ->with(['student', 'feeType', 'billingPeriod'])
            ->when($request->string('search')->toString(), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('invoice_number', 'like', "%{$search}%")
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
            ->when(
                $request->integer('billing_period_id'),
                fn ($query, int $billingPeriodId) => $query->where('billing_period_id', $billingPeriodId),
            )
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $students = Student::query()->orderBy('name')->get(['id', 'name']);
        $billingPeriods = BillingPeriod::query()->latest('year')->latest('month')->get();

        return view('finance.invoices.index', compact(
            'invoices',
            'students',
            'billingPeriods',
        ));
    }

    public function create(): View
    {
        return $this->form(new StudentInvoice);
    }

    public function store(
        StudentInvoiceRequest $request,
        StudentInvoiceService $service,
    ): RedirectResponse {
        $invoice = $service->create(
            $request->validated(),
            (int) $request->user()->getKey(),
        );

        return redirect()
            ->route('finance.student-invoices.show', $invoice)
            ->with('status', 'Tagihan dibuat.');
    }

    public function show(StudentInvoice $studentInvoice): View
    {
        $invoice = $studentInvoice->load([
            'student',
            'enrollment',
            'classroom',
            'academicYear',
            'semester',
            'billingPeriod',
            'feeType',
            'generator',
            'allocations.payment',
        ]);

        return view('finance.invoices.show', compact('invoice'));
    }

    public function edit(StudentInvoice $studentInvoice): View
    {
        return $this->form($studentInvoice);
    }

    public function update(
        StudentInvoiceRequest $request,
        StudentInvoice $studentInvoice,
        StudentInvoiceService $service,
    ): RedirectResponse {
        $invoice = $service->update($studentInvoice, $request->validated());

        return redirect()
            ->route('finance.student-invoices.show', $invoice)
            ->with('status', 'Tagihan diperbarui.');
    }

    public function cancel(
        StudentInvoiceCancellationRequest $request,
        StudentInvoice $studentInvoice,
        StudentInvoiceService $service,
    ): RedirectResponse {
        $service->cancel($studentInvoice, $request->validated('reason'));

        return redirect()
            ->route('finance.student-invoices.show', $studentInvoice)
            ->with('status', 'Tagihan dibatalkan.');
    }

    private function form(StudentInvoice $invoice): View
    {
        $students = Student::query()->orderBy('name')->get(['id', 'name']);
        $academicYears = AcademicYear::query()->latest('starts_on')->get();
        $semesters = Semester::query()->with('academicYear')->latest('starts_on')->get();
        $billingPeriods = BillingPeriod::query()
            ->where('is_active', true)
            ->with(['academicYear', 'semester'])
            ->latest('year')
            ->latest('month')
            ->get();
        $feeTypes = FeeType::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('finance.invoices.form', compact(
            'invoice',
            'students',
            'academicYears',
            'semesters',
            'billingPeriods',
            'feeTypes',
        ));
    }
}
