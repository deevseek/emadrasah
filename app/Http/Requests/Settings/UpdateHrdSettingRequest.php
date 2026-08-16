<?php
declare(strict_types=1);
namespace App\Http\Requests\Settings;
use Illuminate\Foundation\Http\FormRequest;
class UpdateHrdSettingRequest extends FormRequest
{
    public function authorize(): bool{return $this->user()?->can('hrd-settings.update')===true;}
    protected function prepareForValidation():void{foreach(['hrd_face_recognition_enabled','hrd_attendance_location_enabled','hrd_attendance_face_enabled','hrd_attendance_device_binding_enabled','hrd_attendance_anti_replay_enabled','hrd_attendance_new_device_requires_approval','hrd_payroll_by_attendance_enabled','hrd_payroll_auto_late_deduction_enabled','hrd_payroll_auto_cash_advance_deduction_enabled'] as $key)$this->merge([$key=>$this->boolean($key)]);}
    public function rules():array{return [
        'hrd_attendance_latitude'=>['nullable','numeric','between:-90,90'],'hrd_attendance_longitude'=>['nullable','numeric','between:-180,180'],'hrd_attendance_radius_meter'=>['required','integer','between:1,10000'],
        'hrd_attendance_location_enabled'=>['required','boolean'],'hrd_attendance_face_enabled'=>['required','boolean'],'hrd_attendance_device_binding_enabled'=>['required','boolean'],'hrd_attendance_anti_replay_enabled'=>['required','boolean'],'hrd_attendance_new_device_requires_approval'=>['required','boolean'],
        'hrd_attendance_max_accuracy_meter'=>['required','integer','between:1,10000'],'hrd_attendance_location_max_age_seconds'=>['required','integer','between:5,300'],'hrd_attendance_challenge_ttl_seconds'=>['required','integer','between:15,300'],'hrd_face_verification_ttl_seconds'=>['required','integer','between:30,600'],'hrd_face_confidence_threshold'=>['required','numeric','between:0,1'],'hrd_attendance_max_devices'=>['required','integer','between:1,10'],
        'hrd_shift_count'=>['required','integer','between:1,3'],'hrd_shift_1_start'=>['required','date_format:H:i'],'hrd_shift_1_end'=>['required','date_format:H:i'],'hrd_shift_2_start'=>['required','date_format:H:i'],'hrd_shift_2_end'=>['required','date_format:H:i'],'hrd_shift_3_start'=>['required','date_format:H:i'],'hrd_shift_3_end'=>['required','date_format:H:i'],
        'hrd_early_checkin_minutes'=>['required','integer','between:0,720'],'hrd_max_late_checkin_hours'=>['required','integer','between:1,12'],'hrd_face_recognition_enabled'=>['required','boolean'],'hrd_payroll_by_attendance_enabled'=>['required','boolean'],'hrd_payroll_auto_late_deduction_enabled'=>['required','boolean'],'hrd_payroll_auto_cash_advance_deduction_enabled'=>['required','boolean'],
    ];}
}
