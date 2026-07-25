<?php

declare(strict_types=1);

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;

class ImportProcessRequest extends FormRequest
{
    public function authorize():bool{return $this->user()?->can($this->routeIs('teaching-assignments.*')?'teaching-assignments.import':'schedules.import')??false;}
    public function rules():array{return['preview_token'=>['required','uuid','string'],'confirm_replace'=>['nullable','accepted']];}
}
