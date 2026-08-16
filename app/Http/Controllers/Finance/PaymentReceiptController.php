<?php

declare(strict_types=1);
namespace App\Http\Controllers\Finance;
use App\Http\Controllers\Controller;
use App\Listeners\SendSppPaymentReceiptEmail;
use App\Models\Finance\StudentPayment;
use App\Services\Finance\PaymentReceiptService;
use Illuminate\Http\{RedirectResponse,Response};
use Illuminate\View\View;
class PaymentReceiptController extends Controller
{
    public function show(StudentPayment $payment,PaymentReceiptService $service):View{return view('finance.payments.receipt',$service->data($payment));}
    public function pdf(StudentPayment $payment,PaymentReceiptService $service):Response{return response($service->pdf($payment),200,['Content-Type'=>'application/pdf','Content-Disposition'=>'attachment; filename="'.$service->filename($payment).'"']);}
    public function resend(StudentPayment $payment,SendSppPaymentReceiptEmail $listener):RedirectResponse{
        $deliveries=$payment->receiptDeliveries()->get();
        if($deliveries->isEmpty()) return back()->with('success','Belum ada alamat email orang tua/wali yang memenuhi syarat.');
        foreach($deliveries as $delivery)$listener->send($payment,$delivery->email,$delivery->guardian_id,true);
        return back()->with('success','Pengiriman ulang email tanda terima telah diproses.');
    }
}
