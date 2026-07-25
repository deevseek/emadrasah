<?php

declare(strict_types=1);

namespace App\Services\Academic\Imports;

use App\Enums\{DayOfWeek, ScheduleEntryType};
use App\Models\{ImportBatch, LessonSchedule, TeachingAssignment};
use App\Services\Academic\{ScheduleConflictService, ScheduleService, SharedScheduleSessionValidator};
use Illuminate\Support\Facades\{Auth, DB};

final class LessonScheduleImportService
{
    public function __construct(private SimpleXlsx $xlsx, private ImportMatcher $matcher, private ScheduleService $schedules, private SharedScheduleSessionValidator $sharedValidator, private ScheduleConflictService $conflicts) {}

    public function preview(string $path, int $year, int $semester): array
    {
        $result = [];
        foreach ($this->xlsx->read($path) as $index => $source) {
            $type = ScheduleEntryType::tryFrom(mb_strtolower(trim((string) ($source['jenis_slot'] ?? '')))) ?? ScheduleEntryType::Lesson;
            $day = $this->day((string) ($source['hari'] ?? ''));
            [$start, $end] = $this->times($source);
            $class = $this->matcher->classroom($year, $source['kode_kelas'] ?? null, $source['kelas'] ?? null);
            $status = $class['status'] ?: 'valid_new';
            $assignment = null;
            $subject = ['model' => null, 'status' => null];

            if (! $day || ! $start || ! $end || $end <= $start) {
                $status = 'invalid_time';
            }
            if ($type === ScheduleEntryType::Lesson && $class['model']) {
                $subject = $this->matcher->subject($source['kode_mata_pelajaran'] ?? null, $source['mata_pelajaran'] ?? null, $class['model']->grade_level_id);
                if (! $subject['model']) {
                    $status = $subject['status'];
                } else {
                    $query = TeachingAssignment::with('employee')->where(['academic_year_id' => $year, 'semester_id' => $semester, 'classroom_id' => $class['model']->id, 'subject_id' => $subject['model']->id, 'is_active' => true]);
                    if (filled($source['nomor_pegawai'] ?? null)) {
                        $query->whereHas('employee', fn ($q) => $q->where('employee_number', trim((string) $source['nomor_pegawai'])));
                    } elseif (filled($source['guru'] ?? null)) {
                        $teacher = $this->normalize((string) $source['guru']);
                        $matches = $query->get()->filter(fn ($item) => $this->normalize((string) $item->employee?->name) === $teacher);
                        $assignment = $matches->count() === 1 ? $matches->first() : null;
                        $status = $matches->count() > 1 ? 'assignment_ambiguous' : ($matches->isEmpty() ? 'assignment_not_found' : $status);
                    }
                    if (! $assignment) {
                        $matches = $query->get();
                        $assignment = $matches->count() === 1 ? $matches->first() : null;
                        if ($matches->count() !== 1) {
                            $status = $matches->count() > 1 ? 'assignment_ambiguous' : 'assignment_not_found';
                        }
                    }
                }
            }
            if ($type !== ScheduleEntryType::Lesson && blank($source['nama_kegiatan'] ?? null)) {
                $status = 'validation_error';
            }
            $code = $this->nullableTrim($source['kode_sesi_bersama'] ?? null);
            $name = $this->nullableTrim($source['nama_sesi_bersama'] ?? null);
            $result[] = ['line' => $index + 2, 'source' => $source, 'status' => $status, 'academic_year_id' => $year, 'semester_id' => $semester,
                'classroom_id' => $class['model']?->id, 'assignment_id' => $assignment?->id, 'employee_id' => $assignment?->employee_id,
                'subject_id' => $subject['model']?->id, 'entry_type' => $type->value, 'day' => $day?->value, 'starts_at' => $start, 'ends_at' => $end,
                'shared_session_code' => $code, 'shared_session_name' => $name];
        }

        foreach ($this->sharedValidator->validate($result) as $index => $status) {
            $result[$index]['status'] = $status;
        }
        $this->classifyConflictsAndChanges($result);
        $counts = array_count_values(array_column($result, 'status'));
        $processable = ['valid_new', 'valid_update', 'valid_shared_session', 'unchanged'];

        return ['rows' => $result, 'summary' => ['total' => count($result), 'valid' => collect($result)->whereIn('status', $processable)->count(), 'shared_sessions' => collect($result)->where('status', 'valid_shared_session')->count()] + $counts];
    }

