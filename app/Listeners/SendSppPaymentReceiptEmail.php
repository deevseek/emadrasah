<?php

declare(strict_types=1);
namespace App\Listeners;
use App\Events\StudentPaymentCompleted;
use App\Mail\SppPaymentReceiptMail;
use App\Models\Finance\PaymentReceiptDelivery;
use App\Models\GuardianProfile;
use App\Services\Finance\PaymentReceiptService;
use Illuminate\Support\Facades\{Log,Mail};
class SendSppPaymentReceiptEmail
{
    public function __construct(private readonly PaymentReceiptService $receipts) {}
    public function handle(StudentPaymentCompleted $event): void
    {
        try {
            $payment=$this->receipts->ensureReceipt($event->payment);
            $recipients=GuardianProfile::query()->with('user')->where('is_active',true)->whereHas('links',fn($q)=>$q->where('student_id',$payment->student_id)->where('can_view_finance',true))->get()
                ->filter(fn($g)=>$g->user?->is_active && filter_var($g->user?->email,FILTER_VALIDATE_EMAIL))->unique(fn($g)=>strtolower($g->user->email));
            foreach($recipients as $guardian) $this->send($payment,$guardian->user->email,$guardian->id);
        } catch (\Throwable $e) {
            Log::error('Pemrosesan tanda terima gagal setelah pembayaran berhasil.',['payment_number'=>$event->payment->payment_number]);
        }
    }
    public function send($payment,string $email,?int $guardianId=null,bool $retry=false): void
    {
        $delivery=PaymentReceiptDelivery::query()->firstOrCreate(['student_payment_id'=>$payment->id,'email'=>strtolower($email)],['guardian_id'=>$guardianId,'status'=>'pending']);
        if($delivery->status==='sent'&&!$retry)return;
        $delivery->update(['guardian_id'=>$guardianId,'status'=>'pending','attempt_count'=>$delivery->attempt_count+1,'last_error'=>null]);
        try { Mail::to($email)->send(new SppPaymentReceiptMail($payment)); $delivery->update(['status'=>'sent','sent_at'=>now(),'failed_at'=>null]); Log::info('Email tanda terima pembayaran terkirim.',['payment_number'=>$payment->payment_number,'delivery_id'=>$delivery->id]); }
        catch(\Throwable $e){$delivery->update(['status'=>'failed','failed_at'=>now(),'last_error'=>str($e->getMessage())->limit(1000)]);Log::warning('Email tanda terima pembayaran gagal.',['payment_number'=>$payment->payment_number,'delivery_id'=>$delivery->id]);}
    }
}
