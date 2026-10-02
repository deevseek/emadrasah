<?php

declare(strict_types=1);

namespace App\Http\Controllers\Students;

use App\Http\Controllers\Controller;
use App\Http\Requests\Students\ExportStudentCardsRequest;
use App\Services\Students\StudentCardExportService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StudentCardExportController extends Controller
{
    public function __invoke(ExportStudentCardsRequest $request, StudentCardExportService $service): BinaryFileResponse
    {
        $path = $service->export(StudentController::filtered($request), $request->user());

        return response()
            ->download($path, 'data-kartu-siswa-'.now()->format('Y-m-d-Hi').'.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->deleteFileAfterSend();
    }
}
