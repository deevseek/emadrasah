<?php

declare(strict_types=1);

namespace App\Http\Requests\Rfid;

use Illuminate\Foundation\Http\FormRequest;

class RecordRfidAttendanceRequest extends FormRequest
{
    public function authorize(): bool { return $this->attributes->has('rfid_device'); }
    public function rules(): array { return ['uid' => ['required', 'string', 'max:100', 'regex:/[A-Fa-f0-9]/']]; }
}
