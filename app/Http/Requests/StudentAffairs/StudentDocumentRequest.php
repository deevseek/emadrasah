<?php

declare(strict_types=1);

namespace App\Http\Requests\StudentAffairs;
use App\Enums\StudentDocumentType; use Illuminate\Foundation\Http\FormRequest; use Illuminate\Validation\Rule;
class StudentDocumentRequest extends FormRequest { public function authorize(): bool { return true; } public function rules(): array { return ['document_type'=>['required',Rule::enum(StudentDocumentType::class)],'document_number'=>['nullable','string','max:100'],'file'=>['required','file','mimes:pdf,jpg,jpeg,png','extensions:pdf,jpg,jpeg,png','max:4096'],'issued_at'=>['nullable','date'],'expires_at'=>['nullable','date','after_or_equal:issued_at'],'notes'=>['nullable','string']]; } }
