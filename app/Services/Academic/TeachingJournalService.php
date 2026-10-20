<?php

declare(strict_types=1);

namespace App\Services\Academic;

use App\Models\{Classroom, Personnel, StudentAttendance, TeachingJournal, User};
use App\Services\Personnel\ResolvePersonnelAccount;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TeachingJournalService
{
    public const NO_PERSONNEL = 'Akun Anda belum terhubung dengan Data Personalia. Hubungi Operator Madrasah.';

    public function __construct(
        private ResolvePersonnelAccount $personnelAccounts,
        private AcademicAccessService $access,
        private JournalAttendanceResolver $attendanceResolver,
    ) {}

    public function save(array $data, User $user, ?TeachingJournal $journal = null): TeachingJournal
    {
        $this->access->ensureTeachingJournalClassroom(
            $user,
            (int) $data['classroom_id'],
            (int) $data['academic_year_id'],
            (int) $data['semester_id'],
            (int) $data['academic_subject_id'],
        );

        return DB::transaction(function () use ($data, $user, $journal): TeachingJournal {
            Classroom::query()->whereKey($data['classroom_id'])->lockForUpdate()->firstOrFail();
            $personnel = $this->activePersonnel($user);
            if (! $personnel || ! $personnel->is_active) throw new AuthorizationException(self::NO_PERSONNEL);
            if ($journal && ! $user->can('teaching-journals.view-all') && $journal->personnel_id !== $personnel->id) throw new AuthorizationException('Anda hanya dapat mengubah jurnal milik sendiri.');

            $openingLesson = $this->attendanceResolver->isOpeningLesson($data['lesson_number']);
            $attendanceRows = collect($data['attendances'] ?? []);
            $corrections = collect($data['attendance_corrections'] ?? [])->filter(fn ($row) => filled($row['status'] ?? null))->values()->all();
            unset($data['attendances'], $data['attendance_corrections']);
            if (! $openingLesson && $attendanceRows->isNotEmpty()) throw ValidationException::withMessages(['attendances' => 'Absensi harian hanya boleh dicatat melalui jurnal jam 1–2.']);

            $members = $this->attendanceResolver->members((int) $data['classroom_id'], $data['journal_date']);
            if ($openingLesson) $this->attendanceResolver->assertCompleteRoster($members, $attendanceRows);

            $data['personnel_id'] = $user->can('teaching-journals.view-all') && isset($data['personnel_id']) ? (int) $data['personnel_id'] : $personnel->id;
            $data['personnel_id'] = Personnel::whereKey($data['personnel_id'])->where('is_active', true)->firstOrFail()->id;
            if ($journal) {
                $journal->update($data + ['updated_by' => $user->id]);
                $message = 'Mengubah Jurnal Mengajar';
            } else {
                $journal = TeachingJournal::create($data + ['created_by' => $user->id]);
                $message = 'Menyimpan Jurnal Mengajar';
            }

            if ($openingLesson) {
                foreach ($attendanceRows as $row) {
                    $existing = StudentAttendance::query()->where(['student_id' => $row['student_id'], 'classroom_id' => $data['classroom_id'], 'attendance_date' => $data['journal_date']])->lockForUpdate()->first();
                    if (($existing?->source?->value ?? $existing?->source) === 'rfid') continue;
                    StudentAttendance::updateOrCreate(
                        ['student_id' => $row['student_id'], 'classroom_id' => $data['classroom_id'], 'attendance_date' => $data['journal_date']],
                        ['academic_year_id' => $data['academic_year_id'], 'semester_id' => $data['semester_id'], 'status' => $row['status'], 'source' => 'manual', 'scanned_at' => null, 'rfid_device_id' => null, 'notes' => $row['notes'] ?? null, 'recorded_by' => $existing?->recorded_by ?? $user->id, 'updated_by' => $existing ? $user->id : null]
                    );
                }
            }

            $daily = $this->attendanceResolver->daily((int) $data['classroom_id'], $data['journal_date']);
            $this->attendanceResolver->writeSnapshots($journal, $members, $daily, $openingLesson ? [] : $corrections, $user);
            if ($openingLesson) $this->attendanceResolver->syncFollowingJournals((int) $data['classroom_id'], $data['journal_date'], $members, $daily, $user);

            activity('akademik')->causedBy($user)->performedOn($journal)->withProperties(['tanggal' => $journal->journal_date->toDateString(), 'rombel_id' => $journal->classroom_id, 'sumber_absensi' => $openingLesson ? 'manual_harian' : 'snapshot_harian', 'jumlah_koreksi' => count($corrections)])->log($message);
            return $journal;
        });
    }

    public function activePersonnel(User $user): ?Personnel
    {
        return $this->personnelAccounts->handle($user);
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
