<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BtaqGroup;
use App\Models\BtaqGroupStudent;
use App\Models\BtaqJournal;
use App\Models\BtaqJournalStudent;
use App\Models\StudentAttendance;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BtaqService
{
    public function addMembers(BtaqGroup $group, array $studentIds, int $userId): void
    {
        DB::transaction(function () use ($group, $studentIds, $userId): void {
            $uniqueStudentIds = array_values(array_unique($studentIds));
            $activeCount = BtaqGroupStudent::where('btaq_group_id', $group->id)
                ->where('status', 'active')
                ->count();

            if ($group->capacity && $activeCount + count($uniqueStudentIds) > $group->capacity) {
                throw ValidationException::withMessages([
                    'students' => 'Kapasitas kelompok tidak mencukupi.',
                ]);
            }

            foreach ($uniqueStudentIds as $studentId) {
                $exists = BtaqGroupStudent::query()
                    ->join('btaq_groups', 'btaq_groups.id', '=', 'btaq_group_students.btaq_group_id')
                    ->where('btaq_groups.semester_id', $group->semester_id)
                    ->where('btaq_group_students.student_id', $studentId)
                    ->where('btaq_group_students.status', 'active')
                    ->exists();

                if ($exists) {
                    throw ValidationException::withMessages([
                        'students' => 'Siswa sudah aktif pada kelompok BTAQ semester ini.',
                    ]);
                }

                BtaqGroupStudent::create([
                    'btaq_group_id' => $group->id,
                    'student_id' => $studentId,
                    'joined_at' => now()->toDateString(),
                    'status' => 'active',
                ]);
            }

            activity('btaq')
                ->performedOn($group)
                ->causedBy(auth()->user())
                ->event('btaq.members.updated')
                ->log('Anggota BTAQ diperbarui');
        });
    }

    public function saveJournal(
        array $data,
        array $students,
        int $userId,
        ?BtaqJournal $journal = null
    ): BtaqJournal {
        return DB::transaction(function () use ($data, $students, $userId, $journal): BtaqJournal {
            if ($journal && $journal->status === 'submitted') {
                throw ValidationException::withMessages([
                    'status' => 'Jurnal submitted tidak dapat diedit.',
                ]);
            }

            $journal = BtaqJournal::updateOrCreate(
                ['id' => $journal?->id],
                $data + ['created_by' => $userId]
            );

            foreach ($students as $studentId => $payload) {
                BtaqJournalStudent::updateOrCreate(
                    [
                        'btaq_journal_id' => $journal->id,
                        'student_id' => $studentId,
                    ],
                    $payload
                );
            }

            activity('btaq')
                ->performedOn($journal)
                ->causedBy(auth()->user())
                ->event('btaq.journal.saved')
                ->log('Jurnal BTAQ disimpan');

            return $journal;
        });
    }

    public function attendanceFor(int $studentId, string $date): string
    {
        return StudentAttendance::where('student_id', $studentId)
            ->whereDate('attendance_date', $date)
            ->value('status') ?? 'present';
    }
}
