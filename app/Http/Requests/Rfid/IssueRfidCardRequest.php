<?php

declare(strict_types=1);

namespace App\Http\Requests\Rfid;

use App\Enums\RfidWriteOperation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IssueRfidCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        $requestedOperation = RfidWriteOperation::tryFrom((string) $this->input('operation'));
        $permission = ($requestedOperation?->replacesExisting() ?? $this->boolean('replace')) ? 'rfid-card.replace' : 'rfid-card.issue';

        return $this->user()?->can($permission) === true;
    }

    public function rules(): array
    {
        return [
            'operation' => ['sometimes', Rule::enum(RfidWriteOperation::class)],
            'replace' => ['sometimes', 'boolean'],
        ];
    }

    public function operation(): RfidWriteOperation
    {
        if ($this->filled('operation')) {
            return RfidWriteOperation::from((string) $this->input('operation'));
        }

        return $this->boolean('replace') ? RfidWriteOperation::Rewrite : RfidWriteOperation::Write;
    }
}
