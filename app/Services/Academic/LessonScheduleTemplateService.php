<?php

declare(strict_types=1);

namespace App\Services\Academic;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Employee;
use App\Models\HomeroomAssignment;
use App\Models\SchoolProfile;
use App\Models\SchoolSetting;
use App\Models\Semester;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

final class LessonScheduleTemplateService
{
    public const CLASS_CODES = ['I-AS-SALAM', 'I-AR-RAHMAN', 'I-AR-RAHIM', 'II-AL-MUMIN', 'II-AL-WAHHAB', 'III-AL-KHALIQ', 'III-AL-LATHIF', 'IV-AL-BASITH', 'IV-AL-KARIM', 'V-AL-ALIM', 'V-AL-HAKIM', 'VI-AL-MAJID'];

    public function storeTemplate(UploadedFile $file): string
    {
        $path = 'private/academic/templates/lesson-schedules/official-schedule.docx';
        Storage::disk('local')->putFileAs(dirname($path), $file, basename($path));
        $this->setting()->update(['value' => $path]);

        return $path;
    }

    public function templatePath(): ?string
    {
        $path = $this->setting()->value;

        return $path && Storage::disk('local')->exists($path) ? $path : null;
    }

    public function settingRecord(): SchoolSetting
    {
        return $this->setting();
    }

    public function render(Collection $schedules, AcademicYear $year, Semester $semester, Carbon $date, Collection $classrooms): string
    {
        $template = $this->templatePath();
        if (! $template) {
            throw new RuntimeException('Template Word Jadwal Pelajaran belum diunggah.');
        }
        if ($schedules->isEmpty()) {
            throw new RuntimeException('Tidak ada jadwal aktif untuk tahun ajaran dan semester yang dipilih.');
        }

        $output = tempnam(sys_get_temp_dir(), 'jadwal-pelajaran-').'.docx';
        copy(Storage::disk('local')->path($template), $output);
        $zip = new ZipArchive;
        if ($zip->open($output) !== true) {
            @unlink($output);
            throw new RuntimeException('Template bukan berkas DOCX yang valid.');
        }
        $xml = $zip->getFromName('word/document.xml');
        if ($xml === false) {
            $zip->close(); @unlink($output);
            throw new RuntimeException('Template tidak memiliki word/document.xml.');
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = true;
        if (! @$dom->loadXML($xml)) {
            $zip->close(); @unlink($output);
            throw new RuntimeException('XML dokumen pada template tidak valid.');
        }
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $tables = iterator_to_array($xpath->query('//w:tbl'));
        if (count($tables) < $classrooms->count()) {
            $zip->close(); @unlink($output);
            throw new RuntimeException('Tabel kelas pada template tidak dapat ditemukan.');
        }

        $principal = SchoolProfile::query()->first()?->principal_name
            ?: Employee::query()->where('is_active', true)->whereRaw('LOWER(position) LIKE ?', ['%kepala madrasah%'])->value('name') ?: '';
        $filledCells = 0;
        foreach ($classrooms->values() as $index => $classroom) {
            $items = $schedules->where('classroom_id', $classroom->id);
            $homeroom = HomeroomAssignment::with('employee')->where('classroom_id', $classroom->id)->where('academic_year_id', $year->id)->where('is_active', true)->latest('id')->first()?->employee
                ?: $classroom->homeroomTeacher;
            $values = [
                'nama_kelas' => $classroom->name, 'semester' => strtoupper($semester->name), 'tahun_ajaran' => $year->name,
                'tanggal_dokumen' => $date->translatedFormat('d F Y'), 'nama_wali_kelas' => $homeroom?->fullName() ?? $homeroom?->name ?? '',
                'nama_kepala_madrasah' => $principal, 'judul_jadwal' => "JADWAL PELAJARAN KELAS {$classroom->name} SEMESTER ".strtoupper($semester->name),
            ];
            $this->replacePlaceholders($xpath, $values, $this->canonical((string) $classroom->code));
            $filledCells += $this->fillTable($xpath, $tables[$index], $items);
        }
        if ($filledCells === 0) {
            $zip->close();
            @unlink($output);
            throw new RuntimeException('Jadwal tidak dapat dipetakan ke tabel Word. Pastikan kolom hari dan baris waktu pada template sesuai dengan jadwal.');
        }

        $zip->addFromString('word/document.xml', $dom->saveXML());
        $zip->close();

        return $output;
    }

    private function fillTable(\DOMXPath $xpath, \DOMNode $table, Collection $items): int
    {
        $cells = iterator_to_array($xpath->query('.//w:tc', $table));
        $dayColumns = $this->dayColumns($xpath, $table);
        $filled = 0;
        foreach ($items as $item) {
            $day = mb_strtolower($item->day_of_week->label());
            $start = substr((string) $item->starts_at, 0, 5);
            $end = substr((string) $item->ends_at, 0, 5);
            $tokens = ["\${jadwal_{$day}_{$start}_{$end}}", "{{jadwal_{$day}_{$start}_{$end}}}"];
            $target = collect($cells)->first(fn ($cell) => collect($tokens)->contains(fn ($token) => str_contains($this->nodeText($xpath, $cell), $token)));
            if (! $target) {
                $target = $this->gridCell($xpath, $table, $dayColumns[$this->canonicalLabel($day)] ?? null, $start, $end);
            }
            if ($target) {
                $filled += $this->setCellText($xpath, $target, $this->scheduleText($item)) ? 1 : 0;
            }
        }

        return $filled;
    }

    /** @return array<string, int> */
    private function dayColumns(\DOMXPath $xpath, \DOMNode $table): array
    {
        $columns = [];
        $days = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu'];

        foreach ($xpath->query('.//w:tr', $table) as $row) {
            foreach ($this->rowCells($xpath, $row) as $cell) {
                $label = $this->canonicalLabel($this->nodeText($xpath, $cell['node']));
                if (in_array($label, $days, true)) {
                    $columns[$label] = $cell['start'];
                }
            }
        }

        return $columns;
    }

    private function gridCell(\DOMXPath $xpath, \DOMNode $table, ?int $column, string $start, string $end): ?\DOMNode
    {
        if ($column === null) {
            return null;
        }

        foreach ($xpath->query('.//w:tr', $table) as $row) {
            $cells = $this->rowCells($xpath, $row);
            $rowText = str_replace(['.', '–', '—'], [':', '-', '-'], $this->nodeText($xpath, $row));
            if (! str_contains($rowText, $start) || ! str_contains($rowText, $end)) {
                continue;
            }

            foreach ($cells as $cell) {
                if ($cell['start'] <= $column && $cell['end'] >= $column) {
                    return $cell['node'];
                }
            }
        }

        return null;
    }

    /** @return list<array{start: int, end: int, node: \DOMNode}> */
    private function rowCells(\DOMXPath $xpath, \DOMNode $row): array
    {
        $result = [];
        $column = 0;
        foreach ($xpath->query('./w:tc', $row) as $cell) {
            $spanNode = $xpath->query('./w:tcPr/w:gridSpan', $cell)->item(0);
            $span = max(1, (int) ($spanNode?->attributes?->getNamedItemNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'val')?->nodeValue ?? 1));
            $result[] = ['start' => $column, 'end' => $column + $span - 1, 'node' => $cell];
            $column += $span;
        }

        return $result;
    }

