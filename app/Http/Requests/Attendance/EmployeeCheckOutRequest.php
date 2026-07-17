<?php

declare(strict_types=1);

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

final class EmployeeCheckOutRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('employee-attendances.check-out') ?? false; }
    public function rules(): array { return ['latitude'=>['nullable','numeric','between:-90,90'],'longitude'=>['nullable','numeric','between:-180,180'],'accuracy'=>['nullable','integer','min:0'],'photo'=>['nullable','file','mimes:jpg,jpeg,png,webp','max:4096']]; }
}
