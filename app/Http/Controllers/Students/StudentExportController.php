<?php
declare(strict_types=1); namespace App\Http\Controllers\Students;
use App\Http\Controllers\Controller; use App\Services\Students\StudentExportService; use Illuminate\Http\Request; use Symfony\Component\HttpFoundation\BinaryFileResponse;
class StudentExportController extends Controller {public function __invoke(Request $request,StudentExportService $service):BinaryFileResponse{$query=StudentController::filtered($request);return response()->download($service->export($query,$request->user()),'data-siswa-'.now()->format('Y-m-d-Hi').'.xlsx')->deleteFileAfterSend();}}
