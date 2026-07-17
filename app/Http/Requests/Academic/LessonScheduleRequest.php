<?php

declare(strict_types=1);

namespace App\Http\Requests\Academic;

use App\Enums\DayOfWeek;
use App\Models\TeachingAssignment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LessonScheduleRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can($this->route('schedule') ? 'schedules.update' : 'schedules.create') ?? false; }
    public function rules(): array
    { return ['teaching_assignment_id'=>['required','exists:teaching_assignments,id',fn($a,$v,$f)=>$this->checkAssignment((int)$v,$f)], 'day_of_week'=>['required',Rule::enum(DayOfWeek::class)], 'starts_at'=>['required','date_format:H:i'], 'ends_at'=>['required','date_format:H:i','after:starts_at'], 'lesson_hours'=>['required','integer','min:1','max:12'], 'room'=>['nullable','string','max:255'], 'notes'=>['nullable','string'], 'is_active'=>['nullable','boolean']]; }
    private function checkAssignment(int $id, callable $fail): void { $a=TeachingAssignment::with(['employee','classroom','subject'])->find($id); if($a && (! $a->is_active || ! $a->employee?->is_active || ! $a->classroom?->is_active || ! $a->subject?->is_active)) $fail('Penugasan Mengajar harus aktif dan valid.'); }
}
