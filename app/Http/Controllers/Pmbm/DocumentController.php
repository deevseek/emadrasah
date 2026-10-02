<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pmbm;

use App\Http\Controllers\Controller;
use App\Models\Pmbm\PmbmApplicant;
use App\Services\Pmbm\PmbmDocumentService;
use Illuminate\Http\Response;

class DocumentController extends Controller
{
    public function receipt(PmbmApplicant $applicant, PmbmDocumentService $documents): Response
    {
        return response($documents->receipt($applicant), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="tanda-terima-'.$applicant->registration_number.'.pdf"']);
    }
    public function acceptance(PmbmApplicant $applicant, PmbmDocumentService $documents): Response
    {
        return response($documents->acceptanceLetter($applicant), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="surat-penerimaan-'.$applicant->registration_number.'.pdf"']);
    }
}
