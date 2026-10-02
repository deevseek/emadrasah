<?php

declare(strict_types=1);

namespace App\Services\Pmbm;

use App\Enums\Pmbm\ApplicantStatus;
use App\Models\{SchoolProfile};
use App\Models\Pmbm\PmbmApplicant;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Validation\ValidationException;

class PmbmDocumentService
{
    public function receipt(PmbmApplicant $applicant): string
    {
        return Pdf::loadView('pmbm.documents.receipt', $this->data($applicant))->output();
    }

    public function acceptanceLetter(PmbmApplicant $applicant): string
    {
        if ($applicant->status !== ApplicantStatus::Accepted && ! in_array($applicant->status, [ApplicantStatus::RegistrationPaymentPending, ApplicantStatus::RegistrationPaymentPartial, ApplicantStatus::Registered, ApplicantStatus::Enrolled], true)) {
            throw ValidationException::withMessages(['document' => 'Surat penerimaan hanya tersedia bagi calon murid yang telah diterima.']);
        }
        abort_unless($applicant->decision?->approved_at, 404);

        return Pdf::loadView('pmbm.documents.acceptance-letter', $this->data($applicant))->output();
    }

    private function data(PmbmApplicant $applicant): array
    {
        return ['applicant' => $applicant->loadMissing(['academicYear', 'documents', 'decision']), 'school' => SchoolProfile::query()->first(), 'paymentSummary' => app(PmbmPaymentService::class)->summary($applicant), 'printedAt' => now()];
    }
}
