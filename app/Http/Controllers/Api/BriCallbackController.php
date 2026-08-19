<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Finance\StudentVirtualAccount;
use App\Services\Finance\BriConfigurationService;
use App\Services\Finance\BriPaymentNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class BriCallbackController extends Controller
{
    public function inquiry(Request $request, BriConfigurationService $config): JsonResponse
    {
        $data = $request->validate(['partnerServiceId' => ['required', 'string'], 'customerNo' => ['required', 'string'], 'virtualAccountNo' => ['required', 'string']]);
        abort_unless(hash_equals((string) $config->partnerServiceId(), $data['partnerServiceId']), 404);
        abort_unless(hash_equals($data['partnerServiceId'].$data['customerNo'], $data['virtualAccountNo']), 422);
        $va = StudentVirtualAccount::query()->with(['invoice', 'student'])->where('provider', 'BRI')->where('customer_number', $data['customerNo'])->where('virtual_account_number', $data['virtualAccountNo'])->firstOrFail();
        abort_if($va->expires_at?->isPast() || ! $va->invoice || in_array($va->invoice->status, ['paid', 'cancelled', 'draft'], true), 422);

        return response()->json(['virtualAccountData' => [
            'partnerServiceId' => $data['partnerServiceId'], 'customerNo' => $data['customerNo'], 'virtualAccountNo' => $data['virtualAccountNo'],
            'virtualAccountName' => $va->student->name, 'totalAmount' => ['value' => $va->invoice->outstanding_amount, 'currency' => 'IDR'],
            'additionalInfo' => ['invoiceNumber' => $va->invoice->invoice_number],
        ]]);
    }

    public function brivaPayment(Request $request, BriPaymentNotificationService $service): JsonResponse
    {
        $transaction = $service->briva($request->all());
        return response()->json(['responseCode' => config('bri.response_codes.briva_payment_success'), 'responseMessage' => 'Successful', 'virtualAccountData' => ['referenceNo' => $transaction->provider_reference, 'partnerReferenceNo' => $transaction->partner_reference]]);
    }

    public function qrisPayment(Request $request, BriPaymentNotificationService $service): JsonResponse
    {
        abort_unless(config('bri.response_codes.qris_notification_success'), 503, 'Response code QRIS onboarding belum dikonfigurasi.');
        $transaction = $service->qris($request->all());
        return response()->json(['responseCode' => config('bri.response_codes.qris_notification_success'), 'responseMessage' => 'Successful', 'referenceNo' => $transaction->provider_reference, 'partnerReferenceNo' => $transaction->partner_reference]);
    }
}
