<?php

declare(strict_types=1);

namespace App\Services\Academic;

use App\Models\{ClassroomMembership, Personnel, StudentAttendance, TeachingJournal, User};
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TeachingJournalService
{
    public const NO_PERSONNEL = 'Akun Anda belum terhubung dengan Data Personalia. Hubungi Operator Madrasah.';

    public function save(array $data, User $user, ?TeachingJournal $journal = null): TeachingJournal
    {
        return DB::transaction(function () use ($data, $user, $journal): TeachingJournal {
            $personnel = $user->personnel;
            if (! $personnel || ! $personnel->is_active) throw new AuthorizationException(self::NO_PERSONNEL);
            if ($journal && ! $user->can('teaching-journals.view-all') && $journal->personnel_id !== $personnel->id) throw new AuthorizationException('Anda hanya dapat mengubah jurnal milik sendiri.');

            $attendanceRows = collect($data['attendances']);
            unset($data['attendances']);
            $memberIds = ClassroomMembership::query()->where('classroom_id', $data['classroom_id'])->where('status', 'active')->pluck('student_id')->sort()->values();
            if ($memberIds->all() !== $attendanceRows->pluck('student_id')->map(fn ($id) => (int) $id)->sort()->values()->all()) {
                throw ValidationException::withMessages(['attendances' => 'Absensi wajib diisi untuk seluruh siswa aktif pada rombel yang dipilih.']);
            }

            $data['personnel_id'] = $user->can('teaching-journals.view-all') && isset($data['personnel_id']) ? (int) $data['personnel_id'] : $personnel->id;
            $data['personnel_id'] = Personnel::whereKey($data['personnel_id'])->where('is_active', true)->firstOrFail()->id;
            if ($journal) {
                $journal->update($data + ['updated_by' => $user->id]);
                $message = 'Mengubah Jurnal Mengajar';
            } else {
                $journal = TeachingJournal::create($data + ['created_by' => $user->id]);
                $message = 'Menyimpan Jurnal Mengajar';
            }

            $journal->attendances()->delete();
            foreach ($attendanceRows as $row) {
                $journal->attendances()->create(['student_id' => $row['student_id'], 'status' => $row['status'], 'notes' => $row['notes'] ?? null]);
                $existing = StudentAttendance::where(['student_id' => $row['student_id'], 'classroom_id' => $data['classroom_id'], 'attendance_date' => $data['journal_date']])->first();
                StudentAttendance::updateOrCreate(
                    ['student_id' => $row['student_id'], 'classroom_id' => $data['classroom_id'], 'attendance_date' => $data['journal_date']],
                    ['academic_year_id' => $data['academic_year_id'], 'semester_id' => $data['semester_id'], 'status' => $row['status'], 'source' => 'manual', 'scanned_at' => $existing?->scanned_at, 'notes' => $row['notes'] ?? null, 'recorded_by' => $existing?->recorded_by ?? $user->id, 'updated_by' => $existing ? $user->id : null]
                );
            }

            activity('akademik')->causedBy($user)->performedOn($journal)->withProperties(['tanggal' => $journal->journal_date->toDateString(), 'rombel_id' => $journal->classroom_id])->log($message);
            return $journal;
        });
    }

    public function delete(TeachingJournal $journal, User $user): void
    {
        if (! $user->can('teaching-journals.view-all') && $journal->personnel_id !== $user->personnel?->id) throw new AuthorizationException;
        DB::transaction(function () use ($journal, $user): void {
            activity('akademik')->causedBy($user)->withProperties(['tanggal' => $journal->journal_date->toDateString(), 'rombel_id' => $journal->classroom_id])->log('Menghapus Jurnal Mengajar');
            $journal->delete();
        });
    }
}
