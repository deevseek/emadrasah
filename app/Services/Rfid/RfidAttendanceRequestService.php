<?php

declare(strict_types=1);

namespace App\Services\Rfid;

use App\Models\RfidDevice;
use Closure;
use Illuminate\Support\Facades\DB;

class RfidAttendanceRequestService
{
    public static function validRequestId(mixed $value): bool
    {
        return is_string($value) && preg_match('/^[A-Za-z0-9][A-Za-z0-9._:-]{0,127}$/D', $value) === 1;
    }

    public function execute(RfidDevice $device, array $payload, Closure $record): array
    {
        $requestId = $payload['request_id'] ?? null;
        if (! self::validRequestId($requestId)) {
            return $record();
        }

        // Urutan tetap dan nilai sesuai payload yang diterima Laravel. Token
        // hanya menjadi digest; tidak disimpan atau ditulis ke log.
        $hash = hash('sha256', json_encode([
            'uid' => $payload['uid'] ?? null,
            'card_token' => $payload['card_token'] ?? null,
            'scanned_at' => $payload['scanned_at'] ?? null,
        ], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($device, $requestId, $payload, $hash, $record): array {
            // Kunci baris yang sudah ada sebelum membaca key yang belum ada.
            // Retry bersamaan menunggu commit/rollback transaksi pertama.
            RfidDevice::query()->lockForUpdate()->findOrFail($device->id);
            $stored = DB::table('rfid_attendance_requests')
                ->where('rfid_device_id', $device->id)->where('request_id', $requestId)
                ->lockForUpdate()->first();
            if ($stored) {
                if (! hash_equals($stored->payload_hash, $hash)) {
                    return ['http' => 409, 'success' => false, 'code' => 'REQUEST_ID_CONFLICT',
                        'message' => 'Request ID sudah digunakan untuk payload berbeda.'];
                }

                return json_decode($stored->response, true, 512, JSON_THROW_ON_ERROR);
            }

            $result = $record();
            $scannedAt = $payload['scanned_at'] ?? null;
            DB::table('rfid_attendance_requests')->insert([
                'rfid_device_id' => $device->id,
                'request_id' => $requestId,
                'payload_hash' => $hash,
                'device_scanned_at' => is_string($scannedAt) && strlen($scannedAt) <= 64 ? $scannedAt : null,
                'response' => json_encode($result, JSON_THROW_ON_ERROR),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $result;
        }, 3);
    }
}
