<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pmbm;

use App\Http\Controllers\Controller;
use App\Models\Pmbm\PmbmApplicant;
use App\Services\Pmbm\PmbmDocumentService;
use Illuminate\Http\Response;
use App\Models\Pmbm\PmbmApplicantDocument;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function applicantDocument(PmbmApplicant $applicant,PmbmApplicantDocument $document):StreamedResponse
    {
        abort_unless($document->applicant_id===$applicant->id,404);
        return Storage::disk('local')->download($document->path,$document->original_name);
    }
    public function receipt(PmbmApplicant $applicant, PmbmDocumentService $documents): Response
    {
        return response($documents->receipt($applicant), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="tanda-terima-'.$applicant->registration_number.'.pdf"']);
    }
    public function acceptance(PmbmApplicant $applicant, PmbmDocumentService $documents): Response
    {
        return response($documents->acceptanceLetter($applicant), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="surat-penerimaan-'.$applicant->registration_number.'.pdf"']);
    }
}
