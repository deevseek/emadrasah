<?php
declare(strict_types=1); namespace App\Http\Controllers\Personnel; use App\Http\Controllers\Controller;use App\Services\Personnel\PersonnelExportService;use Illuminate\Http\Request;use Symfony\Component\HttpFoundation\BinaryFileResponse;
class PersonnelExportController extends Controller {public function __invoke(Request $r,PersonnelExportService $s):BinaryFileResponse{$path=$s->export(PersonnelController::filtered($r),$r->user());return response()->download($path,'data-personalia-'.now()->format('Y-m-d-Hi').'.xlsx')->deleteFileAfterSend();}}
