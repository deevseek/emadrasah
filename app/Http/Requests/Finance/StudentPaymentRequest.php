<?php

declare(strict_types=1);

namespace App\Http\Requests\Finance;

use App\Enums\Finance\InvoiceStatus;
use App\Enums\Finance\PaymentMethod;
use App\Models\Finance\StudentInvoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class StudentPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('student-payments.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'payment_date' => ['required', 'date', 'before_or_equal:today'],
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'total_amount' => ['required', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'allocations' => ['required', 'array', 'min:1'],
            'allocations.*.student_invoice_id' => [
                'required',
                'integer',
                'distinct',
                'exists:student_invoices,id',
            ],
            'allocations.*.amount' => ['required', 'numeric', 'gt:0'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $allocations = collect($this->input('allocations', []));
                $invoiceIds = $allocations->pluck('student_invoice_id')->filter()->all();
                $invoices = StudentInvoice::query()
                    ->whereIn('id', $invoiceIds)
                    ->get()
                    ->keyBy('id');

                $allocationTotal = 0.0;

                foreach ($allocations as $index => $allocation) {
                    $invoice = $invoices->get((int) ($allocation['student_invoice_id'] ?? 0));
                    $amount = (float) ($allocation['amount'] ?? 0);
                    $allocationTotal += $amount;

                    if ($invoice === null) {
                        continue;
                    }

                    if ((int) $invoice->student_id !== (int) $this->input('student_id')) {
                        $validator->errors()->add(
                            "allocations.{$index}.student_invoice_id",
                            'Tagihan tidak dimiliki siswa yang dipilih.',
                        );
                    }

                    if ($invoice->status === InvoiceStatus::Cancelled->value) {
                        $validator->errors()->add(
                            "allocations.{$index}.student_invoice_id",
                            'Tagihan yang dibatalkan tidak dapat dibayar.',
                        );
                    }

                    if ($amount - (float) $invoice->outstanding_amount > 0.005) {
                        $validator->errors()->add(
                            "allocations.{$index}.amount",
                            'Alokasi melebihi sisa tagihan.',
                        );
                    }
                }

                if (abs($allocationTotal - (float) $this->input('total_amount')) > 0.005) {
                    $validator->errors()->add(
                        'allocations',
                        'Total alokasi harus sama dengan total pembayaran.',
                    );
                }
            },
        ];
    }
}
