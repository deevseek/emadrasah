<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Finance\StudentInvoice;
use App\Models\Finance\StudentVirtualAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class BriVirtualAccountService
{
    public function __construct(private BriConfigurationService $configuration) {}

    public function forInvoice(StudentInvoice $invoice): StudentVirtualAccount
    {
        if (! $this->configuration->brivaEnabled()) throw ValidationException::withMessages(['briva' => 'BRIVA tidak aktif.']);
        $partnerServiceId = (string) $this->configuration->partnerServiceId();
        if ($partnerServiceId === '') throw ValidationException::withMessages(['briva' => 'Partner Service ID belum diberikan BRI.']);
        $mode = $this->configuration->brivaMode();
        $subject = $mode === 'per_invoice' ? $invoice->id : $invoice->student_id;
        $prefix = preg_replace('/\D/', '', $this->configuration->customerNumberPrefix());
        $customerNo = $prefix.str_pad((string) $subject, 12 - strlen($prefix), '0', STR_PAD_LEFT);

        return DB::transaction(fn () => StudentVirtualAccount::query()->firstOrCreate(
            ['provider' => 'BRI', 'virtual_account_number' => $partnerServiceId.$customerNo],
            ['student_id' => $invoice->student_id, 'invoice_id' => $mode === 'per_invoice' ? $invoice->id : null, 'mode' => $mode, 'customer_number' => $customerNo, 'external_id' => (string) Str::uuid(), 'status' => 'active', 'expires_at' => $invoice->due_date?->endOfDay(), 'metadata' => ['partner_service_id' => $partnerServiceId]],
        ));
    }
}
