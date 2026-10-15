<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rfid\RecordRfidAttendanceRequest;
use App\Services\Academic\RfidAttendanceService;
use App\Services\Rfid\RfidAttendanceRequestService;
use Illuminate\Http\JsonResponse;

class RfidAttendanceController extends Controller
{
    public function __invoke(RecordRfidAttendanceRequest $request, RfidAttendanceService $service, RfidAttendanceRequestService $requests): JsonResponse
    {
        $device = $request->attributes->get('rfid_device');
        $result = $requests->execute($device, $request->all(), fn (): array =>
            $service->record($request->string('card_token')->toString(), $request->string('uid')->toString(), $device));
        $status = $result['http']; unset($result['http']);
        return response()->json($result, $status);
    }
}
