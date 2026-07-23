<?php

declare(strict_types=1);

namespace App\Services\StudentAffairs;

use App\Enums\AdmissionType;
use App\Enums\EnrollmentStatus;
use App\Enums\Gender;
use App\Enums\StudentStatus;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Guardian;
use App\Models\GradeLevel;
use App\Models\Student;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ZipArchive;

class StudentImportService
{
    private const COLUMN_ALIASES = [
        'no' => ['no'], 'name' => ['nama lengkap'], 'nisn' => ['nisn'], 'nik' => ['nik'],
        'birth_place' => ['tempat lahir'], 'birth_date' => ['tanggal lahir'], 'classroom' => ['tingkat rombel', 'tingkat - rombel'],
        'age' => ['umur'], 'status' => ['status'], 'gender' => ['jenis kelamin'], 'address' => ['alamat'],
        'phone' => ['no telepon', 'no. telepon'], 'special_needs' => ['kebutuhan khusus'], 'disability' => ['disabilitas'],
        'kip_pip_number' => ['nomor kip/pip', 'nomor kip pip'], 'father_name' => ['nama ayah kandung'],
        'mother_name' => ['nama ibu kandung'], 'guardian_name' => ['nama wali'],
    ];

    public function import(UploadedFile $file): array
    {
        $sheet = $this->readRows($file->getRealPath());
        [$headerRow, $columns] = $this->detectColumns($sheet);
        $created = $updated = $skipped = 0; $errors = [];

        DB::transaction(function () use ($sheet, $headerRow, $columns, &$created, &$updated, &$skipped, &$errors): void {
            foreach ($sheet as $rowNumber => $row) {
                if ($rowNumber <= $headerRow || ! $this->value($row, $columns, 'name')) { continue; }
                try {
                    $payload = $this->mapStudent($row, $columns);
                    $student = $this->findStudent($payload);
                    if ($student) {
                        $student->update($payload);
                        $updated++;
                    } else {
                        $student = Student::create($payload);
                        $created++;
                    }
                    $this->syncGuardians($student, $row, $columns);
                    $this->syncClassroom($student, $this->value($row, $columns, 'classroom'));
                } catch (\Throwable $e) {
                    $skipped++; $errors[] = "Baris {$rowNumber}: {$e->getMessage()}";
                }
            }
        });

        activity('students')->event('student.imported')->withProperties(compact('created', 'updated', 'skipped'))->log('Import data siswa XLSX');

        return compact('created', 'updated', 'skipped', 'errors');
    }

    private function mapStudent(array $row, array $columns): array
    {
        return [
            'name' => $this->value($row, $columns, 'name'),
            'national_student_number' => $this->digits($this->value($row, $columns, 'nisn')),
            'national_identity_number' => $this->digits($this->value($row, $columns, 'nik')),
            'birth_place' => $this->value($row, $columns, 'birth_place'),
            'birth_date' => $this->date($this->value($row, $columns, 'birth_date')),
            'gender' => $this->gender($this->value($row, $columns, 'gender')),
            'student_status' => $this->status($this->value($row, $columns, 'status')),
            'address' => $this->value($row, $columns, 'address'),
            'phone' => $this->phone($this->value($row, $columns, 'phone')),
            'special_needs' => $this->noneNull($this->value($row, $columns, 'special_needs')),
            'disability' => $this->noneNull($this->value($row, $columns, 'disability')),
            'kip_pip_number' => $this->digits($this->value($row, $columns, 'kip_pip_number')),
            'admission_type' => AdmissionType::NewStudent->value,
            'is_active' => true,
        ];
    }

    private function findStudent(array $payload): ?Student
    {
        return Student::query()->where(function ($query) use ($payload): void {
            foreach (['national_student_number', 'national_identity_number'] as $field) {
                if (! empty($payload[$field])) { $query->orWhere($field, $payload[$field]); }
            }
        })->first();
    }

