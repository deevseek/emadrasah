<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApplicationSettingRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('application-settings.update') === true; }

    protected function prepareForValidation(): void
    {
        $data = collect($this->except(['_token', '_method']))->map(fn ($value) => is_string($value) ? trim($value) : $value)->all();
        $data['maintenance_mode'] = $this->boolean('maintenance_mode');
        $data['attendance_rfid_enabled'] = $this->boolean('attendance_rfid_enabled');
        $data['rfid_writer_enabled'] = $this->boolean('rfid_writer_enabled');
        foreach (['hrd_face_recognition_enabled','hrd_payroll_by_attendance_enabled','hrd_payroll_auto_late_deduction_enabled','hrd_payroll_auto_cash_advance_deduction_enabled'] as $key) $data[$key] = $this->boolean($key);
        if (isset($data['app_email'])) $data['app_email'] = strtolower($data['app_email']);
        $this->merge($data);
    }

    public function rules(): array
    {
        $image = ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:2048'];
        return [
            'app_name' => ['required', 'string', 'max:100'], 'app_short_name' => ['required', 'string', 'max:50'],
            'app_description' => ['nullable', 'string', 'max:255'], 'institution_name' => ['required', 'string', 'max:150'],
            'app_email' => ['nullable', 'email', 'max:255'], 'app_phone' => ['nullable', 'string', 'max:30'], 'app_website' => ['nullable', 'url', 'max:255'],
            'primary_logo' => $image, 'login_logo' => $image, 'print_logo' => $image,
            'favicon' => ['nullable', 'file', 'mimes:png,ico', 'max:512'],
            'primary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'], 'default_theme' => ['required', Rule::in(['light'])],
            'sidebar_mode' => ['required', Rule::in(['expanded', 'compact'])], 'default_language' => ['required', Rule::in(['id'])],
            'timezone' => ['required', 'timezone'], 'date_format' => ['required', Rule::in(['DD/MM/YYYY', 'DD-MM-YYYY', 'YYYY-MM-DD'])],
            'time_format' => ['required', Rule::in(['24', '12'])], 'first_day_of_week' => ['required', Rule::in(['monday', 'sunday'])],
            'maintenance_mode' => ['required', 'boolean'], 'maintenance_message' => ['required', 'string', 'max:500'],
            'pagination_size' => ['required', 'integer', Rule::in([10, 20, 25, 50, 100])],
            'attendance_rfid_enabled' => ['required', 'boolean'],
            'rfid_writer_enabled' => ['required', 'boolean'],
            'hrd_attendance_latitude'=>['nullable','numeric','between:-90,90'],'hrd_attendance_longitude'=>['nullable','numeric','between:-180,180'],'hrd_attendance_radius_meter'=>['required','integer','between:1,10000'],
            'hrd_shift_count'=>['required','integer','between:1,3'],'hrd_shift_1_start'=>['required','date_format:H:i'],'hrd_shift_1_end'=>['required','date_format:H:i'],'hrd_shift_2_start'=>['required','date_format:H:i'],'hrd_shift_2_end'=>['required','date_format:H:i'],'hrd_shift_3_start'=>['required','date_format:H:i'],'hrd_shift_3_end'=>['required','date_format:H:i'],
            'hrd_early_checkin_minutes'=>['required','integer','between:0,720'],'hrd_max_late_checkin_hours'=>['required','integer','between:1,12'],'hrd_face_recognition_enabled'=>['required','boolean'],'hrd_payroll_by_attendance_enabled'=>['required','boolean'],'hrd_payroll_auto_late_deduction_enabled'=>['required','boolean'],'hrd_payroll_auto_cash_advance_deduction_enabled'=>['required','boolean'],
        ];
    }

    public function messages(): array
    {
        return ['primary_color.regex' => 'Warna utama harus menggunakan format HEX, misalnya #047857.', 'timezone.timezone' => 'Zona waktu yang dipilih tidak valid.', '*.mimes' => 'Format berkas yang dipilih tidak didukung.', '*.max' => 'Ukuran berkas melebihi batas yang diperbolehkan.'];
    }
}
