<?php

declare(strict_types=1);

namespace App\Http\Requests\Rfid;

use App\Enums\RfidAttendanceResultCode;
use App\Models\RfidAttendanceEvent;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;

class RecordRfidAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->attributes->has('rfid_device');
    }

    public function rules(): array
    {
        return [
            'card_token' => ['required', 'string', 'size:32', 'regex:/^[A-Fa-f0-9]{32}$/'],
            'uid' => ['nullable', 'string', 'max:100', 'regex:/[A-Fa-f0-9]/'],
        ];
    }

    protected function failedValidation(Validator $validator): never
    {
        $message = 'Data kartu RFID tidak valid.';
        $event = RfidAttendanceEvent::create([
            'rfid_device_id' => $this->attributes->get('rfid_device')?->id,
            'result_code' => RfidAttendanceResultCode::CardNotProvisioned->value,
            'success' => false,
            'message' => $message,
            'scanned_at' => now(),
        ]);
        Log::info('RFID live event emitted.', [
            'event_id' => $event->id,
            'device_id' => $this->attributes->get('rfid_device')?->device_id,
            'code' => RfidAttendanceResultCode::CardNotProvisioned->value,
            'student_id' => null,
        ]);

        throw new HttpResponseException(response()->json([
            'success' => false,
            'code' => RfidAttendanceResultCode::CardNotProvisioned->value,
            'message' => $message,
            'event_id' => $event->id,
        ], 422));
    }
}
