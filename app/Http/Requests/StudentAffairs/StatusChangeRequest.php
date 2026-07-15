<?php

declare(strict_types=1);

namespace App\Http\Requests\StudentAffairs;
use App\Enums\StudentStatus; use Illuminate\Foundation\Http\FormRequest; use Illuminate\Validation\Rule;
class StatusChangeRequest extends FormRequest { public function authorize(): bool { return true; } public function rules(): array { return ['new_status'=>['required',Rule::enum(StudentStatus::class)],'effective_date'=>['required','date'],'reason'=>['nullable','string'],'destination_school'=>['nullable','string','max:255'],'document_number'=>['nullable','string','max:100']]; } }
