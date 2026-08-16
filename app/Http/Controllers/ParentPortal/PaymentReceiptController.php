<?php

declare(strict_types=1);
namespace App\Http\Controllers\ParentPortal;
use App\Http\Controllers\Controller;
use App\Models\Finance\StudentPayment;
use App\Services\Finance\PaymentReceiptService;
use App\Services\ParentPortal\GuardianAccessService;
use Illuminate\Http\{Request,Response};
class PaymentReceiptController extends Controller
{
    public function __invoke(Request $request,StudentPayment $payment,GuardianAccessService $access,PaymentReceiptService $service):Response
    {
        $access->student($request->user(),$payment->student_id,'can_view_finance');
        return response($service->pdf($payment),200,['Content-Type'=>'application/pdf','Content-Disposition'=>'attachment; filename="'.$service->filename($payment).'"']);
    }
}
