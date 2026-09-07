<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\OutgoingEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ManualServiceEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public OutgoingEmail $outgoingEmail) {}

    public function envelope(): Envelope
    {
        $address = (string) config('mail.from.address');
        $name = (string) config('mail.from.name');

        return new Envelope(
            from: new Address($address, $name),
            replyTo: [new Address($address, $name)],
            subject: $this->outgoingEmail->subject,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.manual-service');
    }

    public function attachments(): array
    {
        return collect($this->outgoingEmail->attachments ?? [])
            ->map(fn (array $file): Attachment => Attachment::fromStorageDisk('local', $file['path'])
                ->as($file['original_name'])
                ->withMime($file['mime_type']))
            ->all();
    }
}
