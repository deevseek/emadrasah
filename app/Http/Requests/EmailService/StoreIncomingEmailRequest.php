<?php

declare(strict_types=1);

namespace App\Http\Requests\EmailService;

use Illuminate\Foundation\Http\FormRequest;

class StoreIncomingEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message_id' => ['required', 'string', 'max:255'],
            'from_address' => ['required', 'email:rfc', 'max:255'],
            'from_name' => ['nullable', 'string', 'max:255'],
            'to_addresses' => ['required', 'array', 'min:1', 'max:50'],
            'to_addresses.*' => ['required', 'email:rfc', 'max:255'],
            'cc_addresses' => ['nullable', 'array', 'max:50'],
            'cc_addresses.*' => ['required', 'email:rfc', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:1000000'],
            'received_at' => ['required', 'date'],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['file', 'max:10240', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,txt'],
        ];
    }
}
