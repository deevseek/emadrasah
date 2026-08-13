<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rfid\RecordRfidAttendanceRequest;
use App\Services\Academic\RfidAttendanceService;
use Illuminate\Http\JsonResponse;

class RfidAttendanceController extends Controller
{
    public function __invoke(RecordRfidAttendanceRequest $request, RfidAttendanceService $service): JsonResponse
    {
        $result = $service->record($request->string('uid')->toString(), $request->attributes->get('rfid_device'));
        $status = $result['http']; unset($result['http']);
        return response()->json($result, $status);
    }
}
