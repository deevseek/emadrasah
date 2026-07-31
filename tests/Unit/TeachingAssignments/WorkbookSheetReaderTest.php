<?php

declare(strict_types=1);

namespace Tests\Unit\TeachingAssignments;

use App\Services\Personnel\SimpleXlsxService;
use PHPUnit\Framework\TestCase;
use ZipArchive;

class WorkbookSheetReaderTest extends TestCase
{
    public function test_all_expected_sheets_and_formula_calculated_value_are_read(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'teaching').'.xlsx'; $zip = new ZipArchive; $zip->open($path, ZipArchive::CREATE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"/>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="DATABASE" sheetId="1" r:id="rId1"/><sheet name="LEGGER" sheetId="2" r:id="rId2"/><sheet name="PEMBAGIAN TUGAS MAPEL" sheetId="3" r:id="rId3"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Target="worksheets/sheet2.xml"/><Relationship Id="rId3" Target="worksheets/sheet3.xml"/></Relationships>');
        foreach ([1 => 62, 2 => 42, 3 => 50] as $sheet => $calculated) $zip->addFromString("xl/worksheets/sheet{$sheet}.xml", '<?xml version="1.0"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData><row r="1"><c r="A1"><f>SUM(A2:A3)</f><v>'.$calculated.'</v></c></row></sheetData></worksheet>');
        $zip->close(); $sheets = (new SimpleXlsxService)->read($path); @unlink($path);
        $this->assertSame(['DATABASE', 'LEGGER', 'PEMBAGIAN TUGAS MAPEL'], array_keys($sheets)); $this->assertSame('62', $sheets['DATABASE'][1][1]);
    }
}
