<?php
declare(strict_types=1); namespace App\Http\Controllers\Students;
use App\Http\Controllers\Controller; use App\Services\Students\StudentExportService; use Symfony\Component\HttpFoundation\BinaryFileResponse;
class StudentTemplateController extends Controller {public function __invoke(StudentExportService $service):BinaryFileResponse{return response()->download($service->template(),'template-import-data-siswa.xlsx')->deleteFileAfterSend();}}