    public function process(array $rows, int $year, int $semester, string $filename): ImportBatch
    {
        return DB::transaction(function () use ($rows, $year, $semester, $filename) {
            $valid = ['valid_new', 'valid_update', 'valid_shared_session', 'unchanged'];
            $batch = ImportBatch::create(['type' => 'lesson_schedule', 'original_filename' => $filename, 'academic_year_id' => $year, 'semester_id' => $semester, 'status' => 'processing', 'total_rows' => count($rows), 'valid_rows' => collect($rows)->whereIn('status', $valid)->count(), 'imported_by' => Auth::id(), 'started_at' => now()]);
            $created = $updated = 0;
            $updatedSnapshots = [];
            foreach ($rows as $row) {
                if (! in_array($row['status'], ['valid_new', 'valid_update', 'valid_shared_session'], true)) continue;
                $source = $row['source'];
                $payload = ['entry_type' => $row['entry_type'], 'teaching_assignment_id' => $row['assignment_id'], 'academic_year_id' => $year, 'semester_id' => $semester, 'classroom_id' => $row['classroom_id'], 'day_of_week' => $row['day'], 'starts_at' => $row['starts_at'], 'ends_at' => $row['ends_at'], 'lesson_hours' => $row['entry_type'] === 'lesson' ? max(1, (int) ($source['jp'] ?? 1)) : 1, 'activity_name' => $row['entry_type'] === 'lesson' ? null : $source['nama_kegiatan'], 'counts_as_teaching_hour' => $row['entry_type'] === 'lesson', 'room' => $this->nullableTrim($source['ruangan'] ?? null), 'notes' => $this->nullableTrim($source['keterangan'] ?? null), 'shared_session_code' => $row['entry_type'] === 'lesson' ? $row['shared_session_code'] : null, 'shared_session_name' => $row['entry_type'] === 'lesson' ? $row['shared_session_name'] : null, 'source_reference' => $filename.':'.$row['line'], 'import_batch_id' => $batch->id, 'is_active' => true];
                $existing = ! empty($row['existing_id']) ? LessonSchedule::find($row['existing_id']) : null;
                if ($existing) {
                    $updatedSnapshots[(string) $existing->id] = $existing->getAttributes();
                }
                $this->schedules->save($payload, $existing);
                $existing ? $updated++ : $created++;
            }
            $skipped = count($rows) - $created - $updated;
            $batch->update(['status' => 'completed', 'imported_rows' => $created, 'updated_rows' => $updated, 'skipped_rows' => $skipped, 'error_rows' => collect($rows)->whereNotIn('status', $valid)->count(), 'metadata' => ['updated_schedule_snapshots' => $updatedSnapshots], 'finished_at' => now()]);
            return $batch;
        });
    }

