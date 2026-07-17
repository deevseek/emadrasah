<?php

declare(strict_types=1);

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;

class PlaceStudentsRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['student_ids'=>['required','array','min:1'],'student_ids.*'=>['integer','exists:students,id'],'enrolled_at'=>['required','date'],'notes'=>['nullable','string','max:1000'],'override_capacity'=>['nullable','boolean'],'override_reason'=>['nullable','string','max:1000']]; }
}
