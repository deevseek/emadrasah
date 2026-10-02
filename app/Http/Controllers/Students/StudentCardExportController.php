<?php

declare(strict_types=1);

namespace App\Http\Controllers\Students;

use App\Http\Controllers\Controller;
use App\Http\Requests\Students\ExportStudentsRequest;
use App\Services\Students\StudentCardExportService;
use App\Services\Students\StudentExportFilenameService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StudentCardExportController extends Controller
{
    public function __invoke(
        ExportStudentsRequest $request,
        StudentCardExportService $service,
        StudentExportFilenameService $filenameService,
    ): BinaryFileResponse {
        $path = $service->export(StudentController::filtered($request), $request->user());
        $filename = $filenameService->make('data-kartu-siswa', $request->validated('classroom_label'));

        return response()
            ->download($path, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->deleteFileAfterSend();
    }
}
