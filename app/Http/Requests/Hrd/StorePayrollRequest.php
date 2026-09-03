<?php

declare(strict_types=1);

namespace App\Http\Requests\Hrd;

use App\Models\{Personnel, PersonnelPayroll};
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePayrollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('personnel-payroll.create') === true;
    }

    public function rules(): array
    {
        return [
            'personnel_id' => ['required', Rule::exists('personnel', 'id')->where('is_active', true)->where('payroll_enabled', true)],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'pay_date' => ['nullable', 'date', 'after_or_equal:period_end'],
            'allowance' => ['nullable', 'numeric', 'min:0'],
            'deduction' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return ['personnel_id' => 'personalia', 'period_start' => 'awal periode', 'period_end' => 'akhir periode', 'pay_date' => 'tanggal pembayaran'];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $start = Carbon::parse($this->string('period_start')->toString());
            $end = Carbon::parse($this->string('period_end')->toString());
            if (! $start->isSameMonth($end, true)) {
                $validator->errors()->add('period_end', 'Periode payroll harus berada dalam bulan yang sama.');
            }

            $personnel = Personnel::find($this->integer('personnel_id'));
            if (! $personnel || (float) $personnel->base_salary <= 0) {
                $validator->errors()->add('personnel_id', 'Gaji pokok personalia harus diisi sebelum payroll diproses.');
            }

            if (PersonnelPayroll::where('personnel_id', $this->integer('personnel_id'))->whereDate('period_start', $start)->whereDate('period_end', $end)->exists()) {
                $validator->errors()->add('period_start', 'Payroll personalia untuk periode tersebut sudah pernah diproses.');
            }
        }];
    }
}
