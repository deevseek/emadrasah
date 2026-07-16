<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Enums\Finance\InvoiceStatus;
use App\Models\Finance\BillingPeriod;
use App\Models\Finance\FeeType;
use App\Models\Finance\StudentInvoice;
use App\Models\StudentEnrollment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class StudentInvoiceService
{
    public function create(array $data, int $userId): StudentInvoice
    {
        return DB::transaction(function () use ($data, $userId): StudentInvoice {
            $context = $this->resolveContext($data);
            $this->ensureNoDuplicate($data);
            $amounts = $this->calculateAmounts($data);

            $invoice = StudentInvoice::create([
                ...$data,
                'student_enrollment_id' => $context['enrollment']->getKey(),
                'classroom_id' => $context['enrollment']->classroom_id,
                'due_on' => $data['due_on'] ?? $context['billingPeriod']?->due_on,
                'description' => $data['description'] ?? $context['feeType']->name,
                ...$amounts,
                'paid_amount' => 0,
                'outstanding_amount' => $amounts['final_amount'],
                'invoice_number' => app(DocumentNumberService::class)
                    ->next('INV', 'INV/{YEAR}/{MONTH}/{SEQ}'),
                'status' => InvoiceStatus::Unpaid->value,
                'generated_by' => $userId,
            ]);

            $this->refreshStatus($invoice);

            activity('student-finance')
                ->performedOn($invoice)
                ->causedBy(auth()->user())
                ->event('invoice.generated')
                ->log('Tagihan siswa dibuat');

            return $invoice->load(
                'student',
                'feeType',
                'billingPeriod',
                'academicYear',
                'semester',
            );
        });
    }

    public function update(StudentInvoice $invoice, array $data): StudentInvoice
    {
        return DB::transaction(function () use ($invoice, $data): StudentInvoice {
            $invoice = StudentInvoice::query()
                ->lockForUpdate()
                ->findOrFail($invoice->getKey());

            $this->ensureEditable($invoice);
            $context = $this->resolveContext($data);
            $this->ensureNoDuplicate($data, $invoice);
            $amounts = $this->calculateAmounts($data);

            $invoice->update([
                ...$data,
                'student_enrollment_id' => $context['enrollment']->getKey(),
                'classroom_id' => $context['enrollment']->classroom_id,
                'due_on' => $data['due_on'] ?? $context['billingPeriod']?->due_on,
                'description' => $data['description'] ?? $context['feeType']->name,
                ...$amounts,
                'outstanding_amount' => $amounts['final_amount'],
            ]);

            $this->refreshStatus($invoice->refresh());

            activity('student-finance')
                ->performedOn($invoice)
                ->causedBy(auth()->user())
                ->event('invoice.updated')
                ->log('Tagihan siswa diperbarui');

            return $invoice->refresh()->load(
                'student',
                'feeType',
                'billingPeriod',
                'academicYear',
                'semester',
            );
        });
    }

    public function cancel(StudentInvoice $invoice, string $reason): void
    {
        DB::transaction(function () use ($invoice, $reason): void {
            $invoice = StudentInvoice::query()
                ->lockForUpdate()
                ->findOrFail($invoice->getKey());

            if ($invoice->status === InvoiceStatus::Cancelled->value) {
                throw ValidationException::withMessages([
                    'invoice' => 'Tagihan sudah dibatalkan.',
                ]);
            }

            if (bccomp((string) $invoice->paid_amount, '0', 2) > 0) {
                throw ValidationException::withMessages([
                    'invoice' => 'Tagihan yang sudah menerima pembayaran tidak dapat dibatalkan. Batalkan pembayarannya terlebih dahulu.',
                ]);
            }

            $invoice->update(['status' => InvoiceStatus::Cancelled->value]);

            activity('student-finance')
                ->performedOn($invoice)
                ->causedBy(auth()->user())
                ->withProperties(['reason' => $reason])
                ->event('invoice.cancelled')
                ->log('Tagihan siswa dibatalkan');
        });
    }

    public function refreshStatus(StudentInvoice $invoice): void
    {
        if ($invoice->status === InvoiceStatus::Cancelled->value) {
            return;
        }

        $status = InvoiceStatus::Unpaid->value;

        if (bccomp((string) $invoice->paid_amount, '0', 2) > 0) {
            $status = bccomp((string) $invoice->outstanding_amount, '0', 2) <= 0
                ? InvoiceStatus::Paid->value
                : InvoiceStatus::PartiallyPaid->value;
        }

        if (
            $status === InvoiceStatus::Unpaid->value
            && $invoice->due_on?->isPast()
        ) {
            $status = InvoiceStatus::Overdue->value;
        }

        $invoice->forceFill(['status' => $status])->save();
    }

    private function resolveContext(array $data): array
    {
        $enrollment = StudentEnrollment::query()
            ->where('student_id', $data['student_id'])
            ->where('academic_year_id', $data['academic_year_id'])
            ->where('enrollment_status', 'active')
            ->first();

        if ($enrollment === null) {
            throw ValidationException::withMessages([
                'student_id' => 'Siswa tidak memiliki penempatan kelas aktif pada tahun ajaran tersebut.',
            ]);
        }

        $feeType = FeeType::query()->findOrFail($data['fee_type_id']);

        if (! $feeType->is_active) {
            throw ValidationException::withMessages([
                'fee_type_id' => 'Jenis tagihan tidak aktif.',
            ]);
        }

        $billingPeriod = null;

        if (! empty($data['billing_period_id'])) {
            $billingPeriod = BillingPeriod::query()
                ->where('is_active', true)
                ->find($data['billing_period_id']);

            if ($billingPeriod === null) {
                throw ValidationException::withMessages([
                    'billing_period_id' => 'Periode tagihan tidak aktif atau tidak ditemukan.',
                ]);
            }

            if ((int) $billingPeriod->academic_year_id !== (int) $data['academic_year_id']) {
                throw ValidationException::withMessages([
                    'billing_period_id' => 'Periode tagihan tidak sesuai tahun ajaran.',
                ]);
            }

            if (
                $billingPeriod->semester_id !== null
                && (int) $billingPeriod->semester_id !== (int) ($data['semester_id'] ?? 0)
            ) {
                throw ValidationException::withMessages([
                    'billing_period_id' => 'Periode tagihan tidak sesuai semester.',
                ]);
            }
        }

        return compact('enrollment', 'feeType', 'billingPeriod');
    }

    private function calculateAmounts(array $data): array
    {
        $original = (string) $data['original_amount'];
        $discount = (string) ($data['discount_amount'] ?? 0);
        $penalty = (string) ($data['penalty_amount'] ?? 0);
        $final = bcadd(bcsub($original, $discount, 2), $penalty, 2);

        if (bccomp($final, '0', 2) <= 0) {
            throw ValidationException::withMessages([
                'original_amount' => 'Nilai akhir tagihan harus lebih dari nol.',
            ]);
        }

        return [
            'original_amount' => $original,
            'discount_amount' => $discount,
            'penalty_amount' => $penalty,
            'final_amount' => $final,
        ];
    }

    private function ensureNoDuplicate(
        array $data,
        ?StudentInvoice $except = null,
    ): void {
        $duplicate = StudentInvoice::query()
            ->when($except, fn (Builder $query) => $query->whereKeyNot($except->getKey()))
            ->where('student_id', $data['student_id'])
            ->where('fee_type_id', $data['fee_type_id'])
            ->when(
                ! empty($data['billing_period_id']),
                fn (Builder $query) => $query->where('billing_period_id', $data['billing_period_id']),
                fn (Builder $query) => $query
                    ->whereNull('billing_period_id')
                    ->where('academic_year_id', $data['academic_year_id'])
                    ->when(
                        ! empty($data['semester_id']),
                        fn (Builder $query) => $query->where('semester_id', $data['semester_id']),
                        fn (Builder $query) => $query->whereNull('semester_id'),
                    ),
            )
            ->where('status', '!=', InvoiceStatus::Cancelled->value)
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'fee_type_id' => 'Tagihan yang sama sudah tersedia untuk siswa dan periode tersebut.',
            ]);
        }
    }

    private function ensureEditable(StudentInvoice $invoice): void
    {
        if (
            $invoice->status === InvoiceStatus::Cancelled->value
            || bccomp((string) $invoice->paid_amount, '0', 2) > 0
        ) {
            throw ValidationException::withMessages([
                'invoice' => 'Tagihan yang sudah dibayar atau dibatalkan tidak dapat diedit.',
            ]);
        }
    }
}
