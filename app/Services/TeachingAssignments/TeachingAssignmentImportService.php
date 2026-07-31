<?php

declare(strict_types=1);

namespace App\Services\TeachingAssignments;

use App\Models\{Classroom, Personnel, Subject, TeachingImportBatch, TeachingImportRow, User};
use App\Services\Personnel\SimpleXlsxService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\{DB, Storage};
use RuntimeException;

class TeachingAssignmentImportService
{
    public const DATABASE = 'DATABASE';
    public const LEGGER = 'LEGGER';
    public const REFERENCE = 'PEMBAGIAN TUGAS MAPEL';

    public function __construct(
        private SimpleXlsxService $xlsx,
        private WorkbookNameMatcher $names,
        private WorkbookClassroomParser $classrooms,
    ) {}

    public function preview(UploadedFile $file, int $academicYearId, User $actor): TeachingImportBatch
    {
        $stored = $file->store('teaching-assignment-imports', 'local');
        try {
            $sheets = $this->canonicalSheets($this->xlsx->read(Storage::disk('local')->path($stored)));
            $this->assertRequiredSheets($sheets);

            return DB::transaction(function () use ($file, $stored, $sheets, $academicYearId, $actor): TeachingImportBatch {
                $batch = TeachingImportBatch::create(['academic_year_id' => $academicYearId, 'user_id' => $actor->id, 'original_filename' => $file->getClientOriginalName(), 'stored_filename' => $stored, 'status' => 'parsing', 'sheet_count' => count($sheets), 'sheet_names' => array_keys($sheets)]);
                $personnel = Personnel::where('is_active', true)->get();
                $subjects = Subject::where('is_active', true)->get();
                $classrooms = Classroom::with('gradeLevel')->where('academic_year_id', $academicYearId)->where('is_active', true)->get();
                $found = ['personnel' => collect(), 'subject' => collect(), 'classroom' => collect()];

                foreach ($sheets as $sheet => $rows) {
                    foreach ($rows as $number => $values) {
                        if (collect($values)->every(fn ($value) => trim((string) $value) === '')) continue;
                        $analysis = $this->analyzeRow($sheet, $values, $personnel, $subjects, $classrooms);
                        foreach (array_keys($found) as $type) $found[$type] = $found[$type]->merge($analysis[$type.'_ids']);
                        TeachingImportRow::create(['batch_id' => $batch->id, 'sheet_name' => $sheet, 'row_number' => $number, 'row_type' => $this->rowType($sheet), 'raw_data' => $values, 'normalized_data' => $analysis['normalized'], 'status' => $analysis['status'], 'messages' => $analysis['messages'], 'matched_personnel_id' => $analysis['personnel_ids'][0] ?? null, 'matched_subject_id' => $analysis['subject_ids'][0] ?? null, 'matched_classroom_id' => $analysis['classroom_ids'][0] ?? null]);
                    }
                }

                $counts = collect(['matched', 'unmatched', 'selection', 'review'])->mapWithKeys(fn ($status) => [$status => $batch->rows()->where('status', $status)->count()]);
                $summary = ['sheets' => array_keys($sheets), 'subjects_checked' => $found['subject']->unique()->count(), 'personnel_checked' => $found['personnel']->unique()->count(), 'classrooms_checked' => $found['classroom']->unique()->count(), 'source_sheets' => [self::DATABASE, self::LEGGER], 'reference_sheet' => array_key_exists(self::REFERENCE, $sheets) ? self::REFERENCE : null];
                $batch->update(['status' => 'previewed', 'subject_count' => $summary['subjects_checked'], 'personnel_count' => $summary['personnel_checked'], 'classroom_count' => $summary['classrooms_checked'], 'matched_rows' => $counts['matched'], 'unmatched_rows' => $counts['unmatched'], 'selection_rows' => $counts['selection'], 'review_rows' => $counts['review'], 'summary' => $summary]);
                activity('teaching-assignments')->causedBy($actor)->performedOn($batch)->withProperties($summary)->log('Membuat pratinjau import pembagian tugas XLSX.');

                return $batch->fresh();
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($stored);
            throw $exception;
        }
    }

    public function canonicalSheets(array $sheets): array
    {
        $wanted = [self::DATABASE, self::LEGGER, self::REFERENCE];
        $result = [];
        foreach ($sheets as $name => $rows) {
            $canonical = collect($wanted)->first(fn ($value) => mb_strtoupper(trim($name)) === $value);
            if ($canonical) $result[$canonical] = $rows;
        }
        return $result;
    }

    public function assertRequiredSheets(array $sheets): void
    {
        $missing = array_values(array_diff([self::DATABASE, self::LEGGER], array_keys($sheets)));
        if ($missing !== []) throw new RuntimeException('Sheet wajib tidak ditemukan: '.implode(', ', $missing).'.');
    }

    private function analyzeRow(string $sheet, array $values, Collection $personnel, Collection $subjects, Collection $classrooms): array
    {
        $ids = ['personnel' => [], 'subject' => [], 'classroom' => []]; $ambiguous = false;
        foreach ($values as $value) {
            $text = trim((string) $value); if ($text === '') continue;
            $person = $this->names->match($text, $personnel); $subject = $this->names->match($text, $subjects, 'name'); $room = $this->classrooms->match($text, $classrooms);
            if ($person['match']) $ids['personnel'][] = $person['match']->id;
            if ($subject['match']) $ids['subject'][] = $subject['match']->id;
            if ($room['match']) $ids['classroom'][] = $room['match']->id;
            $ambiguous = $ambiguous || in_array('selection', [$person['status'], $subject['status'], $room['status']], true);
            if (preg_match('/,|&|\bdan\b/iu', $text) && preg_match('/\bkelas\b/iu', $text)) $ids['classroom'] = [...$ids['classroom'], ...$this->classrooms->expand($text, $classrooms)->pluck('id')->all()];
        }
        foreach ($ids as &$valuesForType) $valuesForType = array_values(array_unique($valuesForType)); unset($valuesForType);
        $hasMatch = collect($ids)->flatten()->isNotEmpty();
        $homeroomComparison = $this->homeroomComparison($sheet, $ids, $classrooms);
        $status = $homeroomComparison === 'different' ? 'review' : ($ambiguous ? 'selection' : ($hasMatch ? 'matched' : ($sheet === self::LEGGER ? 'unmatched' : 'review')));
        $messages = match (true) { $homeroomComparison === 'different' => ['Wali kelas pada XLSX berbeda dengan aplikasi dan tidak akan diganti otomatis.'], $status === 'matched' => ['Data referensi ditemukan pada aplikasi.'], $status === 'selection' => ['Ditemukan lebih dari satu kandidat; operator perlu memilih.'], $status === 'unmatched' => ['Data sumber belum cocok dengan data aplikasi.'], default => ['Baris referensi perlu diperiksa.'] };
        return ['status' => $status, 'messages' => $messages, 'normalized' => ['cells' => collect($values)->map(fn ($value) => $this->names->normalize((string) $value))->all(), 'personnel_ids' => $ids['personnel'], 'subject_ids' => $ids['subject'], 'classroom_ids' => $ids['classroom'], 'homeroom_comparison' => $homeroomComparison], 'personnel_ids' => $ids['personnel'], 'subject_ids' => $ids['subject'], 'classroom_ids' => $ids['classroom']];
    }

    private function homeroomComparison(string $sheet, array $ids, Collection $classrooms): ?string
    {
        if ($sheet !== self::DATABASE || count($ids['personnel']) !== 1 || count($ids['classroom']) !== 1) return null;
        $classroom = $classrooms->firstWhere('id', $ids['classroom'][0]);
        if (! $classroom || ! $classroom->homeroom_personnel_id) return 'not_set';
        return (int) $classroom->homeroom_personnel_id === (int) $ids['personnel'][0] ? 'same' : 'different';
    }

    private function rowType(string $sheet): string
    {
        return match ($sheet) { self::DATABASE => 'database', self::LEGGER => 'teaching_assignment', default => 'validation_reference' };
    }
}
