<?php

declare(strict_types=1);

namespace App\Services\Academic;

use App\Models\{TeachingJournalTemplate, User};
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{DB, Storage};
use Illuminate\Validation\ValidationException;
use ZipArchive;

class TeachingJournalTemplateService
{
    private const WORD_NAMESPACE = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    private const ROW_FIELDS = ['no', 'hari_tanggal', 'jam_ke', 'mapel', 'guru', 'uraian_mengajar', 'metode', 'hadir', 'tidak_hadir', 'sakit', 'izin', 'alpa', 'keterangan'];

    public function upload(string $name, UploadedFile $file, User $user): TeachingJournalTemplate
    {
        $zip = new ZipArchive;
        if ($zip->open($file->getRealPath()) !== true || $zip->locateName('word/document.xml') === false) {
            throw ValidationException::withMessages(['template' => 'Berkas harus berupa dokumen DOCX yang valid.']);
        }
        $document = $this->prepareDocument((string) $zip->getFromName('word/document.xml'));
        if ($document === null) {
            $zip->close();
            throw ValidationException::withMessages(['template' => 'Tabel jurnal tidak dikenali. Gunakan tabel dengan kolom No, Hari/Tanggal, Uraian Mengajar, dan Keterangan, atau tambahkan placeholder ${no} dan ${uraian_mengajar} pada satu baris data.']);
        }
        $zip->addFromString('word/document.xml', $document);
        $zip->close();

        return DB::transaction(function () use ($name, $file, $user): TeachingJournalTemplate {
            TeachingJournalTemplate::query()->update(['is_active' => false]);
            $path = $file->store('teaching-journal-templates', 'local');
            $template = TeachingJournalTemplate::create(['name' => $name, 'original_name' => $file->getClientOriginalName(), 'path' => $path, 'is_active' => true, 'uploaded_by' => $user->id]);
            activity('akademik')->causedBy($user)->performedOn($template)->log('Mengunggah template jurnal mengajar');
            return $template;
        });
    }

    public function absolutePath(TeachingJournalTemplate $template): string
    {
        abort_unless(Storage::disk('local')->exists($template->path), 404, 'Berkas template tidak ditemukan.');
        return Storage::disk('local')->path($template->path);
    }

    /**
     * Merapikan placeholder yang dipecah Word dan mengisi baris data kosong pada
     * template jurnal lama agar dapat langsung digunakan sebagai template.
     */
    public function prepareDocument(string $xml): ?string
    {
        $document = new \DOMDocument;
        $document->preserveWhiteSpace = true;
        $document->formatOutput = false;

        if (! @$document->loadXML($xml)) {
            return null;
        }

        $xpath = new \DOMXPath($document);
        $xpath->registerNamespace('w', self::WORD_NAMESPACE);

        // Word kerap memecah ${placeholder} menjadi beberapa run (<w:r>).
        foreach ($xpath->query('//w:p[descendant::w:t]') ?: [] as $paragraph) {
            $texts = $xpath->query('.//w:t', $paragraph);
            if ($texts === false || $texts->length === 0) {
                continue;
            }
            $value = '';
            foreach ($texts as $text) {
                $value .= $text->textContent;
            }
            if (! str_contains($value, '${')) {
                continue;
            }
            $texts->item(0)->nodeValue = $value;
            for ($index = 1; $index < $texts->length; $index++) {
                $texts->item($index)->nodeValue = '';
            }
        }

        $normalized = $document->saveXML();
        if (is_string($normalized) && str_contains($normalized, '${no}') && str_contains($normalized, '${uraian_mengajar}')) {
            return $normalized;
        }

        foreach ($xpath->query('//w:tbl') ?: [] as $table) {
            $heading = mb_strtolower(preg_replace('/\s+/u', ' ', $table->textContent) ?? '');
            if (! str_contains($heading, 'uraian mengajar') || ! str_contains($heading, 'hari/tanggal') || ! str_contains($heading, 'keterangan')) {
                continue;
            }

            foreach ($xpath->query('./w:tr', $table) ?: [] as $row) {
                $cells = $xpath->query('./w:tc', $row);
                if ($cells === false || $cells->length !== count(self::ROW_FIELDS) || trim($row->textContent) !== '') {
                    continue;
                }
                foreach (self::ROW_FIELDS as $index => $field) {
                    $cell = $cells->item($index);
                    $paragraph = $xpath->query('./w:p', $cell)?->item(0);
                    if (! $paragraph) {
                        $paragraph = $document->createElementNS(self::WORD_NAMESPACE, 'w:p');
                        $cell->appendChild($paragraph);
                    }
                    $run = $document->createElementNS(self::WORD_NAMESPACE, 'w:r');
                    $text = $document->createElementNS(self::WORD_NAMESPACE, 'w:t');
                    $text->appendChild($document->createTextNode('${'.$field.'}'));
                    $run->appendChild($text);
                    $paragraph->appendChild($run);
                }

                return $document->saveXML() ?: null;
            }
        }

        return null;
    }
}
