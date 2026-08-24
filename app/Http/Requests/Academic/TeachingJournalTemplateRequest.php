<?php

declare(strict_types=1);

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class TeachingJournalTemplateRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('teaching-journals.manage') ?? false; }
    public function rules(): array { return ['name' => ['required', 'string', 'max:100'], 'template' => ['required', File::types(['docx'])->max('10mb')]]; }
}
