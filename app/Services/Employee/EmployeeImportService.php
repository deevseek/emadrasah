<?php

declare(strict_types=1);

namespace App\Services\Employee;

use App\Enums\EmployeeStatus;
use App\Enums\EmploymentType;
use App\Enums\Gender;
use App\Models\Employee;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ZipArchive;

class EmployeeImportService
{
    private const COLUMN_ALIASES = [
        'nama_lengkap' => ['nama lengkap'],
        'gender' => ['l/p', 'lp'],
        'birth' => ['tempat tgl lahir', 'tempat tgl. lahir', 'tempat, tgl lahir', 'tempat, tgl. lahir', 'tempat tanggal lahir', 'tempat/tgl lahir', 'tempat/tgl. lahir', 'tempat/tanggal lahir', 'tempat dan tanggal lahir', 'ttl'],
        'birth_place' => ['tempat lahir'],
        'birth_date' => ['tanggal lahir', 'tgl lahir'],
        'employee_status' => ['status'],
        'employee_number' => ['nomor induk yayasan niy', 'nomor induk yayasan', 'niy'],
        'nip' => ['nip'],
        'rank_grade' => ['pangkat/golongan ruang', 'pangkat golongan ruang', 'pangkat/golongan', 'pangkat golongan'],
        'peg_id' => ['peg.id', 'peg id', 'pegid'],
        'last_education' => ['pendidikan terakhir'],
        'position' => ['jabatan'],
        'certification_status' => ['sertifikasi - impassing', 'sertifikasi impassing', 'sertifikasi'],
        'certification_subject' => ['mapel sertifikasi'],
        'weekly_teaching_hours' => ['jumlah jpl', 'jpl'],
        'bank_name' => ['jenis rekening'],
        'bank_account_number' => ['no. rekening', 'no rekening', 'nomor rekening'],
        'whatsapp' => ['no. hp/ wa aktif', 'no hp/wa aktif', 'no hp wa aktif', 'no. hp/wa aktif', 'no hp wa'],
        'email' => ['e-mail aktif', 'email aktif', 'email'],
    ];

    public function import(UploadedFile $file): array
    {
        $sheet = $this->readRows($file->getRealPath());
        [$headerRow, $columns] = $this->detectColumns($sheet);
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        DB::transaction(function () use ($sheet, $headerRow, $columns, &$created, &$updated, &$skipped, &$errors): void {
            foreach ($sheet as $rowNumber => $row) {
                if ($rowNumber <= $headerRow) {
                    continue;
                }

                $name = $this->value($row, $columns, 'nama_lengkap');
                if ($name === null) {
                    continue;
                }

                try {
                    $payload = $this->mapRow($row, $columns);
                    $employee = $this->findEmployee($payload);

                    if ($employee) {
                        $employee->update($payload);
                        $updated++;
                    } else {
                        Employee::create($payload);
                        $created++;
                    }
                } catch (\Throwable $e) {
                    $skipped++;
                    $errors[] = "Baris {$rowNumber}: {$e->getMessage()}";
                }
            }
        });

        activity('employees')->event('employee.imported')->withProperties(compact('created', 'updated', 'skipped'))->log('Import data personalia XLSX');

        return compact('created', 'updated', 'skipped', 'errors');
    }

