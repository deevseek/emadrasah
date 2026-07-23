<?php

declare(strict_types=1);

namespace App\Http\Requests\StudentAffairs;

use App\Enums\AdmissionType;
use App\Enums\Gender;
use App\Enums\StudentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $id = $this->route('student')?->id;

        return [
            'user_id' => ['nullable', 'exists:users,id', Rule::unique('students', 'user_id')->ignore($id)],
            'student_number' => ['nullable', 'string', 'max:50', Rule::unique('students')->ignore($id)],
            'national_student_number' => ['nullable', 'string', 'max:50', Rule::unique('students')->ignore($id)],
            'national_identity_number' => ['nullable', 'string', 'max:50', Rule::unique('students')->ignore($id)],
            'family_card_number' => ['nullable', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'], 'nickname' => ['nullable', 'string', 'max:100'],
            'gender' => ['required', Rule::enum(Gender::class)], 'birth_place' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'], 'religion' => ['nullable', 'string', 'max:50'],
            'citizenship' => ['nullable', 'string', 'max:50'], 'child_order' => ['nullable', 'integer', 'min:1', 'max:30'],
            'siblings_count' => ['nullable', 'integer', 'min:0', 'max:30'], 'family_status' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string'], 'rt' => ['nullable', 'string', 'max:5'], 'rw' => ['nullable', 'string', 'max:5'],
            'village' => ['nullable', 'string', 'max:100'], 'district' => ['nullable', 'string', 'max:100'], 'city' => ['nullable', 'string', 'max:100'], 'province' => ['nullable', 'string', 'max:100'], 'postal_code' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:50'], 'email' => ['nullable', 'email', 'max:255'],
            'previous_school' => ['nullable', 'string', 'max:255'], 'previous_exam_number' => ['nullable', 'string', 'max:100'], 'previous_diploma_number' => ['nullable', 'string', 'max:100'],
            'admission_date' => ['nullable', 'date', 'after_or_equal:birth_date'], 'admission_type' => ['required', Rule::enum(AdmissionType::class)],
            'student_status' => ['required', Rule::enum(StudentStatus::class)], 'graduation_date' => ['nullable', 'date'],
            'photo' => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/webp', 'extensions:jpg,jpeg,png,webp', 'max:2048'],
            'notes' => ['nullable', 'string'], 'is_active' => ['nullable', 'boolean'],
            'blood_type' => ['nullable', Rule::in(['A','B','AB','O'])], 'weight_kg' => ['nullable', 'numeric', 'min:1', 'max:200'], 'height_cm' => ['nullable', 'numeric', 'min:30', 'max:250'],
            'special_needs' => ['nullable', 'string'], 'disability' => ['nullable', 'string', 'max:255'], 'medical_history' => ['nullable', 'string'], 'allergies' => ['nullable', 'string'], 'bpjs_number' => ['nullable', 'string', 'max:50'], 'kip_pip_number' => ['nullable', 'string', 'max:100'],
            'residence_type' => ['nullable', 'string', 'max:50'], 'transportation_mode' => ['nullable', 'string', 'max:50'], 'distance_to_school_km' => ['nullable', 'numeric', 'min:0', 'max:999'], 'travel_time_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
        ];
    }
}
