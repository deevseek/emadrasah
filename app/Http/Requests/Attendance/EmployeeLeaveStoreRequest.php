<?php

declare(strict_types=1);

namespace App\Http\Requests\Attendance;

use App\Enums\LeaveType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class EmployeeLeaveStoreRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('employee-leaves.create') ?? false; }
    public function rules(): array { return ['type'=>['required', Rule::enum(LeaveType::class)], 'starts_at'=>['required','date','after_or_equal:today'], 'ends_at'=>['required','date','after_or_equal:starts_at'], 'reason'=>['required','string','max:3000'], 'attachment'=>['nullable','file','mimes:pdf,jpg,jpeg,png,webp','max:4096']]; }
}
