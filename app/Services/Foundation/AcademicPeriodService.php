<?php

declare(strict_types=1);

namespace App\Services\Foundation;

use App\Enums\SemesterType;
use App\Models\AcademicYear;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class AcademicPeriodService
{
    public const CACHE_KEY = 'academic_period.active';

    public function current(): ?AcademicYear
    {
        if (! Schema::hasTable('academic_years') || ! Schema::hasTable('semesters')) return null;
        return Cache::rememberForever(self::CACHE_KEY, fn () => AcademicYear::query()->where('is_active', true)->with(['activeSemester'])->first());
    }

    public function create(array $data, User $actor): AcademicYear
    {
        $year = DB::transaction(function () use ($data, $actor): AcademicYear {
            $year = AcademicYear::create($this->yearData($data, $actor, true));
            $this->saveSemesters($year, $data, $actor, true);
            activity('periode-akademik')->causedBy($actor)->performedOn($year)->withProperties(['academic_year_id' => $year->id, 'name' => $year->name])->log("Menambahkan Tahun Ajaran {$year->name}.");
            return $year;
        });
        $this->forgetCache();
        return $year;
    }

    public function update(AcademicYear $academicYear, array $data, User $actor): AcademicYear
    {
        $before = $academicYear->only(['name', 'starts_at', 'ends_at', 'notes']);
        $year = DB::transaction(function () use ($academicYear, $data, $actor, $before): AcademicYear {
            $academicYear->update($this->yearData($data, $actor, false));
            $this->saveSemesters($academicYear, $data, $actor, false);
            activity('periode-akademik')->causedBy($actor)->performedOn($academicYear)->withProperties(['sebelum' => $before, 'sesudah' => $academicYear->only(['name', 'starts_at', 'ends_at', 'notes'])])->log("Memperbarui Tahun Ajaran {$academicYear->name}.");
            return $academicYear->refresh()->load('semesters');
        });
        $this->forgetCache();
        return $year;
    }

    public function activate(Semester $semester, User $actor): bool
    {
        $alreadyActive = false;
        DB::transaction(function () use ($semester, $actor, &$alreadyActive): void {
            AcademicYear::query()->lockForUpdate()->get();
            Semester::query()->lockForUpdate()->get();
            $target = Semester::query()->with('academicYear')->lockForUpdate()->findOrFail($semester->id);
            $previous = Semester::query()->where('is_active', true)->with('academicYear')->first();
            $alreadyActive = $target->is_active && $target->academicYear->is_active;
            AcademicYear::query()->where('is_active', true)->update(['is_active' => false]);
            Semester::query()->where('is_active', true)->update(['is_active' => false]);
            $target->academicYear->update(['is_active' => true, 'updated_by' => $actor->id]);
            $target->update(['is_active' => true, 'updated_by' => $actor->id]);
            activity('periode-akademik')->causedBy($actor)->performedOn($target)->withProperties(['periode_lama' => $previous ? ['semester_id' => $previous->id, 'tahun_ajaran' => $previous->academicYear->name, 'semester' => $previous->type->value] : null, 'periode_baru' => ['semester_id' => $target->id, 'tahun_ajaran' => $target->academicYear->name, 'semester' => $target->type->value]])->log("Mengaktifkan {$target->display_name} Tahun Ajaran {$target->academicYear->name}.");
        });
        $this->forgetCache();
        return ! $alreadyActive;
    }

    public function delete(AcademicYear $academicYear, User $actor): void
    {
        if ($academicYear->is_active || $academicYear->semesters()->where('is_active', true)->exists()) throw ValidationException::withMessages(['academic_year' => 'Tahun ajaran tidak dapat dihapus karena sedang aktif atau sudah digunakan oleh data lain.']);
        try {
            DB::transaction(function () use ($academicYear, $actor): void {
                $name = $academicYear->name; $id = $academicYear->id;
                $academicYear->delete();
                activity('periode-akademik')->causedBy($actor)->withProperties(['academic_year_id' => $id, 'name' => $name])->log("Menghapus Tahun Ajaran {$name}.");
            });
        } catch (QueryException) {
            throw ValidationException::withMessages(['academic_year' => 'Tahun ajaran tidak dapat dihapus karena sedang aktif atau sudah digunakan oleh data lain.']);
        }
        $this->forgetCache();
    }

    public function forgetCache(): void { Cache::forget(self::CACHE_KEY); }

    private function yearData(array $data, User $actor, bool $creating): array
    {
        $values = ['name' => $data['name'], 'starts_at' => $data['starts_at'], 'ends_at' => $data['ends_at'], 'notes' => $data['notes'] ?? null, 'updated_by' => $actor->id];
        if ($creating) $values['created_by'] = $actor->id;
        return $values;
    }

    private function saveSemesters(AcademicYear $year, array $data, User $actor, bool $creating): void
    {
        foreach ([[SemesterType::Ganjil, 'odd_starts_at', 'odd_ends_at'], [SemesterType::Genap, 'even_starts_at', 'even_ends_at']] as [$type, $start, $end]) {
            $values = ['name' => $type->label(), 'starts_at' => $data[$start], 'ends_at' => $data[$end], 'updated_by' => $actor->id];
            if ($creating) $values['created_by'] = $actor->id;
            $year->semesters()->updateOrCreate(['type' => $type->value], $values);
        }
    }
}
