<?php

declare(strict_types=1);

namespace App\Services\Pmbm;

use App\Enums\Pmbm\ApplicantStatus;
use App\Models\Pmbm\PmbmApplicant;
use Illuminate\Database\Eloquent\Builder;

class PmbmReportService
{
    public function query(array $filters): Builder
    {
        $query = PmbmApplicant::query()->with('academicYear');
        foreach (['academic_year_id' => 'academic_year_id', 'source' => 'registration_source', 'status' => 'status', 'gender' => 'gender'] as $key => $column) {
            if (! empty($filters[$key])) {
                $query->where($column, $filters[$key]);
            }
        }
        if (! empty($filters['date_from'])) {
            $query->whereDate('submitted_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('submitted_at', '<=', $filters['date_to']);
        }

        return $query;
    }

    public function summary(array $filters): array
    {
        $counts = $this->query($filters)->reorder()->selectRaw('status, COUNT(*) as total')->groupBy('status')->get()->mapWithKeys(fn ($row) => [$row->status->value => (int) $row->total]);

        return ['total' => $counts->sum(), 'statuses' => collect(ApplicantStatus::cases())->map(fn ($status) => ['label' => $status->label(), 'total' => $counts[$status->value] ?? 0])];
    }

    public function ordered(array $filters): Builder
    {
        return $this->query($filters)->orderByDesc('submitted_at')->orderByDesc('id');
    }

    public function csvCell(?string $value): string
    {
        $value ??= '';

        return preg_match('/^[\s]*[=+@\-]/u', $value) ? "'".$value : $value;
    }
}
