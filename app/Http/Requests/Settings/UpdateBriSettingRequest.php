<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBriSettingRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('finance.bri.configure') === true; }
    protected function prepareForValidation(): void { $this->merge(['enabled'=>$this->boolean('enabled'),'briva_enabled'=>$this->boolean('briva_enabled'),'payroll_enabled'=>$this->boolean('payroll_enabled')]); }
    public function rules(): array
    {
        return [
            'enabled'=>['required','boolean'], 'environment'=>['required',Rule::in(['sandbox','production'])],
            'base_url'=>['nullable','url','max:255'], 'client_id'=>['nullable','string','max:255'],
            'client_secret'=>['nullable','string','max:4096'], 'partner_id'=>['nullable','string','max:255'], 'channel_id'=>['nullable','string','max:50'],
            'private_key'=>['nullable','file','mimes:pem,txt','max:64'], 'public_key'=>['nullable','file','mimes:pem,txt','max:64'],
            'briva_enabled'=>['required','boolean'], 'briva_mode'=>['required',Rule::in(['per_student','per_invoice'])],
            'partner_service_id'=>['nullable','string','max:255'], 'institution_code'=>['nullable','string','max:100'], 'customer_number_prefix'=>['nullable','string','max:50'],
            'payroll_enabled'=>['required','boolean'], 'source_account'=>['nullable','string','max:100'], 'payroll_method'=>['required',Rule::in(['internal_bri','interbank'])],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            foreach (['private_key', 'public_key'] as $key) {
                $file = $this->file($key);
                if ($file && ! str_contains((string) file_get_contents($file->getRealPath()), '-----BEGIN ')) $validator->errors()->add($key, 'Berkas harus berupa key PEM yang valid.');
            }
        });
    }
}