    private function mapRow(array $row, array $columns): array
    {
        $birth = $this->value($row, $columns, 'birth');
        $birthPlace = $this->value($row, $columns, 'birth_place');
        $birthDate = $this->value($row, $columns, 'birth_date');
        $whatsapp = $this->normalizePhone($this->value($row, $columns, 'whatsapp'));
        $email = $this->email($this->value($row, $columns, 'email'));
        $position = $this->value($row, $columns, 'position') ?: 'Pegawai';

        return [
            'name' => $this->value($row, $columns, 'nama_lengkap'),
            'gender' => $this->gender($this->value($row, $columns, 'gender')),
            'birth_place' => $this->birthPlace($birth, $birthPlace),
            'birth_date' => $this->birthDate($birth, $birthDate),
            'employee_status' => $this->status($this->value($row, $columns, 'employee_status')),
            'employee_number' => $this->dashNull($this->value($row, $columns, 'employee_number')),
            'nip' => $this->dashNull($this->value($row, $columns, 'nip')),
            'rank_grade' => $this->dashNull($this->value($row, $columns, 'rank_grade')),
            'peg_id' => $this->dashNull($this->value($row, $columns, 'peg_id')),
            'last_education' => $this->dashNull($this->value($row, $columns, 'last_education')),
            'position' => $position,
            'employment_type' => $this->type($position),
            'certification_status' => $this->dashNull($this->value($row, $columns, 'certification_status')),
            'certification_subject' => $this->dashNull($this->value($row, $columns, 'certification_subject')),
            'weekly_teaching_hours' => $this->integer($this->value($row, $columns, 'weekly_teaching_hours')),
            'bank_name' => $this->dashNull($this->value($row, $columns, 'bank_name')),
            'bank_account_number' => $this->dashNull($this->value($row, $columns, 'bank_account_number')),
            'whatsapp' => $whatsapp,
            'phone' => $whatsapp,
            'email' => $email,
            'is_active' => true,
        ];
    }

    private function findEmployee(array $payload): ?Employee
    {
        return Employee::query()
            ->where(function ($query) use ($payload): void {
                foreach (['employee_number', 'peg_id', 'email'] as $field) {
                    if (! empty($payload[$field])) {
                        $query->orWhere($field, $payload[$field]);
                    }
                }
            })
            ->first();
    }

    private function detectColumns(array $sheet): array
    {
        foreach ($sheet as $rowNumber => $row) {
            $normalizedCells = array_map(fn ($value) => $this->normalizeHeader((string) $value), $row);
            if (! in_array('nama lengkap', $normalizedCells, true)) {
                continue;
            }

            $columns = [];
            foreach ($row as $index => $heading) {
                $normalized = $this->normalizeHeader((string) $heading);
                foreach (self::COLUMN_ALIASES as $field => $aliases) {
                    if (in_array($normalized, $aliases, true)) {
                        $columns[$field] = $index;
                    }
                }
            }

            foreach (['nama_lengkap', 'gender', 'employee_status', 'employee_number', 'position'] as $required) {
                if (! array_key_exists($required, $columns)) {
                    throw new \RuntimeException('Kolom wajib pada XLS tidak ditemukan: '.$required);
                }
            }

            return [$rowNumber, $columns];
        }

        throw new \RuntimeException('Header XLS tidak ditemukan. Pastikan file memakai format Data Personalia seperti contoh yang diunggah.');
    }

    private function readRows(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('Berkas XLSX tidak dapat dibuka.');
        }

        $shared = $this->sharedStrings($zip);
        $xml = simplexml_load_string($zip->getFromName('xl/worksheets/sheet1.xml') ?: '');
        $rows = [];

        foreach ($xml->sheetData->row ?? [] as $row) {
            $rowNumber = (int) $row['r'];
            $rows[$rowNumber] = [];

            foreach ($row->c as $cell) {
                $column = $this->columnIndex((string) $cell['r']);
                $rows[$rowNumber][$column] = $this->cellValue($cell, $shared);
            }
        }

        $zip->close();

