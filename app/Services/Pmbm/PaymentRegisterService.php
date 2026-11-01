<?php

declare(strict_types=1);

namespace App\Services\Pmbm;

use App\Enums\Pmbm\{PaymentStage, PaymentStatus};
use App\Models\AcademicYear;
use App\Models\Pmbm\PmbmPayment;
use Illuminate\Database\Eloquent\Builder;

class PaymentRegisterService
{
    public function data(array $filters): array
    {
        $query = PmbmPayment::query()
            ->when($filters['search'] ?? null, fn (Builder $q, string $term) => $q->where(function (Builder $q) use ($term) {
                $q->where('reference_number', 'like', '%'.$term.'%')
                    ->orWhereHas('applicant', fn (Builder $a) => $a->where('full_name', 'like', '%'.$term.'%')->orWhere('registration_number', 'like', '%'.$term.'%'));
            }))
            ->when($filters['academic_year_id'] ?? null, fn (Builder $q, $id) => $q->whereHas('applicant', fn (Builder $a) => $a->where('academic_year_id', $id)))
            ->when($filters['status'] ?? null, fn (Builder $q, $status) => $q->where('status', $status))
            ->when($filters['payment_stage'] ?? null, fn (Builder $q, $stage) => $q->where('payment_stage', $stage))
            ->when($filters['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('paid_at', '>=', $date))
            ->when($filters['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('paid_at', '<=', $date));

        $summary = [];
        foreach (PaymentStatus::cases() as $status) {
            $group = (clone $query)->where('status', $status);
            $summary[$status->value] = ['count' => (clone $group)->count(), 'amount' => (clone $group)->sum('amount')];
        }

        return [
            'payments' => $query->with(['applicant.academicYear', 'receiver', 'verifier'])->orderByDesc('paid_at')->orderByDesc('id')->paginate(25)->withQueryString(),
            'summary' => $summary,
            'filters' => $filters,
            'academicYears' => AcademicYear::orderByDesc('starts_at')->get(),
            'statuses' => PaymentStatus::cases(),
            'stages' => PaymentStage::cases(),
        ];
    }
}
