<?php

declare(strict_types=1);

namespace App\Services\Academic;

use App\Models\Classroom;
use App\Models\Personnel;
use App\Models\StudentAttendance;
use App\Models\TeachingJournal;
use App\Models\User;
use App\Services\Personnel\ResolvePersonnelAccount;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TeachingJournalService
{
    public const NO_PERSONNEL = 'Akun Anda belum terhubung dengan Data Personalia. Hubungi Operator Madrasah.';

    public function __construct(
        private ResolvePersonnelAccount $personnelAccounts,
        private TeachingJournalAccessService $access,
        private JournalAttendanceResolver $attendanceResolver,
    ) {}

    public function save(array $data, User $user, ?TeachingJournal $journal = null): TeachingJournal
    {
        $this->access->ensureManage($user, $journal);
        $data['journal_date'] = Carbon::parse($data['journal_date'])->toDateString();

        return DB::transaction(function () use ($data, $user, $journal): TeachingJournal {
            Classroom::query()->whereKey($data['classroom_id'])->lockForUpdate()->firstOrFail();
            $personnel = $this->activePersonnel($user);
            if (! $user->can('teaching-journals.view-all') && (! $personnel || ! $personnel->is_active)) {
                throw new AuthorizationException(self::NO_PERSONNEL);
            }
            if ($journal && ! $user->can('teaching-journals.view-all') && $journal->personnel_id !== $personnel->id) {
                throw new AuthorizationException('Anda hanya dapat mengubah jurnal milik sendiri.');
            }

            $openingLesson = $this->attendanceResolver->isOpeningLesson($data['lesson_number']);
            $attendanceRows = collect($data['attendances'] ?? []);
            $corrections = collect($data['attendance_corrections'] ?? [])->filter(fn ($row) => filled($row['status'] ?? null))->values()->all();
            unset($data['attendances'], $data['attendance_corrections']);
            if (! $openingLesson && $attendanceRows->isNotEmpty()) {
                throw ValidationException::withMessages(['attendances' => 'Absensi harian hanya boleh dicatat melalui jurnal jam 1–2.']);
            }

            $members = $this->attendanceResolver->members((int) $data['classroom_id'], $data['journal_date']);
            if ($openingLesson) {
                $this->attendanceResolver->assertCompleteRoster($members, $attendanceRows);
            }

            $data['personnel_id'] = $user->can('teaching-journals.view-all') && isset($data['personnel_id']) ? (int) $data['personnel_id'] : ($journal?->personnel_id ?? $personnel?->id);
            if (! Personnel::whereKey($data['personnel_id'])->where('is_active', true)->exists()) {
                throw ValidationException::withMessages(['personnel_id' => 'Pilih guru dengan Data Personalia aktif.']);
            }
            $this->access->validateContext($user, $data, $journal);
            if ($journal && ((int) $journal->classroom_id !== (int) $data['classroom_id'] || ! $journal->journal_date->isSameDay($data['journal_date']))) {
                $journal->attendances()->where('source', '!=', 'legacy')->delete();
            }
            if ($journal) {
                $journal->update($data + ['updated_by' => $user->id]);
                $message = 'Mengubah Jurnal Mengajar';
            } else {
                $journal = TeachingJournal::create($data + ['created_by' => $user->id]);
                $message = 'Menyimpan Jurnal Mengajar';
            }

            if ($openingLesson) {
                foreach ($attendanceRows as $row) {
                    $existing = StudentAttendance::query()->where(['student_id' => $row['student_id'], 'classroom_id' => $data['classroom_id']])->whereDate('attendance_date', $data['journal_date'])->lockForUpdate()->first();
                    if (($existing?->source?->value ?? $existing?->source) === 'rfid') {
                        continue;
                    }
                    ($existing ?? new StudentAttendance)->fill(
                        ['student_id' => $row['student_id'], 'classroom_id' => $data['classroom_id'], 'attendance_date' => $data['journal_date']] +
                        ['academic_year_id' => $data['academic_year_id'], 'semester_id' => $data['semester_id'], 'status' => $row['status'], 'source' => 'manual', 'scanned_at' => null, 'rfid_device_id' => null, 'notes' => $row['notes'] ?? null, 'recorded_by' => $existing?->recorded_by ?? $user->id, 'updated_by' => $existing ? $user->id : null]
                    )->save();
                }
            }

            $daily = $this->attendanceResolver->daily((int) $data['classroom_id'], $data['journal_date']);
            $this->attendanceResolver->writeSnapshots($journal, $members, $daily, $openingLesson ? [] : $corrections, $user);
            if ($openingLesson) {
                $this->attendanceResolver->syncFollowingJournals((int) $data['classroom_id'], $data['journal_date'], $members, $daily, $user);
            }

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
        $this->access->ensureManage($user, $journal);
        DB::transaction(function () use ($journal, $user): void {
            activity('akademik')->causedBy($user)->withProperties(['tanggal' => $journal->journal_date->toDateString(), 'rombel_id' => $journal->classroom_id])->log('Menghapus Jurnal Mengajar');
            $journal->delete();
        });
    }
}
