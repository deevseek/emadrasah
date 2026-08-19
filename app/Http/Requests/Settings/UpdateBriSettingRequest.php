<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBriSettingRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('finance.bri.configure') === true; }
    protected function prepareForValidation(): void { $this->merge(['enabled'=>$this->boolean('enabled'),'briva_enabled'=>$this->boolean('briva_enabled'),'qris_enabled'=>$this->boolean('qris_enabled'),'payroll_enabled'=>$this->boolean('payroll_enabled'),'direct_debit_enabled'=>$this->boolean('direct_debit_enabled'),'timestamp_tolerance'=>$this->input('timestamp_tolerance',300),'timeout'=>$this->input('timeout',20)]); }
    public function rules(): array
    {
        return [
            'enabled'=>['required','boolean'], 'environment'=>['required',Rule::in(['sandbox','production'])],
            'base_url'=>['nullable','url','max:255'], 'client_id'=>['nullable','string','max:255'],
            'client_secret'=>['nullable','string','max:4096'], 'partner_id'=>['nullable','string','max:255'], 'channel_id'=>['nullable','string','max:50'],
            'private_key'=>['nullable','file','mimes:pem,txt','max:64'], 'public_key'=>['nullable','file','mimes:pem,txt','max:64'],
            'registered_account_number'=>['nullable','digits_between:8,20'],
            'briva_enabled'=>['required','boolean'], 'briva_mode'=>['required',Rule::in(['per_student','per_invoice'])],
            'partner_service_id'=>['nullable','string','max:255'], 'institution_code'=>['nullable','string','max:100'], 'customer_number_prefix'=>['nullable','string','max:50'],
            'qris_enabled'=>['required','boolean'], 'merchant_id'=>['nullable','string','max:255'], 'terminal_id'=>['nullable','string','max:255'], 'qris_service_code'=>['nullable','string','max:50'],
            'payroll_enabled'=>['required','boolean'], 'source_account'=>['nullable','string','max:100'], 'payroll_method'=>['required',Rule::in(['internal_bri','interbank'])],
            'intrabank_service_code'=>['nullable','string','max:50'], 'interbank_service_code'=>['nullable','string','max:50'], 'status_inquiry_service_code'=>['nullable','string','max:50'],
            'timestamp_tolerance'=>['required','integer','min:1','max:3600'], 'timeout'=>['required','integer','min:1','max:300'],
            'qris_notification_success_code'=>['nullable','string','max:50'], 'direct_debit_enabled'=>['required','boolean'],
            'path_bank_statement'=>['nullable','string','max:255'], 'path_qris_generate'=>['nullable','string','max:255'],
            'path_transaction_status'=>['nullable','string','max:255'], 'path_intrabank_transfer'=>['nullable','string','max:255'], 'path_interbank_transfer'=>['nullable','string','max:255'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($this->boolean('enabled')) foreach (['base_url','client_id','partner_id','channel_id'] as $field) if (! $this->filled($field)) $validator->errors()->add($field, 'Wajib diisi saat integrasi BRI aktif.');
            if ($this->boolean('briva_enabled') && ! $this->filled('partner_service_id')) $validator->errors()->add('partner_service_id', 'Wajib diisi dari nilai onboarding BRI.');
            if ($this->boolean('qris_enabled')) foreach (['merchant_id','terminal_id','qris_service_code'] as $field) if (! $this->filled($field)) $validator->errors()->add($field, 'Wajib diisi saat QRIS aktif.');
            if ($this->boolean('payroll_enabled')) foreach (['intrabank_service_code','interbank_service_code','status_inquiry_service_code'] as $field) if (! $this->filled($field)) $validator->errors()->add($field, 'Wajib diisi saat payroll BRI aktif.');
            foreach (['private_key', 'public_key'] as $key) {
                $file = $this->file($key);
                if ($file && ! str_contains((string) file_get_contents($file->getRealPath()), '-----BEGIN ')) $validator->errors()->add($key, 'Berkas harus berupa key PEM yang valid.');
            }
        });
    }
}
