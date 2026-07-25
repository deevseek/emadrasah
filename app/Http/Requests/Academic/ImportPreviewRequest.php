<?php

declare(strict_types=1);

namespace App\Http\Requests\Academic;

use App\Models\Semester;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class ImportPreviewRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can($this->routeIs('teaching-assignments.*')?'teaching-assignments.import':'schedules.import')??false; }
    public function rules(): array { return ['academic_year_id'=>['required','exists:academic_years,id'],'semester_id'=>['required','exists:semesters,id',function($a,$v,$fail){if((int)Semester::find($v)?->academic_year_id!==(int)$this->academic_year_id)$fail('Semester harus sesuai tahun ajaran.');}],'file'=>['required',File::types(['xlsx'])->max(10240)],'mode'=>['nullable','in:create,update,replace']]; }
}
