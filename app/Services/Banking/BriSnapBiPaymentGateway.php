<?php

declare(strict_types=1);

namespace App\Services\Banking;

use App\Contracts\Banking\BankPaymentGateway;
use App\Services\Finance\BriConfigurationService;
use Illuminate\Support\Str;
use RuntimeException;

class BriSnapBiPaymentGateway implements BankPaymentGateway
{
    public function __construct(private BriConfigurationService $configuration) {}

    public function createVirtualAccount(array $request): array
    {
        if (! $this->configuration->brivaEnabled()) throw new RuntimeException('BRIVA belum diaktifkan.');
        $setting = $this->configuration->setting();
        $partnerServiceId = (string) ($setting?->partner_service_id ?: config('bri.briva.partner_service_id', config('bri.briva.institution_code')));
        if ($partnerServiceId === '') throw new RuntimeException('Partner Service ID BRIVA belum dikonfigurasi.');

        $customerNo = preg_replace('/\D+/', '', (string) ($request['customer_no'] ?? $request['student_id'] ?? '')) ?: '';
        $prefix = (string) ($setting?->customer_number_prefix ?? config('bri.briva.customer_number_prefix', ''));
        $customerNo = $prefix.$customerNo;
        if ($customerNo === '' || strlen($customerNo) > 20) throw new RuntimeException('Customer number BRIVA tidak valid.');

        $service = str_pad(trim($partnerServiceId), 8, ' ', STR_PAD_LEFT);
        return ['status'=>'active','external_id'=>(string)($request['external_id']??Str::uuid()),'customer_no'=>$customerNo,'partner_service_id'=>$service,'virtual_account_number'=>$service.$customerNo];
    }

    public function transfer(array $request): array { throw new RuntimeException('Gunakan BankTransferGateway untuk transfer dana.'); }
    public function inquire(string $id): array { return ['status'=>'active','external_id'=>$id]; }
}