    private function syncGuardians(Student $student, array $row, array $columns): void
    {
        foreach ([['father_name', 'Ayah', true], ['mother_name', 'Ibu', false], ['guardian_name', 'Wali', false]] as [$field, $relationship, $primary]) {
            $name = $this->value($row, $columns, $field); if (! $name) { continue; }
            $guardian = Guardian::firstOrCreate(['name' => $name], ['is_active' => true]);
            $student->guardians()->syncWithoutDetaching([$guardian->id => ['relationship' => $relationship, 'is_primary' => $primary, 'is_emergency_contact' => $primary, 'lives_with_student' => false, 'financially_responsible' => $primary]]);
        }
    }

    private function syncClassroom(Student $student, ?string $classroomName): void
    {
        $classroomName = $this->noneNull($classroomName);
        if (! $classroomName || Str::contains(Str::lower($classroomName), 'tanpa kelas')) {
            return;
        }

        $academicYear = AcademicYear::query()->where('is_active', true)->first() ?? AcademicYear::query()->latest('starts_on')->first();
        if (! $academicYear) {
            return;
        }

        $classroom = $this->findClassroom($classroomName, $academicYear) ?? $this->createClassroom($classroomName, $academicYear);

        $student->enrollments()
            ->where('academic_year_id', $academicYear->id)
            ->where('classroom_id', '!=', $classroom->id)
            ->where('enrollment_status', EnrollmentStatus::Active->value)
            ->update(['enrollment_status' => EnrollmentStatus::Transferred->value, 'completed_at' => now()->toDateString()]);

        $student->enrollments()->updateOrCreate(
            ['academic_year_id' => $classroom->academic_year_id, 'classroom_id' => $classroom->id],
            ['enrolled_at' => now()->toDateString(), 'enrollment_status' => EnrollmentStatus::Active->value]
        );
    }

    private function findClassroom(string $classroomName, AcademicYear $academicYear): ?Classroom
    {
        $candidates = array_unique(array_filter([$classroomName, $this->rombelName($classroomName)]));

        $classrooms = Classroom::query()
            ->where('academic_year_id', $academicYear->id)
            ->with('gradeLevel')
            ->get();

        foreach ($classrooms as $classroom) {
            foreach ($candidates as $candidate) {
                if ($this->normalizeClassroomText($classroom->name) === $this->normalizeClassroomText($candidate)
                    || $this->normalizeClassroomText($classroom->code) === $this->normalizeClassroomText($candidate)) {
                    return $classroom;
                }
            }
        }

        return null;
    }

    private function createClassroom(string $classroomName, AcademicYear $academicYear): Classroom
    {
        $level = $this->gradeLevelNumber($classroomName);
        $gradeLevel = GradeLevel::firstOrCreate(
            ['level' => $level],
            ['name' => 'Kelas '.$level, 'code' => 'K'.$level, 'is_active' => true]
        );

        $rombelName = $this->rombelName($classroomName) ?? $classroomName;
        $code = Str::of($rombelName)->replaceMatches('/[^A-Za-z0-9]+/', '-')->trim('-')->upper()->limit(50, '')->toString();

        return Classroom::create([
            'academic_year_id' => $academicYear->id,
            'grade_level_id' => $gradeLevel->id,
            'name' => $classroomName,
            'code' => $code !== '' ? $code : 'K'.$level.'-IMPORT',
            'capacity' => null,
            'is_active' => true,
        ]);
    }

    private function gradeLevelNumber(string $classroomName): int
    {
        if (preg_match('/kelas\s*(\d+)/i', $classroomName, $matches)) {
            return max(1, min(6, (int) $matches[1]));
        }

        return 1;
    }

    private function rombelName(string $classroomName): ?string
    {
        if (! str_contains($classroomName, '-')) {
            return null;
        }

        return $this->clean(Str::after($classroomName, '-'));
    }

    private function normalizeClassroomText(?string $value): string
    {
        return Str::of((string) $value)->lower()->replaceMatches('/[^a-z0-9]+/', '')->toString();
    }

