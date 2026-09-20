<?php

declare(strict_types=1);
namespace App\Mail;
use App\Models\Pmbm\PmbmApplicant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\{Content, Envelope};
use Illuminate\Queue\SerializesModels;
class PmbmRegistrationReceiptMail extends Mailable { use Queueable, SerializesModels; public function __construct(public PmbmApplicant $applicant) {} public function envelope(): Envelope { return new Envelope(subject: 'Tanda Terima Pendaftaran PMBM '.$this->applicant->registration_number); } public function content(): Content { return new Content(view: 'emails.pmbm-registration-receipt'); } }
