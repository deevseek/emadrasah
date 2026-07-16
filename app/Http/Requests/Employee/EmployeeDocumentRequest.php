<?php

declare(strict_types=1);
namespace App\Http\Requests\Employee;
use App\Enums\EmployeeDocumentType; use Illuminate\Foundation\Http\FormRequest; use Illuminate\Validation\Rule;
class EmployeeDocumentRequest extends FormRequest
{ public function authorize(): bool { return $this->user()?->can('employees.manage-documents') ?? false; } public function rules(): array { return ['type'=>['required',Rule::enum(EmployeeDocumentType::class)],'document_number'=>['nullable','string','max:100'],'document_date'=>['nullable','date','before_or_equal:today'],'description'=>['nullable','string'],'file'=>[$this->isMethod('put')?'nullable':'required','file','mimes:pdf,jpg,jpeg,png,webp','max:4096']]; } }
