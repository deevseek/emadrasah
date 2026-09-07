<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\OutgoingEmailStatus;
use App\Mail\ManualServiceEmail;
use App\Models\OutgoingEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendOutgoingEmail implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public int $outgoingEmailId) {}

    public function handle(): void
    {
        $email = OutgoingEmail::query()->find($this->outgoingEmailId);
        if (! $email || $email->status !== OutgoingEmailStatus::Queued) {
            return;
        }

        try {
            $pending = Mail::to($email->to_addresses);
            if ($email->cc_addresses) {
                $pending->cc($email->cc_addresses);
            }
            if ($email->bcc_addresses) {
                $pending->bcc($email->bcc_addresses);
            }
            $sentMessage = $pending->send(new ManualServiceEmail($email));
            $email->update([
                'status' => OutgoingEmailStatus::Sent,
                'provider_message_id' => $sentMessage?->getMessageId(),
                'error_message' => null,
                'sent_at' => now(),
            ]);
            activity('pelayanan-email')->causedBy($email->user)->performedOn($email)->log('Email pelayanan berhasil dikirim.');
        } catch (Throwable) {
            $email->update([
                'status' => OutgoingEmailStatus::Failed,
                'provider_message_id' => null,
                'error_message' => 'Layanan email tidak dapat menyelesaikan pengiriman. Silakan coba kembali atau hubungi administrator.',
                'sent_at' => null,
            ]);
            activity('pelayanan-email')->causedBy($email->user)->performedOn($email)->log('Pengiriman email pelayanan gagal.');
        }
    }
}