        return $rows;
    }

    private function sharedStrings(ZipArchive $zip): array
    {
        $xml = simplexml_load_string($zip->getFromName('xl/sharedStrings.xml') ?: '<sst/>');
        $strings = [];

        foreach ($xml->si ?? [] as $item) {
            $strings[] = isset($item->t) ? (string) $item->t : trim(implode('', array_map('strval', iterator_to_array($item->xpath('.//t')))));
        }

        return $strings;
    }

    private function cellValue(\SimpleXMLElement $cell, array $shared): ?string
    {
        $type = (string) $cell['t'];
        if ($type === 's') {
            return $shared[(int) $cell->v] ?? null;
        }

        if ($type === 'inlineStr') {
            return trim(implode('', array_map('strval', iterator_to_array($cell->xpath('.//t')))));
        }

        return isset($cell->v) ? (string) $cell->v : null;
    }

    private function columnIndex(string $cell): int
    {
        preg_match('/[A-Z]+/', $cell, $matches);
        $index = 0;
        foreach (str_split($matches[0] ?? 'A') as $letter) {
            $index = ($index * 26) + ord($letter) - 64;
        }

        return $index - 1;
    }

    private function value(array $row, array $columns, string $field): ?string
    {
        return $this->clean($row[$columns[$field] ?? -1] ?? null);
    }

    private function clean(mixed $value): ?string
    {
        $clean = trim(preg_replace('/\s+/', ' ', (string) $value));

        return $clean === '' ? null : $clean;
    }

    private function dashNull(mixed $value): ?string
    {
        $clean = $this->clean($value);

        return $clean === '-' ? null : $clean;
    }

    private function integer(?string $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private function email(?string $value): ?string
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) ? $value : null;
    }

    private function gender(?string $value): string
    {
        return Str::upper((string) $value) === 'L' ? Gender::Male->value : Gender::Female->value;
    }

    private function status(?string $value): string
    {
        return match (Str::upper(trim((string) $value))) {
            'GTY' => EmployeeStatus::FoundationPermanentTeacher->value,
            'GTT' => EmployeeStatus::NonPermanentTeacher->value,
            'PTY' => EmployeeStatus::FoundationPermanentEmployee->value,
            'PTT' => EmployeeStatus::NonPermanentEmployee->value,
            default => EmployeeStatus::Other->value,
        };
    }

    private function type(?string $value): string
    {
        $position = Str::lower((string) $value);

        return match (true) {
            str_contains($position, 'kepala') => EmploymentType::Principal->value,
            str_contains($position, 'kelas') => EmploymentType::ClassTeacher->value,
            str_contains($position, 'btaq') => EmploymentType::BtaqTeacher->value,
            str_contains($position, 'usaha') => EmploymentType::Administration->value,
            str_contains($position, 'bersih') => EmploymentType::EducationStaff->value,
            default => EmploymentType::SubjectTeacher->value,
        };
    }

    private function birthPlace(?string $combinedValue, ?string $placeValue = null): ?string
    {
        if ($placeValue !== null) {
            return $this->clean($placeValue);
        }

        $parts = explode(',', (string) $combinedValue, 2);

        return $this->clean($parts[0] ?? null);
    }

    private function birthDate(?string $combinedValue, ?string $dateValue = null): ?string
    {
        $date = $dateValue;

        if ($date === null) {
            $parts = explode(',', (string) $combinedValue, 2);
            if (! isset($parts[1])) {
                return null;
            }

            $date = trim($parts[1]);
        }

        if (is_numeric($date)) {
            return CarbonImmutable::create(1899, 12, 30, 0, 0, 0, 'Asia/Jakarta')
                ->addDays((int) $date)
                ->toDateString();
        }

        $date = $this->normalizeIndonesianDate(trim($date));

        try {
            return CarbonImmutable::parse($date, 'Asia/Jakarta')->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeIndonesianDate(string $date): string
    {
        return str_ireplace(
            ['januari', 'februari', 'maret', 'mei', 'juni', 'juli', 'agustus', 'oktober', 'desember'],
            ['January', 'February', 'March', 'May', 'June', 'July', 'August', 'October', 'December'],
            $date,
        );
    }

    private function normalizePhone(?string $value): ?string
    {
        $clean = $this->clean($value);
        if (! $clean) {
            return null;
        }

        $number = preg_replace('/[^0-9+]/', '', $clean);

        return str_starts_with($number, '0') ? '+62'.substr($number, 1) : $number;
    }

    private function normalizeHeader(string $value): string
    {
        return Str::of($value)->lower()->replaceMatches('/[^a-z0-9\/\.\-]+/', ' ')->squish()->toString();
    }
}
