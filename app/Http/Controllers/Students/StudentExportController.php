<?php

declare(strict_types=1);

namespace App\Http\Controllers\Students;

use App\Http\Controllers\Controller;
use App\Http\Requests\Students\ExportStudentsRequest;
use App\Services\Students\StudentExportFilenameService;
use App\Services\Students\StudentExportService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StudentExportController extends Controller
{
    public function __invoke(
        ExportStudentsRequest $request,
        StudentExportService $service,
        StudentExportFilenameService $filenameService,
    ): BinaryFileResponse {
        $query = StudentController::filtered($request);
        $filename = $filenameService->make('data-siswa', $request->validated('classroom_label'));

        return response()
            ->download($service->export($query, $request->user()), $filename)
            ->deleteFileAfterSend();
    }
}
