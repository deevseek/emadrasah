<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Personnel\SimpleXlsxService;
use Tests\TestCase;
use ZipArchive;

class SimpleXlsxServiceTest extends TestCase
{
    public function test_it_reads_a_worksheet_with_an_absolute_relationship_target(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'personnel-xlsx');
        $service = new SimpleXlsxService;
        $service->write([['NO', 'NAMA LENGKAP'], ['1', 'Ahmad']], $path);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path) === true);
        $relationships = (string) $zip->getFromName('xl/_rels/workbook.xml.rels');
        $zip->addFromString(
            'xl/_rels/workbook.xml.rels',
            str_replace('Target="worksheets/sheet1.xml"', 'Target="/xl/worksheets/sheet1.xml"', $relationships),
        );
        $zip->close();

        try {
            $sheets = $service->read($path);

            $this->assertSame('NAMA LENGKAP', $sheets['Data Personalia'][1][2]);
            $this->assertSame('Ahmad', $sheets['Data Personalia'][2][2]);
        } finally {
            @unlink($path);
        }
    }
}
