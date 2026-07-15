<?php

declare(strict_types=1);

namespace App\Http\Requests\StudentAffairs;
use Illuminate\Foundation\Http\FormRequest;
class EnrollmentRequest extends FormRequest { public function authorize(): bool { return true; } public function rules(): array { return ['student_id'=>['required','exists:students,id'],'academic_year_id'=>['required','exists:academic_years,id'],'classroom_id'=>['required','exists:classrooms,id'],'enrollment_number'=>['nullable','string','max:50'],'enrolled_at'=>['nullable','date'],'notes'=>['nullable','string']]; } }
