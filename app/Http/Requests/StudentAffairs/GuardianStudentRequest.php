<?php

declare(strict_types=1);

namespace App\Http\Requests\StudentAffairs;
use App\Enums\GuardianRelationship; use Illuminate\Foundation\Http\FormRequest; use Illuminate\Validation\Rule;
class GuardianStudentRequest extends FormRequest { public function authorize(): bool { return true; } public function rules(): array { return ['guardian_id'=>['required','exists:guardians,id'],'relationship'=>['required',Rule::enum(GuardianRelationship::class)],'is_primary'=>['nullable','boolean'],'is_emergency_contact'=>['nullable','boolean'],'lives_with_student'=>['nullable','boolean'],'financially_responsible'=>['nullable','boolean'],'notes'=>['nullable','string']]; } }
