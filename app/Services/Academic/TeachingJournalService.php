<?php

declare(strict_types=1);

namespace App\Services\Academic;

use App\Models\Personnel;
use App\Models\Student;
use App\Models\TeachingJournal;
use App\Models\TeachingJournalAttendance;
use App\Models\User;
use App\Services\Personnel\ResolvePersonnelAccount;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TeachingJournalService
{
    public const NO_PERSONNEL = 'Akun Anda belum terhubung dengan Data Personalia. Hubungi Operator Madrasah.';

    public function __construct(private ResolvePersonnelAccount $personnelAccounts, private JournalAttendanceResolver $attendanceResolver) {}

    public function save(array $data, User $user, ?TeachingJournal $journal = null): TeachingJournal
    {
        return DB::transaction(function () use ($data, $user, $journal): TeachingJournal {
            $personnel = $this->activePersonnel($user);
            if (! $personnel || ! $personnel->is_active) {
                throw new AuthorizationException(self::NO_PERSONNEL);
            }
            if ($journal && ! $user->can('teaching-journals.view-all') && $journal->personnel_id !== $personnel->id) {
                throw new AuthorizationException('Anda hanya dapat mengubah jurnal milik sendiri.');
            }

            $attendanceRows = collect($data['attendances']);
            unset($data['attendances']);
            $members = $this->attendanceResolver->members($data);
            $memberIds = $members->pluck('student_id')->sort()->values();
            Student::whereIn('id', $memberIds)->orderBy('id')->lockForUpdate()->get(['id']);
            $automatic = $this->attendanceResolver->resolve($data, null, $members);
            $contextChanged = false;
            if ($journal) {
                $journal = TeachingJournal::whereKey($journal->id)->lockForUpdate()->firstOrFail();
                if (! $user->can('teaching-journals.view-all') && $journal->personnel_id !== $personnel->id) {
                    throw new AuthorizationException('Anda hanya dapat mengubah jurnal milik sendiri.');
                }
                foreach (['classroom_id', 'academic_year_id', 'semester_id', 'journal_date'] as $field) {
                    $old = $field === 'journal_date' ? $journal->journal_date->toDateString() : $journal->$field;
                    if ((string) $old !== (string) $data[$field]) {
                        $contextChanged = true;
                    }
                }
            }
            $previous = $journal ? $journal->attendances()->lockForUpdate()->get()->keyBy('student_id') : collect();
            if ($memberIds->all() !== $attendanceRows->pluck('student_id')->map(fn ($id) => (int) $id)->sort()->values()->all()) {
                throw ValidationException::withMessages(['attendances' => 'Absensi wajib diisi untuk seluruh siswa aktif pada rombel yang dipilih.']);
            }

            if ($contextChanged) {
                activity('akademik')->causedBy($user)->performedOn($journal)->withProperties(['konteks_lama' => $journal->only(['academic_year_id', 'semester_id', 'classroom_id', 'journal_date']), 'absensi_lama' => $previous->values()->toArray()])->log('Mengubah konteks jurnal dan memuat ulang absensi harian');
                $journal->attendances()->whereNotIn('student_id', $memberIds)->delete();
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

            $details = [];
            foreach ($attendanceRows as $index => $row) {
                $existing = $previous->get($row['student_id']);
                $mode = $row['mode'] ?? (! $contextChanged && $existing && $existing->origin !== 'daily' ? 'keep' : 'daily');
                if ($mode === 'keep' && $existing && ! $contextChanged) {
                    continue;
                }
                $values = collect($automatic->get($row['student_id']))->except(['name', 'student_id'])->all();
                if ($mode === 'manual') {
                    if (! in_array($row['status'] ?? null, ['present', 'sick', 'permitted', 'absent'], true) || ! trim($row['notes'] ?? '')) {
                        throw ValidationException::withMessages(["attendances.$index.notes" => 'Koreksi khusus pelajaran wajib memiliki status resmi dan alasan.']);
                    }
                    $values = array_merge($values, ['status' => $row['status'], 'notes' => $row['notes'], 'origin' => 'manual', 'corrected_by' => $user->id, 'corrected_at' => now()]);
                    activity('akademik')->causedBy($user)->performedOn($journal)->withProperties(['student_id' => $row['student_id'], 'sebelum' => $existing?->status?->value, 'sesudah' => $row['status'], 'alasan' => $row['notes']])->log('Koreksi absensi per pelajaran');
                } else {
                    if ($existing && $existing->origin !== 'daily') {
                        activity('akademik')->causedBy($user)->performedOn($journal)->withProperties(['student_id' => $row['student_id'], 'sebelum' => $existing->toArray(), 'sesudah' => $values])->log('Mengembalikan absensi pelajaran ke sumber harian');
                    }
                    $values += ['corrected_by' => null, 'corrected_at' => null];
                }
                $details[] = $values + ['teaching_journal_id' => $journal->id, 'student_id' => $row['student_id'], 'deleted_at' => null, 'created_at' => $existing?->created_at ?? now(), 'updated_at' => now()];
            }

            if ($details) {
                TeachingJournalAttendance::upsert($details, ['teaching_journal_id', 'student_id'], ['status', 'notes', 'origin', 'daily_source', 'arrival_at', 'corrected_by', 'corrected_at', 'deleted_at', 'updated_at']);
            }

            activity('akademik')->causedBy($user)->performedOn($journal)->withProperties(['tanggal' => $journal->journal_date->toDateString(), 'rombel_id' => $journal->classroom_id])->log($message);

            return $journal;
        });
    }

    public function activePersonnel(User $user): ?Personnel
    {
        return $this->personnelAccounts->handle($user);
    }

    public function delete(TeachingJournal $journal, User $user): void
    {
        if (! $user->can('teaching-journals.view-all') && $journal->personnel_id !== $user->personnel?->id) {
            throw new AuthorizationException;
        }
        DB::transaction(function () use ($journal, $user): void {
            activity('akademik')->causedBy($user)->withProperties(['tanggal' => $journal->journal_date->toDateString(), 'rombel_id' => $journal->classroom_id])->log('Menghapus Jurnal Mengajar');
            $journal->delete();
        });
    }
}
