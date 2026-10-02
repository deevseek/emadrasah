<?php

declare(strict_types=1);

namespace App\Services\Students;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class StudentCardExportService
{
    public const HEADERS = ['NAMA_LENGKAP', 'NISN', 'TANGGAL_LAHIR', 'ALAMAT', 'FOTO'];

    public function export(Builder $query, User $actor): string
    {
        $path = tempnam(sys_get_temp_dir(), 'student-cards-');
        $stream = $path === false ? false : fopen($path, 'wb');

        if ($stream === false) {
            throw new RuntimeException('File data kartu siswa tidak dapat dibuat.');
        }

        fwrite($stream, "\xEF\xBB\xBF");
        fputcsv($stream, self::HEADERS, ',', '"', '');

        $mayViewSensitiveData = $actor->can('students.view-sensitive');
        $query->chunk(500, function ($students) use ($stream, $mayViewSensitiveData): void {
            foreach ($students as $student) {
                fputcsv($stream, [
                    $student->full_name,
                    $student->nisn,
                    $student->birth_date?->format('d/m/Y'),
                    $mayViewSensitiveData ? $student->address : 'Data disembunyikan',
                    $this->photoFilename($student->nisn, $student->photo_path),
                ], ',', '"', '');
            }
        });

        fclose($stream);

        activity('students')
            ->causedBy($actor)
            ->log('Mengekspor data siswa untuk pencetakan kartu sekolah.');

        return $path;
    }

    private function photoFilename(?string $nisn, ?string $photoPath): string
    {
        if (! $nisn || ! $photoPath || ! Storage::disk('local')->exists($photoPath)) {
            return '';
        }

        return $nisn.'.'.strtolower(pathinfo($photoPath, PATHINFO_EXTENSION));
    }
}
