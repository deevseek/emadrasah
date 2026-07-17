<?php

declare(strict_types=1);

namespace App\Http\Requests\Attendance;

use App\Enums\StudentAttendanceStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudentAttendanceRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('student-attendances.create') || $this->user()?->can('student-attendances.update-draft'); }
    public function rules(): array
    {
        return ['attendance_date'=>['required','date','before_or_equal:today'],'students'=>['required','array','min:1'],'students.*.status'=>['required',Rule::in(array_column(StudentAttendanceStatus::cases(),'value'))],'students.*.arrival_time'=>['nullable','date_format:H:i'],'students.*.departure_time'=>['nullable','date_format:H:i'],'students.*.late_minutes'=>['nullable','integer','min:0','max:600'],'students.*.early_leave_minutes'=>['nullable','integer','min:0','max:600'],'students.*.reason'=>['nullable','string','max:1000'],'students.*.notes'=>['nullable','string','max:1000'],'students.*.attachment'=>['nullable','file','mimes:pdf,jpg,jpeg,png,webp','max:2048']];
    }
    public function attributes(): array { return ['attendance_date'=>'tanggal absensi','students'=>'data siswa','students.*.status'=>'status siswa','students.*.attachment'=>'bukti izin/sakit']; }
}
