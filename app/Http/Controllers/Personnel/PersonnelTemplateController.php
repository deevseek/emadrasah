<?php
declare(strict_types=1); namespace App\Http\Controllers\Personnel; use App\Http\Controllers\Controller;use App\Services\Personnel\PersonnelExportService;use Symfony\Component\HttpFoundation\BinaryFileResponse;
class PersonnelTemplateController extends Controller {public function __invoke(PersonnelExportService $s):BinaryFileResponse{return response()->download($s->template(),'template-data-personalia.xlsx')->deleteFileAfterSend();}}
