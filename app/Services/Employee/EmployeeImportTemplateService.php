<?php

declare(strict_types=1);

namespace App\Services\Employee;

use ZipArchive;

class EmployeeImportTemplateService
{
    private const HEADERS = [
        'NO',
        'NAMA LENGKAP',
        'L/P',
        'TEMPAT, TGL LAHIR',
        'STATUS',
        'NOMOR INDUK YAYASAN (NIY)',
        'NIP',
        'PANGKAT/GOLONGAN RUANG',
        'Peg.ID',
        'PENDIDIKAN TERAKHIR',
        'JABATAN',
        'SERTIFIKASI - IMPASSING',
        'MAPEL SERTIFIKASI',
        'JUMLAH JPL',
        'JENIS REKENING',
        'NO. REKENING',
        'NO. HP/ WA AKTIF',
        'E-MAIL AKTIF',
    ];

    private const EXAMPLE_ROWS = [
        ['1', 'USWATUN KHASANAH, S.Pd.I., M.Pd.', 'P', 'DEMAK, 26 AGUSTUS 1993', 'GTY', '620.0720.001', '-', '-', '20367380193001', 'S2', 'KEPALA MADRASAH', 'Non Sertifikasi', '', '24', 'BRI', '001601028750535', '089681929596', 'uswahasna82@gmail.com'],
        ['2', 'RO’IS RO’DATUL URBAH, S.Pd.', 'P', 'DEMAK, 14 NOVEMBER 1997', 'GTY', '620.0723.022', '-', '-', '20367380197004', 'D4 / S1', 'GURU KELAS 2', 'Non Sertifikasi', '', '32', 'BRI', '001601048852537', '088233182624', 'roisurbah12r@gmail.com'],
    ];

    public function content(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'employee-import-template-');
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', $this->contentTypes());
        $zip->addFromString('_rels/.rels', $this->rootRelationships());
        $zip->addFromString('xl/workbook.xml', $this->workbook());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelationships());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->worksheet());
        $zip->close();

        $content = file_get_contents($path);
        @unlink($path);

        return $content ?: '';
    }

    private function worksheet(): string
    {
        $rows = [self::HEADERS, ...self::EXAMPLE_ROWS];
        $xmlRows = '';

        foreach ($rows as $rowIndex => $row) {
            $excelRow = $rowIndex + 1;
            $xmlRows .= '<row r="'.$excelRow.'">';
            foreach ($row as $columnIndex => $value) {
                $cell = $this->columnName($columnIndex + 1).$excelRow;
                $xmlRows .= '<c r="'.$cell.'" t="inlineStr"><is><t>'.$this->escape($value).'</t></is></c>';
            }
            $xmlRows .= '</row>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheetViews><sheetView workbookViewId="0"/></sheetViews><sheetFormatPr defaultRowHeight="15"/>'
            .'<cols><col min="1" max="18" width="22" customWidth="1"/></cols>'
            .'<sheetData>'.$xmlRows.'</sheetData>'
            .'</worksheet>';
    }

    private function columnName(int $index): string
    {
        $name = '';
        while ($index > 0) {
            $index--;
            $name = chr(65 + ($index % 26)).$name;
            $index = intdiv($index, 26);
        }

        return $name;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }

    private function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>';
    }

    private function rootRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>';
    }

    private function workbook(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Data Personalia" sheetId="1" r:id="rId1"/></sheets></workbook>';
    }

    private function workbookRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>';
    }
}
