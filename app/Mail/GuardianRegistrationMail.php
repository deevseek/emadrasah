<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\GuardianProfile;
use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GuardianRegistrationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public GuardianProfile $guardian,
        public Student $student,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Akun Portal Orang Tua Berhasil Terdaftar');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.guardian-registration');
    }
}
