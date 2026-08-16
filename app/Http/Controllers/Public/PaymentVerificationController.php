<?php

declare(strict_types=1);
namespace App\Http\Controllers\Public;
use App\Http\Controllers\Controller;
use App\Models\Finance\StudentPayment;
use App\Services\Finance\PaymentReceiptService;
use Illuminate\View\View;
class PaymentVerificationController extends Controller
{
    public function __invoke(string $token,PaymentReceiptService $service):View
    {
        $payment=StudentPayment::query()->where('receipt_verification_token',$token)->first();
        return view('public.payment-verification',$payment?($service->data($payment)+['valid'=>true]):['valid'=>false]);
    }
}
