<?php

declare(strict_types=1);

namespace App\Http\Requests\Consultation;

use Illuminate\Foundation\Http\FormRequest;

class SendConsultationMessageRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }

    public function rules(): array
    {
        return ['message' => ['required', 'string', 'max:4000']];
    }

    public function attributes(): array { return ['message' => 'pesan konsultasi']; }
}
