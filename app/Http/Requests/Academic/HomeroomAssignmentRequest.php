<?php

declare(strict_types=1);

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;

class HomeroomAssignmentRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['employee_id'=>['required','exists:employees,id'],'started_at'=>['required','date'],'reason'=>['nullable','string','max:1000'],'notes'=>['nullable','string','max:1000']]; }
}
