<?php

declare(strict_types=1);

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;

class TransferStudentRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['target_classroom_id'=>['required','exists:classrooms,id'],'effective_date'=>['required','date'],'reason'=>['required','string','max:1000'],'notes'=>['nullable','string','max:1000']]; }
}
