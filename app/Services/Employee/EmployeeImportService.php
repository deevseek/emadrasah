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
    public function import(UploadedFile $file): array
    {
        $rows = $this->readRows($file->getRealPath());
        $created = $updated = $skipped = 0; $errors = [];

        DB::transaction(function () use ($rows, &$created, &$updated, &$skipped, &$errors): void {
            foreach ($rows as $rowNumber => $row) {
                if ($rowNumber < 11 || empty($row[1])) { continue; }
                $name = trim((string) $row[1]);
                if ($name === '' || Str::upper($name) === 'NAMA LENGKAP') { continue; }
                try {
                    $payload = $this->mapRow($row);
                    $key = array_filter([$payload['employee_number'] ?? null, $payload['peg_id'] ?? null, $payload['email'] ?? null])[0] ?? null;
                    if (! $key) { $skipped++; $errors[] = "Baris {$rowNumber}: dilewati karena NIY, Peg.ID, dan email kosong."; continue; }
                    $employee = Employee::query()
                        ->when($payload['employee_number'] ?? null, fn ($q, $v) => $q->orWhere('employee_number', $v))
                        ->when($payload['peg_id'] ?? null, fn ($q, $v) => $q->orWhere('peg_id', $v))
                        ->when($payload['email'] ?? null, fn ($q, $v) => $q->orWhere('email', $v))
                        ->first();
                    $employee ? ($employee->update($payload) && $updated++) : (Employee::create($payload) && $created++);
                } catch (\Throwable $e) {
                    $skipped++; $errors[] = "Baris {$rowNumber}: {$e->getMessage()}";
                }
            }
        });

        activity('employees')->event('employee.imported')->withProperties(compact('created', 'updated', 'skipped'))->log('Import data personalia XLSX');
        return compact('created', 'updated', 'skipped', 'errors');
    }

    private function mapRow(array $r): array
    {
        return [
            'name' => trim((string) ($r[1] ?? '')), 'gender' => $this->gender($r[2] ?? null), 'birth_place' => $this->birthPlace($r[3] ?? null), 'birth_date' => $this->birthDate($r[3] ?? null),
            'employee_status' => $this->status($r[4] ?? null), 'employee_number' => $this->clean($r[5] ?? null), 'nip' => $this->dashNull($r[6] ?? null), 'rank_grade' => $this->clean($r[7] ?? null), 'peg_id' => $this->clean($r[8] ?? null),
            'last_education' => $this->clean($r[9] ?? null), 'position' => $this->clean($r[10] ?? null) ?: 'Pegawai', 'employment_type' => $this->type($r[10] ?? null), 'certification_status' => $this->clean($r[11] ?? null),
            'certification_subject' => $this->clean($r[12] ?? null), 'weekly_teaching_hours' => is_numeric($r[13] ?? null) ? (int) $r[13] : null, 'bank_name' => $this->clean($r[14] ?? null), 'bank_account_number' => $this->clean($r[15] ?? null),
            'whatsapp' => $this->normalizePhone($r[16] ?? null), 'phone' => $this->normalizePhone($r[16] ?? null), 'email' => filter_var($this->clean($r[17] ?? null), FILTER_VALIDATE_EMAIL) ? $this->clean($r[17] ?? null) : null, 'is_active' => true,
        ];
    }

    private function readRows(string $path): array
    {
        $zip = new ZipArchive(); if ($zip->open($path) !== true) { throw new \RuntimeException('Berkas XLSX tidak dapat dibuka.'); }
        $shared = $this->sharedStrings($zip); $xml = simplexml_load_string($zip->getFromName('xl/worksheets/sheet1.xml') ?: ''); $rows = [];
        foreach ($xml->sheetData->row ?? [] as $row) { $i=(int)$row['r']; $rows[$i]=[]; foreach ($row->c as $c) { $col=$this->colIndex((string)$c['r']); $v=(string)$c->v; $rows[$i][$col] = ((string)$c['t']==='s') ? ($shared[(int)$v] ?? '') : $v; } }
        $zip->close(); return $rows;
    }
    private function sharedStrings(ZipArchive $zip): array { $xml=simplexml_load_string($zip->getFromName('xl/sharedStrings.xml') ?: '<sst/>'); $out=[]; foreach ($xml->si ?? [] as $si) { $out[] = isset($si->t) ? (string)$si->t : trim(implode('', array_map('strval', iterator_to_array($si->xpath('.//t'))))); } return $out; }
    private function colIndex(string $cell): int { preg_match('/[A-Z]+/', $cell, $m); $n=0; foreach (str_split($m[0] ?? 'A') as $ch) $n=$n*26+ord($ch)-64; return $n-1; }
    private function clean(mixed $v): ?string { $s=trim((string)$v); return $s === '' ? null : $s; }
    private function dashNull(mixed $v): ?string { $s=$this->clean($v); return $s === '-' ? null : $s; }
    private function gender(mixed $v): string { return Str::upper((string)$v)==='L' ? Gender::Male->value : Gender::Female->value; }
    private function status(mixed $v): string { return match (Str::upper(trim((string)$v))) { 'GTY' => EmployeeStatus::FoundationPermanentTeacher->value, 'GTT' => EmployeeStatus::NonPermanentTeacher->value, 'PTY' => EmployeeStatus::FoundationPermanentEmployee->value, 'PTT' => EmployeeStatus::NonPermanentEmployee->value, default => EmployeeStatus::Other->value }; }
    private function type(mixed $v): string { $s=Str::lower((string)$v); return str_contains($s,'kepala') ? EmploymentType::Principal->value : (str_contains($s,'kelas') ? EmploymentType::ClassTeacher->value : (str_contains($s,'btaq') ? EmploymentType::BtaqTeacher->value : (str_contains($s,'usaha') ? EmploymentType::Administration->value : (str_contains($s,'bersih') ? EmploymentType::EducationStaff->value : EmploymentType::SubjectTeacher->value)))); }
    private function birthPlace(mixed $v): ?string { $p=explode(',', (string)$v, 2); return $this->clean($p[0] ?? null); }
    private function birthDate(mixed $v): ?string { $p=explode(',', (string)$v, 2); if (!isset($p[1])) return null; try { return CarbonImmutable::parse(trim($p[1]), 'Asia/Jakarta')->toDateString(); } catch (\Throwable) { return null; } }
    private function normalizePhone(mixed $v): ?string { $s=$this->clean($v); if (!$s) return null; $n=preg_replace('/[^0-9+]/','',$s); return str_starts_with($n,'0') ? '+62'.substr($n,1) : $n; }
}
