<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Academic\TeachingJournalTemplateService;
use PHPUnit\Framework\TestCase;

class TeachingJournalTemplateServiceTest extends TestCase
{
    private const NS = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    public function test_it_adds_placeholders_to_first_empty_row_of_recognized_journal_table(): void
    {
        $headers = ['No', 'Hari/Tanggal', 'Jam Ke', 'Mapel', 'Guru', 'Uraian Mengajar', 'Metode', 'Hadir', 'Tidak Hadir', 'Sakit', 'Izin', 'Alpa', 'Keterangan'];
        $headerRow = implode('', array_map(fn (string $text): string => "<w:tc><w:p><w:r><w:t>{$text}</w:t></w:r></w:p></w:tc>", $headers));
        $emptyRow = str_repeat('<w:tc><w:p/></w:tc>', 13);
        $xml = '<?xml version="1.0"?><w:document xmlns:w="'.self::NS.'"><w:body><w:tbl><w:tr>'.$headerRow.'</w:tr><w:tr>'.$emptyRow.'</w:tr></w:tbl></w:body></w:document>';

        $result = (new TeachingJournalTemplateService)->prepareDocument($xml);

        $this->assertNotNull($result);
        $this->assertStringContainsString('${no}', $result);
        $this->assertStringContainsString('${uraian_mengajar}', $result);
        $this->assertStringContainsString('${keterangan}', $result);
    }

    public function test_it_normalizes_placeholders_split_into_multiple_word_runs(): void
    {
        $xml = '<?xml version="1.0"?><w:document xmlns:w="'.self::NS.'"><w:body><w:tbl><w:tr><w:tc><w:p><w:r><w:t>${</w:t></w:r><w:r><w:t>no}</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>${uraian_</w:t></w:r><w:r><w:t>mengajar}</w:t></w:r></w:p></w:tc></w:tr></w:tbl></w:body></w:document>';

        $result = (new TeachingJournalTemplateService)->prepareDocument($xml);

        $this->assertNotNull($result);
        $this->assertStringContainsString('${no}', $result);
        $this->assertStringContainsString('${uraian_mengajar}', $result);
    }

    public function test_it_rejects_an_unrecognized_table_without_placeholders(): void
    {
        $xml = '<?xml version="1.0"?><w:document xmlns:w="'.self::NS.'"><w:body><w:tbl><w:tr><w:tc><w:p><w:r><w:t>Tabel lain</w:t></w:r></w:p></w:tc></w:tr></w:tbl></w:body></w:document>';

        $this->assertNull((new TeachingJournalTemplateService)->prepareDocument($xml));
    }
}
