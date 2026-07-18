<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\FeeType;
use App\Models\Finance\StudentInvoice;
use App\Models\AcademicYear;
use App\Models\Classroom;
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

        $route = $request->routeIs('student-finance.*') ? 'student-finance.bills.show' : 'finance.student-invoices.show';
        return redirect()->route($route, $invoice)->with('status', 'Tagihan dibuat.');
    }
    public function edit(StudentInvoice $studentInvoice): View
    {
        abort_if($studentInvoice->paid_amount > 0 || $studentInvoice->status !== 'draft', 403);

        return view('finance.invoices.form', ['invoice' => $studentInvoice, 'students' => Student::orderBy('name')->get(), 'feeTypes' => FeeType::where('is_active', true)->get()]);
    }

    public function update(Request $request, StudentInvoice $studentInvoice, StudentInvoiceService $service): RedirectResponse
    {
        abort_if($studentInvoice->paid_amount > 0 || $studentInvoice->status !== 'draft', 403);
        $studentInvoice->update($request->validate(['description' => ['nullable', 'string'], 'due_on' => ['nullable', 'date']]));

        return redirect()->route('student-finance.bills.show', $studentInvoice)->with('status', 'Draft tagihan diperbarui.');
    }

    public function print(StudentInvoice $studentInvoice): View
    {
        return view('finance.invoices.print', ['invoice' => $studentInvoice->load('student.activeEnrollment.classroom', 'feeType')]);
    }

    public function cancel(Request $request, StudentInvoice $studentInvoice): RedirectResponse
    {
        abort_if($studentInvoice->paid_amount > 0, 422, 'Tagihan dengan pembayaran tidak dapat dibatalkan langsung.');
        $studentInvoice->update(['status' => 'cancelled', 'cancelled_by' => $request->user()->id, 'cancelled_at' => now(), 'cancellation_reason' => $request->validate(['reason' => ['required', 'string', 'min:5']])['reason']]);

        return back()->with('status', 'Tagihan dibatalkan.');
    }

    public function bulk(): View
    {
        return view('finance.invoices.bulk', ['academicYears' => AcademicYear::latest()->get(), 'classrooms' => Classroom::where('is_active', true)->orderBy('name')->get(), 'feeTypes' => FeeType::where('is_active', true)->get()]);
    }

    public function preview(Request $request): View
    {
        $data = $request->validate(['academic_year_id' => ['required', 'exists:academic_years,id'], 'classroom_id' => ['nullable', 'exists:classrooms,id'], 'fee_type_id' => ['required', 'exists:fee_types,id'], 'billing_month' => ['required', 'integer', 'between:1,12'], 'billing_year' => ['required', 'integer', 'min:2000'], 'original_amount' => ['required', 'numeric', 'min:1'], 'due_on' => ['required', 'date']]);
        $students = Student::where('is_active', true)->whereHas('enrollments', fn ($query) => $query->where('academic_year_id', $data['academic_year_id'])->when($data['classroom_id'] ?? null, fn ($q, $classroom) => $q->where('classroom_id', $classroom))->where('enrollment_status', 'active'))->with('activeEnrollment.classroom')->get();

        return view('finance.invoices.preview', ['data' => $data, 'students' => $students, 'feeType' => FeeType::find($data['fee_type_id'])]);
    }

    public function generate(Request $request, StudentInvoiceService $service): RedirectResponse
    {
        $data = $request->validate(['academic_year_id' => ['required', 'exists:academic_years,id'], 'classroom_id' => ['nullable', 'exists:classrooms,id'], 'fee_type_id' => ['required', 'exists:fee_types,id'], 'billing_month' => ['required', 'integer', 'between:1,12'], 'billing_year' => ['required', 'integer', 'min:2000'], 'original_amount' => ['required', 'numeric', 'min:1'], 'due_on' => ['required', 'date'], 'description' => ['nullable', 'string']]);
        $students = Student::where('is_active', true)->whereHas('enrollments', fn ($query) => $query->where('academic_year_id', $data['academic_year_id'])->when($data['classroom_id'] ?? null, fn ($q, $classroom) => $q->where('classroom_id', $classroom))->where('enrollment_status', 'active'))->with('activeEnrollment')->get();
        $created = 0; $skipped = 0;
        foreach ($students as $student) {
            $exists = StudentInvoice::where('student_id', $student->id)->where('fee_type_id', $data['fee_type_id'])->where('billing_period_id', null)->whereMonth('created_at', $data['billing_month'])->whereYear('created_at', $data['billing_year'])->exists();
            if ($exists) { $skipped++; continue; }
            $service->create(['student_id' => $student->id, 'academic_year_id' => $data['academic_year_id'], 'fee_type_id' => $data['fee_type_id'], 'original_amount' => $data['original_amount'], 'discount_amount' => 0, 'penalty_amount' => 0, 'due_on' => $data['due_on'], 'description' => $data['description'] ?? 'Tagihan SPP bulanan'], (int) $request->user()->id);
            $created++;
        }

        return redirect()->route('student-finance.bills.index')->with('status', "Generate selesai: {$created} dibuat, {$skipped} dilewati.");
    }

}
