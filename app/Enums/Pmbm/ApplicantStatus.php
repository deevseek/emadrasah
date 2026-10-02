<?php

declare(strict_types=1);

namespace App\Enums\Pmbm;

enum ApplicantStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case DocumentVerification = 'document_verification';
    case Verified = 'verified';
    case InitialPaymentPending = 'initial_payment_pending';
    case InitialPaymentVerified = 'initial_payment_verified';
    case FittingScheduled = 'fitting_scheduled';
    case FittingCompleted = 'fitting_completed';
    case InterviewScheduled = 'interview_scheduled';
    case Interviewed = 'interviewed';
    case ChildInterviewScheduled = 'child_interview_scheduled';
    case ChildInterviewed = 'child_interviewed';
    case ObservationScheduled = 'observation_scheduled';
    case Observed = 'observed';
    case DecisionPending = 'decision_pending';
    case Accepted = 'accepted';
    case Waitlisted = 'waitlisted';
    case Rejected = 'rejected';
    case RegistrationPaymentPending = 'registration_payment_pending';
    case RegistrationPaymentPartial = 'registration_payment_partial';
    case Registered = 'registered';
    case Enrolled = 'enrolled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf', self::Submitted => 'Pendaftaran diterima', self::DocumentVerification => 'Verifikasi berkas', self::Verified => 'Berkas terverifikasi',
            self::InitialPaymentPending => 'Menunggu pembayaran awal', self::InitialPaymentVerified => 'Pembayaran awal terverifikasi', self::FittingScheduled => 'Fitting dijadwalkan', self::FittingCompleted => 'Fitting selesai',
            self::InterviewScheduled => 'Wawancara orang tua dijadwalkan', self::Interviewed => 'Wawancara orang tua selesai', self::ChildInterviewScheduled => 'Wawancara anak dijadwalkan', self::ChildInterviewed => 'Wawancara anak selesai', self::ObservationScheduled => 'Observasi dijadwalkan', self::Observed => 'Observasi selesai', self::DecisionPending => 'Menunggu keputusan', self::Accepted => 'Diterima', self::Waitlisted => 'Daftar cadangan', self::Rejected => 'Belum dapat diterima',
            self::RegistrationPaymentPending => 'Menunggu daftar ulang', self::RegistrationPaymentPartial => 'Pembayaran daftar ulang sebagian', self::Registered => 'Daftar ulang selesai', self::Enrolled => 'Menjadi siswa',
        };
    }
}
