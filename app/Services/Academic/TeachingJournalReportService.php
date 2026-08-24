<?php

declare(strict_types=1);

namespace App\Services\Academic;

use App\Models\TeachingJournalTemplate;
use Illuminate\Support\Collection;
use RuntimeException;
use ZipArchive;

class TeachingJournalReportService
{
    public const ROW_FIELDS = ['no', 'hari_tanggal', 'jam_ke', 'mapel', 'guru', 'uraian_mengajar', 'metode', 'hadir', 'tidak_hadir', 'sakit', 'izin', 'alpa', 'keterangan'];

    public function createDocx(TeachingJournalTemplate $template, Collection $journals, string $sourcePath): string
    {
        $target = tempnam(sys_get_temp_dir(), 'jurnal-').'.docx';
        if (! copy($sourcePath, $target)) throw new RuntimeException('Template jurnal gagal disalin.');
        $zip = new ZipArchive;
        if ($zip->open($target) !== true) throw new RuntimeException('Template harus berupa DOCX yang valid.');
        $first = $journals->first();
        $scalar = [
            'tahun_ajaran' => $first?->academicYear?->name ?? '—', 'semester' => $first?->semester?->display_name ?? '—',
            'kelas' => $first?->classroom?->display_name ?? '—', 'nama_guru' => $first?->personnel?->full_name ?? '—',
            'bulan' => $first?->journal_date?->translatedFormat('F Y') ?? '—', 'jumlah_jurnal' => (string) $journals->count(),
        ];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (! str_ends_with((string) $name, '.xml') || (! str_starts_with((string) $name, 'word/'))) continue;
            $xml = $zip->getFromIndex($i);
            if (! is_string($xml)) continue;
            $xml = $this->cloneJournalRow($xml, $journals);
            foreach ($scalar as $key => $value) $xml = str_replace('${'.$key.'}', $this->xml((string) $value), $xml);
            $zip->addFromString((string) $name, $xml);
        }
        $zip->close();
        return $target;
    }

    private function cloneJournalRow(string $xml, Collection $journals): string
    {
        if (! preg_match('/<w:tr\b[^>]*>.*?\$\{(?:no|hari_tanggal|uraian_mengajar)\}.*?<\/w:tr>/s', $xml, $match)) return $xml;
        $rows = $journals->values()->map(function ($journal, int $index) use ($match): string {
            $counts = $journal->attendances->countBy(fn ($attendance) => $attendance->status->value);
            $values = [
                'no' => (string) ($index + 1), 'hari_tanggal' => $journal->journal_date->translatedFormat('l, d F Y'),
                'jam_ke' => $journal->lesson_number ?: '—', 'mapel' => $journal->subject->name, 'guru' => $journal->personnel->full_name,
                'uraian_mengajar' => $journal->topic, 'metode' => $journal->learning_method ?: '—',
                'hadir' => (string) ($counts['present'] ?? 0), 'tidak_hadir' => (string) ($journal->attendances->count() - ($counts['present'] ?? 0)),
                'sakit' => (string) ($counts['sick'] ?? 0), 'izin' => (string) ($counts['permitted'] ?? 0), 'alpa' => (string) ($counts['absent'] ?? 0), 'keterangan' => $journal->notes ?: '—',
            ];
            $row = $match[0];
            foreach ($values as $key => $value) $row = str_replace('${'.$key.'}', $this->xml($value), $row);
            return $row;
        })->implode('');
        return str_replace($match[0], $rows, $xml);
    }

    private function xml(string $value): string { return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8'); }
}
