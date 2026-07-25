<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Academic\Imports\ImportMatcher;
use App\Services\Academic\Imports\SimpleXlsx;
use App\Services\Academic\Imports\TeachingAssignmentImportService;
use App\Services\Academic\TeachingAssignmentService;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;
use ZipArchive;

final class AcademicImportXlsxTest extends TestCase
{
    public function test_prefixed_namespaces_and_empty_middle_columns_are_read_without_shifting(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'academic-import-prefixed-');
        $this->createPrefixedXlsxFixture($path);

        try {
            $rows = (new SimpleXlsx)->read($path);

            $this->assertCount(3, $rows);
            $this->assertSame('Ahmad', $rows[0]['nama_guru']);
            $this->assertSame('', $rows[0]['nomor_pegawai']);
            $this->assertSame('I Ar-Rahman', $rows[0]['kelas']);
            $this->assertSame('4', $rows[0]['jp_per_minggu']);
            $this->assertSame('', $rows[0]['keterangan']);
            $this->assertSame('BTAQ', $rows[1]['mata_pelajaran']);
        } finally {
            @unlink($path);
        }
    }

    public function test_empty_workbook_is_rejected(): void
    {
        $service = $this->service();
        $path = $this->workbook(['tahun_ajaran'], []);

        try {
            $service->preview($path, 1, 1);
            $this->fail('Workbook kosong seharusnya ditolak.');
        } catch (ValidationException $exception) {
            $this->assertSame('File XLSX tidak menghasilkan baris data. Periksa format workbook dan header.', $exception->errors()['file'][0]);
        } finally {
            @unlink($path);
        }
    }

    public function test_incomplete_headers_are_rejected_with_missing_header_names(): void
    {
        $service = $this->service();
        $path = $this->workbook(['tahun_ajaran', 'nama_guru'], [['2026/2027', 'Ahmad']]);

        try {
            $service->preview($path, 1, 1);
            $this->fail('Header tidak lengkap seharusnya ditolak.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('semester', $exception->errors()['file'][0]);
            $this->assertStringContainsString('kelas', $exception->errors()['file'][0]);
        } finally {
            @unlink($path);
        }
    }

    public function test_empty_preview_cannot_create_a_completed_batch(): void
    {
        $this->expectException(ValidationException::class);
        $this->service()->process([], 1, 1, 'create', 'kosong.xlsx');
    }

    private function service(): TeachingAssignmentImportService
    {
        return new TeachingAssignmentImportService(new SimpleXlsx, new ImportMatcher, Mockery::mock(TeachingAssignmentService::class));
    }

    private function workbook(array $headers, array $rows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'academic-import-');
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $xml = '<?xml version="1.0"?><x:worksheet xmlns:x="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><x:sheetData>';
        foreach (array_merge([$headers], $rows) as $rowIndex => $row) {
            $xml .= '<x:row>';
            foreach ($row as $columnIndex => $value) {
                $xml .= '<x:c r="'.chr(65 + $columnIndex).($rowIndex + 1).'" t="inlineStr"><x:is><x:t>'.htmlspecialchars((string) $value, ENT_XML1).'</x:t></x:is></x:c>';
            }
            $xml .= '</x:row>';
        }
        $zip->addFromString('xl/worksheets/sheet1.xml', $xml.'</x:sheetData></x:worksheet>');
        $zip->close();

        return $path;
    }

    private function createPrefixedXlsxFixture(string $path): void
    {
        $headers = [
            'tahun_ajaran', 'semester', 'nama_guru', 'nomor_pegawai', 'kelas', 'kode_kelas',
            'mata_pelajaran', 'kode_mata_pelajaran', 'jp_per_minggu', 'tanggal_mulai',
            'tanggal_selesai', 'keterangan', 'aktif',
        ];
        $rows = [
            ['2026/2027', 'Ganjil', 'Ahmad', '', 'I Ar-Rahman', 'KLS-1', 'Fikih', 'MP-1', 4, '2026-07-01', '2026-12-20', '', 'Ya'],
            ['2026/2027', 'Ganjil', 'Fatimah', '', 'I As-Salam', 'KLS-2', 'BTAQ', 'MP-2', 3, '2026-07-01', '2026-12-20', '', 'Ya'],
            ['2026/2027', 'Ganjil', 'Hasan', '', "II Al-Mu'min", 'KLS-3', 'TIK', 'MP-3', 2, '2026-07-01', '2026-12-20', '', 'Ya'],
        ];

        $sharedStrings = array_merge($headers, ['Ahmad']);
        $sharedStringsXml = '<?xml version="1.0"?><x:sst xmlns:x="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        foreach ($sharedStrings as $value) {
            $sharedStringsXml .= '<x:si><x:t>'.htmlspecialchars($value, ENT_XML1).'</x:t></x:si>';
        }
        $sharedStringsXml .= '</x:sst>';

        $sheetXml = '<?xml version="1.0"?><x:worksheet xmlns:x="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><x:sheetData>';
        $sheetXml .= '<x:row r="1">';
        foreach ($headers as $columnIndex => $header) {
            $sheetXml .= '<x:c r="'.$this->columnName($columnIndex).'1" t="s"><x:v>'.$columnIndex.'</x:v></x:c>';
        }
        $sheetXml .= '</x:row>';

        foreach ($rows as $rowIndex => $row) {
            $excelRow = $rowIndex + 2;
            $sheetXml .= '<x:row r="'.$excelRow.'">';
            foreach ($row as $columnIndex => $value) {
                // Sengaja hilangkan nomor_pegawai (D) dan keterangan (L) dari XML.
                if ($value === '') {
                    continue;
                }

                $reference = $this->columnName($columnIndex).$excelRow;
                if (is_int($value)) {
                    $sheetXml .= '<x:c r="'.$reference.'"><x:v>'.$value.'</x:v></x:c>';
                } elseif ($rowIndex === 0 && $columnIndex === 2) {
                    $sheetXml .= '<x:c r="'.$reference.'" t="s"><x:v>'.count($headers).'</x:v></x:c>';
                } elseif ($rowIndex === 1 && $columnIndex === 6) {
                    $sheetXml .= '<x:c r="'.$reference.'" t="inlineStr"><x:is><x:r><x:t>BT</x:t></x:r><x:r><x:t>AQ</x:t></x:r></x:is></x:c>';
                } else {
                    $sheetXml .= '<x:c r="'.$reference.'" t="inlineStr"><x:is><x:t>'.htmlspecialchars((string) $value, ENT_XML1).'</x:t></x:is></x:c>';
                }
            }
            $sheetXml .= '</x:row>';
        }
        $sheetXml .= '</x:sheetData></x:worksheet>';

        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0"?><x:workbook xmlns:x="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><x:sheets><x:sheet name="Data Impor" sheetId="1" r:id="rId1"/></x:sheets></x:workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/></Relationships>');
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
        $zip->addFromString('xl/sharedStrings.xml', $sharedStringsXml);
        $zip->close();
    }

    private function columnName(int $index): string
    {
        $name = '';
        for ($number = $index + 1; $number > 0; $number = intdiv($number - 1, 26)) {
            $name = chr(65 + (($number - 1) % 26)).$name;
        }

        return $name;
    }
}
