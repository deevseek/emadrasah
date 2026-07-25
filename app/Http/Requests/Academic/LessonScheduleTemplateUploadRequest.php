<?php

declare(strict_types=1);

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;

final class LessonScheduleTemplateUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('schedules.print') === true;
    }

    public function rules(): array
    {
        return ['template' => ['required', 'file', 'mimes:docx', 'mimetypes:application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/zip', 'max:10240']];
    }

    public function messages(): array
    {
        return ['template.mimes' => 'Template harus berupa dokumen Word DOCX.', 'template.mimetypes' => 'Template harus berupa dokumen Word DOCX.'];
    }
}
