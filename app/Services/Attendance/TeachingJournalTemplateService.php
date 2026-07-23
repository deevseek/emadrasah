<?php

declare(strict_types=1);

namespace App\Services\Attendance;

use App\Models\SchoolProfile;
use App\Models\SchoolSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

final class TeachingJournalTemplateService
{
    public function storeTemplate(string $type, UploadedFile $file): string
    {
        $path = $file->storeAs('teaching-journal-templates', $type.'-'.Str::uuid().'.docx', 'local');
        $this->setting($type)->update(['value' => $path]);

        return $path;
    }

    public function templatePath(string $type): ?string
    {
        $value = $this->setting($type)->value;

        return $value && Storage::disk('local')->exists($value) ? $value : null;
    }

    public function render(string $type, Collection $journals, SchoolProfile $profile, Carbon $month): string
    {
        $templatePath = $this->templatePath($type);
        if (! $templatePath) {
            throw new RuntimeException('Template Word jurnal belum diunggah.');
        }

        $outputPath = tempnam(sys_get_temp_dir(), 'jurnal-akademik-').'.docx';
        copy(Storage::disk('local')->path($templatePath), $outputPath);

        $zip = new ZipArchive();
        if ($zip->open($outputPath) !== true) {
            throw new RuntimeException('Template Word tidak dapat dibuka.');
        }

        $documentXml = $zip->getFromName('word/document.xml');
        if ($documentXml === false) {
            $zip->close();
            throw new RuntimeException('Template Word tidak valid.');
        }

        $documentXml = strtr($documentXml, $this->placeholders($type, $journals, $profile, $month));
        $zip->addFromString('word/document.xml', $documentXml);
        $zip->close();

        return $outputPath;
    }

    private function setting(string $type): SchoolSetting
    {
        return SchoolSetting::firstOrCreate(
            ['group' => 'teaching_journal_templates', 'key' => $type.'_docx_path'],
            ['value' => null, 'type' => 'file', 'is_public' => false],
        );
    }

    private function placeholders(string $type, Collection $journals, SchoolProfile $profile, Carbon $month): array
    {
        $first = $journals->first();
        $rows = $journals->values()->map(function ($journal, int $index): string {
            return implode("\n", [
                'No: '.($index + 1),
                'Tanggal: '.$journal->journal_date->translatedFormat('l, d/m/Y'),
                'Jam/Pertemuan: '.($journal->meeting_number ?: '-'),
                'Mapel: '.($journal->subject?->name ?? '-'),
                'Guru: '.($journal->employee?->fullName() ?? $journal->employee?->name ?? '-'),
                'Uraian: '.($journal->learning_topic ?: $journal->learning_material ?: '-'),
                'Metode: '.($journal->learning_method ?: '-'),
                'Keterangan: '.($journal->teacher_notes ?: '-'),
            ]);
        })->join("\n\n");

        $classroom = $first?->classroom;
        $employee = $first?->employee;
        $homeroom = $classroom?->homeroomTeacher;

        return collect([
            '${jenis_jurnal}' => $type === 'class' ? 'Jurnal Kelas' : 'Jurnal Guru',
            '${nama_madrasah}' => $profile->school_name,
            '${nama_yayasan}' => $profile->foundation_name ?: 'Yayasan',
            '${nsm}' => $profile->nsm ?: '-',
            '${npsn}' => $profile->npsn ?: '-',
            '${alamat_madrasah}' => collect([$profile->address, $profile->village, $profile->district, $profile->city])->filter()->join(', ') ?: '-',
            '${email_madrasah}' => $profile->email ?: '-',
            '${tahun_ajaran}' => $first?->academicYear?->name ?? '-',
            '${semester}' => strtoupper($first?->semester?->name ?? '-'),
            '${bulan}' => $month->translatedFormat('F Y'),
            '${kelas}' => $classroom?->name ?? '-',
            '${nama_guru_mapel}' => $employee?->fullName() ?? $employee?->name ?? '-',
            '${nama_wali_kelas}' => $homeroom?->fullName() ?? $homeroom?->name ?? '-',
            '${nama_penanggung_jawab}' => $type === 'class' ? ($homeroom?->fullName() ?? '-') : ($employee?->fullName() ?? '-'),
            '${nama_kepala_madrasah}' => $profile->principal_name ?: '-',
            '${tanggal_cetak}' => now()->translatedFormat('d F Y'),
            '${baris_jurnal}' => $rows !== '' ? $rows : 'Belum ada jurnal mengajar pada bulan ini.',
        ])->map(fn ($value): string => $this->escape((string) $value))->all();
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
}
