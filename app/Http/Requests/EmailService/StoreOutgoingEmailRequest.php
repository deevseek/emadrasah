<?php

declare(strict_types=1);

namespace App\Http\Requests\EmailService;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreOutgoingEmailRequest extends FormRequest
{
    private const MAX_RECIPIENTS = 50;

    public function authorize(): bool
    {
        return $this->user()?->can('email-service.send') ?? false;
    }

    protected function prepareForValidation(): void
    {
        foreach (['to_addresses', 'cc_addresses', 'bcc_addresses'] as $field) {
            $value = $this->input($field, []);
            $addresses = is_array($value) ? $value : preg_split('/[,;\r\n]+/', (string) $value);
            $addresses = collect($addresses)->map(fn ($address) => strtolower(trim((string) $address)))->filter()->unique()->values()->all();
            $this->merge([$field => $addresses]);
        }
    }

    public function rules(): array
    {
        return [
            'to_addresses' => ['required', 'array', 'min:1', 'max:'.self::MAX_RECIPIENTS],
            'to_addresses.*' => ['required', 'email:rfc', 'max:254', 'distinct:ignore_case'],
            'cc_addresses' => ['nullable', 'array', 'max:'.self::MAX_RECIPIENTS],
            'cc_addresses.*' => ['required', 'email:rfc', 'max:254', 'distinct:ignore_case'],
            'bcc_addresses' => ['nullable', 'array', 'max:'.self::MAX_RECIPIENTS],
            'bcc_addresses.*' => ['required', 'email:rfc', 'max:254', 'distinct:ignore_case'],
            'subject' => ['required', 'string', 'max:255', 'not_regex:/[\r\n]/'],
            'body' => ['required', 'string', 'max:100000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png', 'max:10240'],
            'action' => ['required', 'in:draft,send'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $all = collect(['to_addresses', 'cc_addresses', 'bcc_addresses'])
                ->flatMap(fn (string $field) => $this->input($field, []));
            if ($all->count() > self::MAX_RECIPIENTS) {
                $validator->errors()->add('to_addresses', 'Jumlah seluruh penerima maksimal 50 alamat.');
            }
            if ($all->unique()->count() !== $all->count()) {
                $validator->errors()->add('to_addresses', 'Alamat penerima tidak boleh sama pada Kepada, CC, atau BCC.');
            }
        }];
    }

    public function messages(): array
    {
        return [
            'to_addresses.required' => 'Alamat email tujuan wajib diisi.',
            'to_addresses.*.email' => 'Salah satu alamat email tujuan tidak valid.',
            'subject.required' => 'Subjek wajib diisi.',
            'subject.not_regex' => 'Subjek tidak boleh memuat pemisah baris.',
            'body.required' => 'Isi email wajib diisi.',
            'attachments.max' => 'Lampiran maksimal 5 file.',
            'attachments.*.mimes' => 'Lampiran harus berupa PDF, DOC, DOCX, XLS, XLSX, JPG, JPEG, atau PNG.',
            'attachments.*.max' => 'Ukuran setiap lampiran maksimal 10 MB.',
        ];
    }
}