    private function canonicalLabel(string $value): string
    {
        return preg_replace('/[^a-z]/', '', mb_strtolower($value)) ?? '';
    }

    private function scheduleText($item): string
    {
        if ($item->entry_type->value !== 'lesson') return (string) ($item->activity_name ?? '');

        return (string) ($item->subject?->short_name ?: $item->subject?->name ?: $item->teachingAssignment?->subject?->name ?: '');
    }

    private function replacePlaceholders(\DOMXPath $xpath, array $values, string $code): void
    {
        foreach ($values as $key => $value) {
            $generic = ["\${$key}", "{{$key}}"];
            $specific = ["\${$key}_{$code}", "{{$key}_{$code}}"];
            $matches = [];
            foreach ($xpath->query('//w:p') as $paragraph) {
                $text = $this->nodeText($xpath, $paragraph);
                if (str_contains($text, $specific[0]) || str_contains($text, $specific[1])) {
                    $this->setParagraphText($xpath, $paragraph, str_replace($specific, (string) $value, $text));
                } elseif (str_contains($text, $generic[0]) || str_contains($text, $generic[1])) {
                    $matches[] = $paragraph;
                }
            }
            if (isset($matches[0])) {
                $paragraph = $matches[0];
                $this->setParagraphText($xpath, $paragraph, str_replace($generic, (string) $value, $this->nodeText($xpath, $paragraph)));
            }
        }
    }

    private function setCellText(\DOMXPath $xpath, \DOMNode $cell, string $value): bool
    {
        $paragraph = $xpath->query('./w:p', $cell)->item(0);
        if (! $paragraph) {
            $paragraph = $cell->ownerDocument?->createElementNS(
                'http://schemas.openxmlformats.org/wordprocessingml/2006/main',
                'w:p'
            );
            if (! $paragraph) {
                return false;
            }
            $cell->appendChild($paragraph);
        }

        return $this->setParagraphText($xpath, $paragraph, $value);
    }

    private function setParagraphText(\DOMXPath $xpath, \DOMNode $paragraph, string $value): bool
    {
        $texts = iterator_to_array($xpath->query('.//w:t', $paragraph));
        if (! $texts) {
            $document = $paragraph->ownerDocument;
            if (! $document) {
                return false;
            }
            $run = $document->createElementNS(
                'http://schemas.openxmlformats.org/wordprocessingml/2006/main',
                'w:r'
            );
            $text = $document->createElementNS(
                'http://schemas.openxmlformats.org/wordprocessingml/2006/main',
                'w:t'
            );
            $run->appendChild($text);
            $paragraph->appendChild($run);
            $texts = [$text];
        }
        $texts[0]->nodeValue = $value;
        if ($value !== trim($value)) {
            $texts[0]->setAttributeNS('http://www.w3.org/XML/1998/namespace', 'xml:space', 'preserve');
        }
        foreach (array_slice($texts, 1) as $text) $text->nodeValue = '';

        return true;
    }

    private function nodeText(\DOMXPath $xpath, \DOMNode $node): string
    {
        return collect(iterator_to_array($xpath->query('.//w:t', $node)))->map(fn ($text) => $text->textContent)->join('');
    }

    private function canonical(string $value): string
    {
        return trim(preg_replace('/-+/', '-', str_replace(["'", '’', ' '], ['', '', '-'], strtoupper($value))), '-');
    }

    private function setting(): SchoolSetting
    {
        return SchoolSetting::firstOrCreate(['group' => 'lesson_schedule_templates', 'key' => 'official_docx_path'], ['value' => null, 'type' => 'file', 'is_public' => false]);
    }
}
