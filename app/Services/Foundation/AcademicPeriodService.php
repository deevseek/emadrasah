<?php

declare(strict_types=1);

namespace App\Services\Foundation;

use App\Models\AcademicYear;
use App\Models\Semester;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcademicPeriodService
{
    public function activateYear(AcademicYear $year): void
    {
        DB::transaction(function () use ($year): void {
            AcademicYear::query()->whereKeyNot($year->id)->update(['is_active' => false]);
            $year->update(['is_active' => true]);
        });
    }

    public function deactivateYear(AcademicYear $year): void
    {
        $year->update(['is_active' => false]);
        Semester::query()->where('academic_year_id', $year->id)->update(['is_active' => false]);
    }

    public function activateSemester(Semester $semester): void
    {
        if (! $semester->academicYear?->is_active) {
            throw ValidationException::withMessages(['academic_year_id' => 'Semester aktif harus berada pada tahun ajaran aktif.']);
        }

        DB::transaction(function () use ($semester): void {
            Semester::query()->whereKeyNot($semester->id)->update(['is_active' => false]);
            $semester->update(['is_active' => true]);
        });
    }
}
