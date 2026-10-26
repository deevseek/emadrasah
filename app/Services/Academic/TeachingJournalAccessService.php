<?php

declare(strict_types=1);

namespace App\Services\Academic;

use App\Models\AcademicSubject;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\LessonSchedule;
use App\Models\Personnel;
use App\Models\Semester;
use App\Models\TeachingJournal;
use App\Models\User;
use App\Services\Personnel\ResolvePersonnelAccount;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class TeachingJournalAccessService
{
    public function __construct(private ResolvePersonnelAccount $personnelAccounts) {}

    public function classrooms(User $user, int $year, int $semester): Builder
    {
        $query = Classroom::query()->where('is_active', true)->where('academic_year_id', $year)->with('gradeLevel');
        if ($user->can('teaching-journals.view-all')) {
            return $query;
        }
        $personnel = $this->personnelAccounts->handle($user);
        if (! $personnel) {
            return $query->whereRaw('1=0');
        }

        if ($this->isBtaqTeacher($personnel)) {
            return $query;
        }

        return $query->where(fn ($q) => $q->where('homeroom_personnel_id', $personnel->id)
            ->orWhereIn('id', $this->schedules($personnel->id, $year, $semester)->select('classroom_id')));
    }

    private function schedules(int $personnel, int $year, int $semester): Builder
    {
        return LessonSchedule::query()->where(['personnel_id' => $personnel, 'academic_year_id' => $year, 'semester_id' => $semester, 'active' => true]);
    }

    public function subjects(User $user, int $room, int $year, int $semester): Builder
    {
        $query = AcademicSubject::query()->where('is_active', true)->orderBy('sort_order');
        if ($user->can('teaching-journals.view-all')) {
            return $query;
        }
        $personnel = $this->personnelAccounts->handle($user);
        if (! $personnel) {
            return $query->whereRaw('1=0');
        }
        // Pertahankan hak lama wali kelas pada rombelnya sendiri.
        if (Classroom::whereKey($room)->where('academic_year_id', $year)->where('homeroom_personnel_id', $personnel->id)->exists()) {
            return $query;
        }

        return $query->where(function (Builder $subjects) use ($personnel, $room, $year, $semester): void {
            $subjects->whereIn('id', $this->schedules($personnel->id, $year, $semester)->where('classroom_id', $room)->select('academic_subject_id'));
            if ($this->isBtaqTeacher($personnel)) {
                $subjects->orWhere(function (Builder $btaq): void {
                    $btaq->whereRaw('UPPER(TRIM(code)) IN (?, ?)', ['BTAQ', 'BATQ'])
                        ->orWhereRaw('UPPER(TRIM(name)) IN (?, ?)', ['BTAQ', 'BATQ']);
                });
            }
        });
    }

    private function isBtaqTeacher(Personnel $personnel): bool
    {
        // Jabatan resmi personalia menjadi penugasan lintas rombel khusus BTAQ.
        return in_array(mb_strtolower(preg_replace('/\s+/u', ' ', trim((string) $personnel->position))), ['guru btaq', 'guru batq'], true);
    }

    public function ensureManage(User $user, ?TeachingJournal $journal = null): void
    {
        if (! $user->can('teaching-journals.manage')) {
            throw new AuthorizationException('Anda tidak memiliki izin mengelola jurnal.');
        }
        if ($user->can('teaching-journals.view-all')) {
            return;
        }
        $personnel = $this->personnelAccounts->handle($user);
        if (! $personnel) {
            throw new AuthorizationException(TeachingJournalService::NO_PERSONNEL);
        }
        if ($journal && (int) $journal->personnel_id !== (int) $personnel->id) {
            throw new AuthorizationException('Anda hanya dapat mengelola jurnal milik sendiri.');
        }
    }

    public function validateContext(User $user, array $data, ?TeachingJournal $journal): void
    {
        $this->ensureManage($user, $journal);
        $date = Carbon::parse($data['journal_date'])->startOfDay();
        $unchanged = $journal && collect(['personnel_id', 'classroom_id', 'academic_subject_id', 'academic_year_id', 'semester_id', 'lesson_number'])->every(fn ($key) => (string) $journal->$key === (string) $data[$key]) && $journal->journal_date->isSameDay($date);
        // Jurnal historis tetap dapat diperbaiki pemiliknya tanpa membuka konteks baru.
        if ($unchanged) {
            return;
        }
        $year = AcademicYear::find($data['academic_year_id']);
        $semester = Semester::whereKey($data['semester_id'])->where('academic_year_id', $data['academic_year_id'])->first();
        $room = Classroom::whereKey($data['classroom_id'])->where('academic_year_id', $data['academic_year_id'])->first();
        if (! $year || ! $semester || ! $room || ! $date->betweenIncluded($year->starts_at, $year->ends_at) || ! $date->betweenIncluded($semester->starts_at, $semester->ends_at)) {
            throw ValidationException::withMessages(['journal_date' => 'Rombel, tahun ajaran, semester, dan tanggal jurnal harus sesuai periode yang dipilih.']);
        }
        if (! $room->is_active || ! AcademicSubject::whereKey($data['academic_subject_id'])->where('is_active', true)->exists()) {
            throw ValidationException::withMessages(['classroom_id' => 'Rombel dan mata pelajaran harus aktif.']);
        }
        if ($user->can('teaching-journals.view-all')) {
            return;
        }
        if (! $this->classrooms($user, $year->id, $semester->id)->whereKey($room->id)->exists()) {
            throw ValidationException::withMessages(['classroom_id' => 'Anda tidak memiliki penugasan aktif pada rombel ini. Hubungi Operator Madrasah.']);
        }
        if (! $this->subjects($user, $room->id, $year->id, $semester->id)->whereKey($data['academic_subject_id'])->exists()) {
            throw ValidationException::withMessages(['academic_subject_id' => 'Mata pelajaran ini bukan penugasan aktif Anda pada rombel dan semester yang dipilih.']);
        }
    }
}
