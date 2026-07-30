<?php

declare(strict_types=1);

namespace App\Services\Personnel;

use App\Models\Personnel;
use App\Models\PersonnelImportBatch;
use App\Models\PersonnelImportRow;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PersonnelImportService
{
    public const HEADERS = ['NO', 'NAMA LENGKAP', 'L/P', 'TEMPAT TGL LAHIR', 'STATUS', 'NOMOR INDUK YAYASAN (NIY)', 'NIP', 'PANGKAT/GOLONGAN RUANG', 'PEG ID', 'PENDIDIKAN TERAKHIR', 'JABATAN', 'SERTIFIKASI IMPASSING', 'MAPEL SERTIFIKASI', 'JUMLAH JPL', 'JENIS REKENING', 'NO REKENING', 'NO HP/WA AKTIF', 'EMAIL AKTIF'];

    private const PLACEHOLDERS = ['-', '–', '—', 'N/A', 'NA', 'NULL', 'NIHIL', 'TIDAK ADA'];

    private const NULLABLE_TEXT_FIELDS = [
        'foundation_employee_number', 'nip', 'rank_grade', 'external_employee_id',
        'last_education', 'certification_status', 'certification_subject', 'bank_name',
        'bank_account_number', 'phone', 'email',
    ];

    private const NUMBER_FIELDS = [
        'foundation_employee_number', 'nip', 'external_employee_id', 'bank_account_number', 'phone',
    ];

    public function __construct(private SimpleXlsxService $xlsx, private PersonnelDuplicateService $duplicates) {}

    public function preview(UploadedFile $file, string $strategy, User $actor): PersonnelImportBatch
    {
        $stored = $file->store('personnel-imports', 'local');
        $batch = PersonnelImportBatch::create(['user_id' => $actor->id, 'original_filename' => $file->getClientOriginalName(), 'stored_filename' => $stored, 'status' => 'uploaded', 'duplicate_strategy' => $strategy]);

        try {
            $sheets = $this->xlsx->read(Storage::disk('local')->path($stored));
            [$rows, $header] = $this->locate($sheets);
            foreach ($rows as $number => $raw) {
                if ($number <= $header || collect($raw)->filter(fn ($v) => trim((string) $v) !== '')->isEmpty()) {
                    continue;
                }
                $mapped = $this->map($rows[$header], $raw);
                $normalized = $this->normalize($mapped);
                [$status, $messages, $match] = $this->validate($normalized);
                PersonnelImportRow::create(['batch_id' => $batch->id, 'row_number' => $number, 'raw_data' => $mapped, 'normalized_data' => $normalized, 'status' => $status, 'messages' => $messages, 'matched_personnel_id' => $match?->id]);
            }
            $this->summarize($batch, 'previewed');

            return $batch->fresh();
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($stored);
            $batch->update(['status' => 'failed']);
            throw $e;
        }
    }

    public function confirm(PersonnelImportBatch $batch, User $actor): PersonnelImportBatch
    {
        abort_unless($batch->user_id === $actor->id && $batch->status === 'previewed', 403);
        $batch->update(['status' => 'processing', 'started_at' => now()]);
        $counts = ['imported_rows' => 0, 'updated_rows' => 0, 'skipped_rows' => 0, 'failed_rows' => 0];
        $batch->rows()->whereIn('status', ['valid', 'warning', 'duplicate'])->orderBy('id')->chunk(200, function ($rows) use ($batch, $actor, &$counts): void {
            DB::transaction(function () use ($rows, $batch, $actor, &$counts): void {
                foreach ($rows as $row) {
                    try {
                        $data = array_filter($row->normalized_data, fn ($v) => $v !== null && $v !== '');
                        if ($row->matched_personnel_id) {
                            if ($batch->duplicate_strategy === 'skip') {
                                $counts['skipped_rows']++;
                                continue;
                            }
                            unset($data['user_id'], $data['is_active']);
                            $data['updated_by'] = $actor->id;
                            Personnel::whereKey($row->matched_personnel_id)->update($data);
                            $counts['updated_rows']++;
                        } else {
                            Personnel::create([...$data, 'is_active' => true, 'created_by' => $actor->id, 'updated_by' => $actor->id]);
                            $counts['imported_rows']++;
                        }
                    } catch (\Throwable) {
                        $counts['failed_rows']++;
                    }
                }
            });
        });
        $batch->update([...$counts, 'status' => 'completed', 'completed_at' => now(), 'summary' => $counts]);
        Storage::disk('local')->delete($batch->stored_filename);
        activity('personnel')->causedBy($actor)->log('Mengimpor '.($counts['imported_rows'] + $counts['updated_rows']).' data personalia dari file XLSX.');

        return $batch->fresh();
    }

    public function parseBirth(mixed $value): array
    {
        $v = trim((string) $value);
        if ($v === '') {
            return [null, null, null];
        }
        if (is_numeric($v)) {
            try {
                return [null, Carbon::create(1899, 12, 30)->addDays((int) $v)->format('Y-m-d'), null];
            } catch (\Throwable) {
            }
        }
        $months = ['JANUARI' => 1, 'FEBRUARI' => 2, 'MARET' => 3, 'APRIL' => 4, 'MEI' => 5, 'JUNI' => 6, 'JULI' => 7, 'AGUSTUS' => 8, 'SEPTEMBER' => 9, 'OKTOBER' => 10, 'NOVEMBER' => 11, 'DESEMBER' => 12];
        if (preg_match('/^(.*?),\s*(\d{1,2})\s+('.implode('|', array_keys($months)).')\s+(\d{4})$/iu', $v, $m)) {
            return [trim($m[1]), sprintf('%04d-%02d-%02d', $m[4], $months[strtoupper($m[3])], $m[2]), null];
        }
        if (preg_match('/^(.*?),\s*(\d{1,2})[\/-](\d{1,2})[\/-](\d{4})$/', $v, $m)) {
            return [trim($m[1]), sprintf('%04d-%02d-%02d', $m[4], $m[3], $m[2]), null];
        }
        if (preg_match('/^(.*?),\s*(\d{4}-\d{2}-\d{2})$/', $v, $m)) {
            return [trim($m[1]), $m[2], null];
        }

        return [$v, null, 'Format tanggal lahir tidak dapat dibaca.'];
    }

    private function locate(array $sheets): array
    {
        foreach ($sheets as $rows) {
            foreach ($rows as $n => $row) {
                $norm = array_map(fn ($v) => $this->header($v), $row);
                if (count(array_intersect($norm, self::HEADERS)) >= 8) {
                    return [$rows, $n];
                }
            }
        }
        throw new \RuntimeException('Header Data Personalia tidak ditemukan.');
    }

    private function header($v): string
    {
        $v = strtoupper(trim(preg_replace('/\s+/', ' ', str_replace(["\r", "\n", '.', '-'], [' ', ' ', '', ' '], (string) $v))));

        return match ($v) {
            'E MAIL AKTIF', 'EMAIL AKTIF' => 'EMAIL AKTIF',
            'PEGID', 'PEG ID', 'PEGAWAI ID' => 'PEG ID',
            'NO HP/WA AKTIF' => 'NO HP/WA AKTIF',
            'TEMPAT, TGL LAHIR', 'TEMPAT, TGL LAHIR ' => 'TEMPAT TGL LAHIR',
            'NO REKENING' => 'NO REKENING',
            'SERTIFIKASI IMPASSING' => 'SERTIFIKASI IMPASSING',
            default => $v,
        };
    }

    private function map(array $headers, array $row): array
    {
        $keys = ['NAMA LENGKAP' => 'full_name', 'L/P' => 'gender', 'TEMPAT TGL LAHIR' => 'birth', 'STATUS' => 'employment_status', 'NOMOR INDUK YAYASAN (NIY)' => 'foundation_employee_number', 'NIP' => 'nip', 'PANGKAT/GOLONGAN RUANG' => 'rank_grade', 'PEG ID' => 'external_employee_id', 'PENDIDIKAN TERAKHIR' => 'last_education', 'JABATAN' => 'position', 'SERTIFIKASI IMPASSING' => 'certification_status', 'MAPEL SERTIFIKASI' => 'certification_subject', 'JUMLAH JPL' => 'weekly_teaching_hours', 'JENIS REKENING' => 'bank_name', 'NO REKENING' => 'bank_account_number', 'NO HP/WA AKTIF' => 'phone', 'EMAIL AKTIF' => 'email'];
        $out = [];
        foreach ($headers as $column => $value) {
            if (isset($keys[$this->header($value)])) {
                $out[$keys[$this->header($value)]] = $row[$column] ?? null;
            }
        }

        return $out;
    }

    private function normalize(array $data): array
    {
        foreach ($data as &$value) {
            $value = is_string($value) ? trim($value, " \t\n\r\0\x0B'") : $value;
        }
        unset($value);

        foreach (self::NULLABLE_TEXT_FIELDS as $field) {
            $data[$field] = $this->nullableText($data[$field] ?? null);
        }
        foreach (self::NUMBER_FIELDS as $field) {
            $data[$field] = $this->nullableNumber($data[$field] ?? null);
        }

        [$data['birth_place'], $data['birth_date'], $warning] = $this->parseBirth($data['birth'] ?? null);
        unset($data['birth']);
        $gender = strtoupper((string) ($data['gender'] ?? ''));
        $data['gender'] = in_array($gender, ['L', 'LAKI-LAKI', 'PRIA']) ? 'male' : (in_array($gender, ['P', 'PEREMPUAN', 'WANITA']) ? 'female' : $gender);
        $data['employment_status'] = strtoupper((string) ($data['employment_status'] ?? ''));
        $data['email'] = $data['email'] !== null ? strtolower($data['email']) : null;
        $data['phone'] = $data['phone'] !== null ? str_replace(' ', '', $data['phone']) : null;
        if (isset($data['weekly_teaching_hours']) && $data['weekly_teaching_hours'] !== '') {
            $data['weekly_teaching_hours'] = is_numeric($data['weekly_teaching_hours']) ? (int) $data['weekly_teaching_hours'] : $data['weekly_teaching_hours'];
        } else {
            $data['weekly_teaching_hours'] = null;
        }
        $data['_birth_warning'] = $warning;

        return $data;
    }

    private function nullableText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim((string) $value);

        if ($value === '' || in_array(mb_strtoupper($value), self::PLACEHOLDERS, true)) {
            return null;
        }

        return $value;
    }

    private function nullableNumber(mixed $value): ?string
    {
        $value = $this->nullableText($value);

        return $value === null ? null : preg_replace('/\.0$/', '', $value);
    }

    private function validate(array $data): array
    {
        $warning = array_filter([$data['_birth_warning'] ?? null]);
        unset($data['_birth_warning']);
        $validator = Validator::make($data, ['full_name' => 'required|max:200', 'gender' => 'required|in:male,female', 'employment_status' => 'required|max:50', 'position' => 'required|max:150', 'email' => 'nullable|email|max:200', 'weekly_teaching_hours' => 'nullable|integer|between:0,100']);
        $messages = $validator->errors()->all();
        $duplicate = $this->duplicates->find($data);
        if ($duplicate['conflict']) {
            $messages[] = 'Data memiliki beberapa kecocokan berbeda dan harus diperiksa manual.';
        } elseif ($duplicate['match']) {
            $messages[] = 'Data sudah tersedia berdasarkan '.$this->matchedByLabel($duplicate['matched_by']).'.';
        }
        $status = $validator->errors()->isNotEmpty() || $duplicate['conflict'] ? 'invalid' : ($duplicate['match'] ? 'duplicate' : ($warning ? 'warning' : 'valid'));

        return [$status, [...$messages, ...$warning], $duplicate['match']];
    }

    private function matchedByLabel(?string $matchedBy): string
    {
        return match ($matchedBy) {
            'foundation_employee_number' => 'NIY',
            'nip' => 'NIP',
            'external_employee_id' => 'Peg.ID',
            'email' => 'email',
            'name_and_birth_date' => 'nama lengkap dan tanggal lahir',
            default => 'identifier yang valid',
        };
    }

    private function summarize(PersonnelImportBatch $batch, string $status): void
    {
        $query = $batch->rows();
        $batch->update(['status' => $status, 'total_rows' => (clone $query)->count(), 'valid_rows' => (clone $query)->where('status', 'valid')->count(), 'warning_rows' => (clone $query)->where('status', 'warning')->count(), 'invalid_rows' => (clone $query)->where('status', 'invalid')->count(), 'duplicate_rows' => (clone $query)->where('status', 'duplicate')->count()]);
    }
}
