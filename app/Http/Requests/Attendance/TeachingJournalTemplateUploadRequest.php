<?php

declare(strict_types=1);

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

final class TeachingJournalTemplateUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('teaching-journals.print') === true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'in:teacher,class'],
            'template' => ['required', 'file', 'mimes:docx', 'max:4096'],
        ];
    }
}