    private function detectColumns(array $sheet): array
    {
        foreach ($sheet as $rowNumber => $row) {
            $normalized = array_map(fn ($value) => $this->normalizeHeader((string) $value), $row);
            if (! in_array('nama lengkap', $normalized, true)) { continue; }
            $columns = [];
            foreach ($row as $index => $heading) {
                $heading = $this->normalizeHeader((string) $heading);
                foreach (self::COLUMN_ALIASES as $field => $aliases) { if (in_array($heading, $aliases, true)) { $columns[$field] = $index; } }
            }
            foreach (['name', 'nisn', 'nik', 'birth_place', 'birth_date', 'classroom', 'status', 'gender'] as $required) {
                if (! array_key_exists($required, $columns)) { throw new \RuntimeException('Kolom wajib pada XLSX tidak ditemukan: '.$required); }
            }
            return [$rowNumber, $columns];
        }
        throw new \RuntimeException('Header XLSX tidak ditemukan. Pastikan format sesuai daftar siswa pada tangkapan layar.');
    }

    private function readRows(string $path): array
    {
        $zip = new ZipArchive(); if ($zip->open($path) !== true) { throw new \RuntimeException('Berkas XLSX tidak dapat dibuka.'); }
        $shared = $this->sharedStrings($zip); $xml = simplexml_load_string($zip->getFromName('xl/worksheets/sheet1.xml') ?: ''); $rows = [];
        foreach ($xml->sheetData->row ?? [] as $row) { $rowNumber = (int) $row['r']; $rows[$rowNumber] = []; foreach ($row->c as $cell) { $rows[$rowNumber][$this->columnIndex((string) $cell['r'])] = $this->cellValue($cell, $shared); } }
        $zip->close(); return $rows;
    }
    private function sharedStrings(ZipArchive $zip): array { $xml = simplexml_load_string($zip->getFromName('xl/sharedStrings.xml') ?: '<sst/>'); $strings = []; foreach ($xml->si ?? [] as $item) { $strings[] = isset($item->t) ? (string) $item->t : trim(implode('', array_map('strval', iterator_to_array($item->xpath('.//t'))))); } return $strings; }
    private function cellValue(\SimpleXMLElement $cell, array $shared): ?string { $type = (string) $cell['t']; if ($type === 's') { return $shared[(int) $cell->v] ?? null; } if ($type === 'inlineStr') { return trim(implode('', array_map('strval', iterator_to_array($cell->xpath('.//t'))))); } return isset($cell->v) ? (string) $cell->v : null; }
    private function columnIndex(string $cell): int { preg_match('/[A-Z]+/', $cell, $matches); $index = 0; foreach (str_split($matches[0] ?? 'A') as $letter) { $index = ($index * 26) + ord($letter) - 64; } return $index - 1; }
    private function value(array $row, array $columns, string $field): ?string { return $this->clean($row[$columns[$field] ?? -1] ?? null); }
    private function clean(mixed $value): ?string { $clean = trim(preg_replace('/\s+/', ' ', (string) $value)); return $clean === '' ? null : $clean; }
    private function noneNull(?string $value): ?string { return in_array(Str::lower((string) $value), ['', '-', 'tidak ada'], true) ? null : $value; }
    private function digits(?string $value): ?string { $clean = preg_replace('/\D+/', '', (string) $value); return $clean === '' ? null : $clean; }
    private function phone(?string $value): ?string { $clean = preg_replace('/[^0-9+]/', '', (string) $value); return $clean === '' ? null : (str_starts_with($clean, '0') ? '+62'.substr($clean, 1) : $clean); }
    private function gender(?string $value): string { return Str::lower((string) $value) === 'perempuan' ? Gender::Female->value : Gender::Male->value; }
    private function status(?string $value): string { return Str::lower((string) $value) === 'aktif' ? StudentStatus::Active->value : StudentStatus::Inactive->value; }
    private function date(?string $value): ?string { if (! $value) { return null; } try { return is_numeric($value) ? CarbonImmutable::create(1899, 12, 30)->addDays((int) $value)->toDateString() : CarbonImmutable::parse($value)->toDateString(); } catch (\Throwable) { return null; } }
    private function normalizeHeader(string $value): string { return Str::of($value)->lower()->replaceMatches('/[^a-z0-9\/\.\-]+/', ' ')->replace(['-', '.'], ' ')->squish()->toString(); }
}