    private function classifyConflictsAndChanges(array &$rows): void
    {
        $accepted = [];
        foreach ($rows as &$row) {
            if ($row['status'] !== 'valid_new') continue;
            $natural = LessonSchedule::where(['academic_year_id' => $row['academic_year_id'], 'semester_id' => $row['semester_id'], 'classroom_id' => $row['classroom_id'], 'day_of_week' => $row['day'], 'entry_type' => $row['entry_type']])->where('starts_at', $row['starts_at'])->where('ends_at', $row['ends_at'])->first();
            if ($natural) {
                $row['existing_id'] = $natural->id;
                $same = (int) $natural->teaching_assignment_id === (int) $row['assignment_id'] && (int) $natural->subject_id === (int) $row['subject_id'] && $natural->shared_session_code === $row['shared_session_code'] && $natural->shared_session_name === $row['shared_session_name'];
                $row['status'] = $same ? 'unchanged' : 'valid_update';
                continue;
            }
            $incoming = ['employee_id' => $row['employee_id'], 'subject_id' => $row['subject_id'], 'semester_id' => $row['semester_id'], 'day_of_week' => $row['day'], 'starts_at' => $row['starts_at'], 'ends_at' => $row['ends_at'], 'classroom_id' => $row['classroom_id'], 'shared_session_code' => $row['shared_session_code']];
            $base = LessonSchedule::where(['semester_id' => $row['semester_id'], 'day_of_week' => $row['day'], 'is_active' => true])->where('starts_at', '<', $row['ends_at'])->where('ends_at', '>', $row['starts_at']);
            if ((clone $base)->where('classroom_id', $row['classroom_id'])->exists()) $row['status'] = 'classroom_conflict';
            elseif (($teacher = (clone $base)->where('employee_id', $row['employee_id'])->get()->first(fn ($e) => ! $this->conflicts->belongsToSameSharedSession($incoming, $e)))) $row['status'] = 'teacher_conflict';
            elseif (filled($row['source']['ruangan'] ?? null) && ($room = (clone $base)->where('room', trim((string) $row['source']['ruangan']))->get()->first(fn ($e) => ! $this->conflicts->belongsToSameSharedSession($incoming, $e)))) $row['status'] = 'room_conflict';
            foreach ($accepted as $previous) {
                if ($row['day'] !== $previous['day'] || $row['starts_at'] >= $previous['ends_at'] || $row['ends_at'] <= $previous['starts_at']) continue;
                if ($row['classroom_id'] === $previous['classroom_id']) $row['status'] = 'classroom_conflict';
                elseif ($row['employee_id'] === $previous['employee_id'] && ! $this->sameSharedRows($row, $previous)) $row['status'] = 'teacher_conflict';
                elseif (filled($row['source']['ruangan'] ?? null) && ($row['source']['ruangan'] === ($previous['source']['ruangan'] ?? null)) && ! $this->sameSharedRows($row, $previous)) $row['status'] = 'room_conflict';
            }
            if ($row['status'] === 'valid_new') $row['status'] = filled($row['shared_session_code']) ? 'valid_shared_session' : 'valid_new';
            if (in_array($row['status'], ['valid_new', 'valid_shared_session'], true)) $accepted[] = $row;
        }
    }

    private function sameSharedRows(array $a, array $b): bool { return filled($a['shared_session_code']) && $a['shared_session_code'] === $b['shared_session_code'] && $a['employee_id'] === $b['employee_id'] && $a['subject_id'] === $b['subject_id'] && $a['semester_id'] === $b['semester_id'] && $a['day'] === $b['day'] && $a['starts_at'] === $b['starts_at'] && $a['ends_at'] === $b['ends_at'] && $a['classroom_id'] !== $b['classroom_id']; }
    private function nullableTrim(mixed $value): ?string { $value = trim((string) $value); return $value === '' ? null : $value; }
    private function normalize(string $value): string { return preg_replace('/[^a-z0-9]+/u', '', mb_strtolower(trim($value))) ?? ''; }
    private function day(string $value): ?DayOfWeek { return DayOfWeek::tryFrom(mb_strtolower(trim($value))); }
    private function times(array $row): array { $a = str_replace('.', ':', trim((string) ($row['waktu_mulai'] ?? ''))); $b = str_replace('.', ':', trim((string) ($row['waktu_selesai'] ?? ''))); if (str_contains($a, '-') && $b === '') [$a, $b] = array_map('trim', explode('-', $a, 2)); foreach ([&$a, &$b] as &$value) if (preg_match('/^\d{1,2}:\d{2}$/', $value)) $value = str_pad($value, 5, '0', STR_PAD_LEFT); return [preg_match('/^\d{2}:\d{2}$/', $a) ? $a : null, preg_match('/^\d{2}:\d{2}$/', $b) ? $b : null]; }
}
