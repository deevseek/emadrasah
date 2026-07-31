<?php
declare(strict_types=1);namespace App\Http\Controllers\Subjects;use App\Http\Controllers\Controller;use App\Services\Subjects\SubjectExportService;use Symfony\Component\HttpFoundation\BinaryFileResponse;
class SubjectExportController extends Controller {public function __invoke(SubjectExportService $service):BinaryFileResponse{abort_unless(request()->user()->can('subjects.export'),403);return $service->download(request()->user());}}
