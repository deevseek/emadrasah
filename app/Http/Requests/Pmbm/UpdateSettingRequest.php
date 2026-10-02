<?php

declare(strict_types=1);

namespace App\Http\Requests\Pmbm;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('pmbm.settings.update') ?? false; }
    public function rules(): array
    {
        return ['academic_year_id'=>'required|exists:academic_years,id','title'=>'required|string|max:255','enabled'=>'boolean','registration_prefix'=>'required|string|max:40','registration_open_at'=>'nullable|date','registration_close_at'=>'nullable|date|after:registration_open_at','quota_total'=>'nullable|integer|min:1','online_registration_enabled'=>'boolean','offline_registration_enabled'=>'boolean','minimum_initial_payment_percent'=>'required|integer|min:0|max:100','fee_items'=>'nullable|array','fee_items.*.name'=>'required|string|max:100','fee_items.*.male'=>'required|numeric|min:0','fee_items.*.female'=>'required|numeric|min:0','schedule_items'=>'nullable|array','schedule_items.*.title'=>'required|string|max:150','schedule_items.*.scheduled_at'=>'nullable|date','schedule_items.*.description'=>'nullable|string|max:500','panitia_phone'=>'nullable|string|max:30','panitia_email'=>'nullable|email|max:255','location'=>'nullable|string|max:255','registration_instructions'=>'nullable|string|max:5000','allowed_payment_methods'=>'required|array|min:1','allowed_payment_methods.*'=>'in:bank_transfer,virtual_account','bank_name'=>'nullable|required_if:allowed_payment_methods.*,bank_transfer|string|max:100','bank_account_number'=>'nullable|string|max:100','bank_account_name'=>'nullable|string|max:150','payment_instructions'=>'nullable|string|max:5000','payment_proof_required'=>'boolean','announcement_at'=>'nullable|date','require_fitting'=>'boolean','require_parent_interview'=>'boolean','require_child_interview'=>'boolean','require_child_observation'=>'boolean','require_headmaster_approval'=>'boolean','require_payment_before_registration_complete'=>'boolean','document_requirements'=>'nullable|array','document_requirements.*.name'=>'required|string|max:100','document_requirements.*.code'=>'required|alpha_dash|max:100','document_requirements.*.is_required'=>'boolean','document_requirements.*.is_active'=>'boolean','document_requirements.*.channel'=>'required|in:online,offline,both'];
    }
}
