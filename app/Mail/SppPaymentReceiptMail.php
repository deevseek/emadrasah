<?php

declare(strict_types=1);
namespace App\Mail;
use App\Models\Finance\StudentPayment;
use App\Services\Finance\PaymentReceiptService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\{Attachment,Content,Envelope};
use Illuminate\Queue\SerializesModels;
class SppPaymentReceiptMail extends Mailable
{
    use Queueable, SerializesModels;
    public function __construct(public StudentPayment $payment) {}
    public function envelope(): Envelope
    {
        $s=app(PaymentReceiptService::class)->data($this->payment)['snapshot'];
        return new Envelope(subject:'Tanda Terima Pembayaran SPP - '.$s['student']['name'].' - '.$s['invoice']['period']);
    }
    public function content(): Content { return new Content(view:'emails.spp-payment-receipt',with:app(PaymentReceiptService::class)->data($this->payment)); }
    public function attachments(): array
    {
        $service=app(PaymentReceiptService::class);
        return [Attachment::fromData(fn()=>$service->pdf($this->payment),$service->filename($this->payment))->withMime('application/pdf')];
    }
}
