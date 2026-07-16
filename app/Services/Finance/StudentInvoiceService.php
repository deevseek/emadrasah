<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Enums\Finance\InvoiceStatus;
use App\Models\Finance\FeeType;
use App\Models\Finance\StudentInvoice;
use App\Models\StudentEnrollment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentInvoiceService
{
    public function create(array $data, int $userId): StudentInvoice
    {
        return DB::transaction(function () use ($data, $userId): StudentInvoice {
            $enrollment = StudentEnrollment::query()
                ->where('student_id', $data['student_id'])
                ->where('academic_year_id', $data['academic_year_id'])
                ->where('enrollment_status', 'active')
                ->firstOrFail();
            $feeType = FeeType::findOrFail($data['fee_type_id']);
            throw_if(! $feeType->is_active, ValidationException::withMessages(['fee_type_id' => 'Jenis tagihan tidak aktif.']));
            $original = (string) $data['original_amount'];
            $discount = (string) ($data['discount_amount'] ?? 0);
            $penalty = (string) ($data['penalty_amount'] ?? 0);
            $final = bcadd(bcsub($original, $discount, 2), $penalty, 2);
            throw_if(bccomp($final, '0', 2) < 0, ValidationException::withMessages(['original_amount' => 'Nilai akhir tidak boleh negatif.']));
            $invoice = StudentInvoice::firstOrCreate([
                'student_id' => $data['student_id'],
                'fee_type_id' => $data['fee_type_id'],
                'billing_period_id' => $data['billing_period_id'] ?? null,
            ], [
                'student_enrollment_id' => $enrollment->id,
                'classroom_id' => $enrollment->classroom_id,
                'academic_year_id' => $data['academic_year_id'],
                'semester_id' => $data['semester_id'] ?? null,
                'invoice_number' => app(DocumentNumberService::class)->next('INV', 'INV/{YEAR}/{MONTH}/{SEQ}'),
                'description' => $data['description'] ?? $feeType->name,
                'original_amount' => $original,
                'discount_amount' => $discount,
                'penalty_amount' => $penalty,
                'final_amount' => $final,
                'paid_amount' => 0,
                'outstanding_amount' => $final,
                'due_on' => $data['due_on'] ?? null,
                'status' => InvoiceStatus::Unpaid->value,
                'generated_by' => $userId,
            ]);
            activity('student-finance')->performedOn($invoice)->causedBy(auth()->user())->event('invoice.generated')->log('Tagihan siswa dibuat');

            return $invoice;
        });
    }

    public function refreshStatus(StudentInvoice $invoice): void
    {
        $status = InvoiceStatus::Unpaid->value;
        if (bccomp((string) $invoice->paid_amount, '0', 2) > 0) {
            $status = bccomp((string) $invoice->outstanding_amount, '0', 2) <= 0 ? InvoiceStatus::Paid->value : InvoiceStatus::PartiallyPaid->value;
        }
        if ($status === InvoiceStatus::Unpaid->value && $invoice->due_on && $invoice->due_on->isPast()) {
            $status = InvoiceStatus::Overdue->value;
        }
        $invoice->forceFill(['status' => $status])->save();
    }
}
