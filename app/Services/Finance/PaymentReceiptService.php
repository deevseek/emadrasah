<?php

declare(strict_types=1);
namespace App\Services\Finance;
use App\Models\Finance\StudentPayment;
use App\Services\Foundation\SchoolProfileService;
use Illuminate\Support\Str;
class PaymentReceiptService
{
    public function __construct(private readonly SimplePdfRenderer $pdf, private readonly SchoolProfileService $profiles) {}
    public function ensureReceipt(StudentPayment $payment): StudentPayment
    {
        if ($payment->receipt_snapshot && $payment->receipt_verification_token) return $payment;
        $payment->loadMissing(['student.activeClassroomMembership.classroom','invoice.items.feeType','invoice.academicYear','invoice.semester','creator']);
        $profile = $this->profiles->current();
        $previous = (int) StudentPayment::query()->where('invoice_id',$payment->invoice_id)->where('status','succeeded')->where('paid_at','<=',$payment->paid_at)->where('id','!=',$payment->id)->sum('amount');
        $total = (int) ($payment->invoice?->total ?? $payment->amount); $current=(int)$payment->amount; $paid=$previous+$current; $remaining=max(0,$total-$paid);
        $period=$payment->invoice?->billing_month?->locale('id')->translatedFormat('F Y') ?? 'Tanpa periode';
        $snapshot = [
            'school'=>['name'=>$profile?->name ?? config('app.name'),'address'=>$profile?->display_address,'phone'=>$profile?->phone,'email'=>$profile?->email,'website'=>$profile?->website,'logo_path'=>$profile?->logo_path],
            'payment_number'=>$payment->payment_number,'paid_at'=>$payment->paid_at->toISOString(),'amount'=>$current,'method'=>$payment->payment_method,'bank_reference'=>$payment->bank_reference,
            'student'=>['name'=>$payment->student?->full_name,'nis'=>$payment->student?->nis,'nisn'=>$payment->student?->nisn,'class'=>$payment->student?->current_classroom_name],
            'invoice'=>['number'=>$payment->invoice?->invoice_number,'period'=>$period,'academic_year'=>$payment->invoice?->academicYear?->name,'semester'=>$payment->invoice?->semester?->name,'total'=>$total,'previous'=>$previous,'paid'=>$paid,'remaining'=>$remaining,
                'items'=>$payment->invoice?->items->map(fn($i)=>['description'=>$i->description ?: $i->feeType?->name,'amount'=>(int)$i->amount])->values()->all() ?? []],
            'status'=>$remaining===0?'LUNAS':'PEMBAYARAN SEBAGIAN','officer'=>$payment->creator?->name ?? 'Sistem',
        ];
        $payment->forceFill(['receipt_verification_token'=>Str::random(48),'receipt_snapshot'=>$snapshot,'receipt_generated_at'=>now()])->save();
        return $payment->refresh();
    }
    public function data(StudentPayment $payment): array
    {
        $payment=$this->ensureReceipt($payment); $snapshot=$payment->receipt_snapshot;
        return compact('payment','snapshot')+['verificationUrl'=>route('payment.verify',$payment->receipt_verification_token),'verificationCode'=>strtoupper(substr($payment->receipt_verification_token,0,12))];
    }
    public function pdf(StudentPayment $payment): string { return $this->pdf->render('finance.payments.receipt-pdf',$this->data($payment)); }
    public function filename(StudentPayment $payment): string
    {
        $s=$this->ensureReceipt($payment)->receipt_snapshot; return Str::of('Tanda-Terima-SPP-'.($s['student']['name']??'Siswa').'-'.($s['invoice']['period']??''))->ascii()->replaceMatches('/[^A-Za-z0-9-]+/','-')->trim('-').'.pdf';
    }
}
