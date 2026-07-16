<?php

declare(strict_types=1);

namespace App\Http\Requests\StudentAffairs;

use App\Enums\StudentDocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudentDocumentRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return ['document_type' => ['required', Rule::enum(StudentDocumentType::class)], 'document_number' => ['nullable', 'string', 'max:100'], 'document_date' => ['nullable', 'date'], 'file' => ['required', 'file', 'mimetypes:application/pdf,image/jpeg,image/png,image/webp', 'extensions:pdf,jpg,jpeg,png,webp', 'max:4096'], 'issued_at' => ['nullable', 'date'], 'expires_at' => ['nullable', 'date', 'after_or_equal:issued_at'], 'notes' => ['nullable', 'string']];
    }
}
