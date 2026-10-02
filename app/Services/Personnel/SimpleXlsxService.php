<?php

declare(strict_types=1);

namespace App\Services\Personnel;

use RuntimeException;
use ZipArchive;

class SimpleXlsxService
{
    public function read(string $path): array
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new RuntimeException('File tidak dapat dibaca atau rusak.');
        }

        $shared = [];
        $sharedStrings = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedStrings) {
            $xml = simplexml_load_string($sharedStrings);
            foreach ($xml->si as $item) {
                $shared[] = trim(implode('', array_map('strval', $item->xpath('.//*[local-name()="t"]'))));
            }
        }

        $workbook = simplexml_load_string((string) $zip->getFromName('xl/workbook.xml'));
        $relationships = simplexml_load_string((string) $zip->getFromName('xl/_rels/workbook.xml.rels'));
        $targets = [];
        foreach ($relationships->Relationship as $relationship) {
            $targets[(string) $relationship['Id']] = $this->workbookTarget((string) $relationship['Target']);
        }

        $workbook->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        $sheets = [];
        foreach ($workbook->sheets->sheet as $sheet) {
            $attributes = $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
            $target = $targets[(string) $attributes['id']] ?? '';
            $contents = $target === '' ? false : $zip->getFromName($target);
            if ($contents === false || ($xml = simplexml_load_string($contents)) === false) {
                continue;
            }

            $rows = [];
            foreach ($xml->sheetData->row as $row) {
                $values = [];
                foreach ($row->c as $cell) {
                    preg_match('/[A-Z]+/', (string) $cell['r'], $matches);
                    $column = $this->columnNumber($matches[0]);
                    $type = (string) $cell['t'];
                    $value = (string) $cell->v;
                    if ($type === 's') {
                        $value = $shared[(int) $value] ?? '';
                    } elseif ($type === 'inlineStr') {
                        $value = implode('', array_map('strval', $cell->xpath('.//*[local-name()="t"]')));
                    }
                    $values[$column] = $value;
                }
                $rows[(int) $row['r']] = $values;
            }
            $sheets[(string) $sheet['name']] = $rows;
        }

        $zip->close();

        return $sheets;
    }

    /**
     * @param  array{sheet_name?: string, column_widths?: array<int, int|float>}  $options
     */
    public function write(array $rows, string $path, array $options = []): void
    {
        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('File XLSX tidak dapat dibuat.');
        }

        $escape = fn ($value): string => htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $sheetName = mb_substr(preg_replace('/[\\\\\/?*\[\]:]/', ' ', $options['sheet_name'] ?? 'Data Personalia'), 0, 31);
        $lastColumn = $this->columnName(count($rows[0] ?? []));
        $lastRow = max(count($rows), 1);
        $columnWidths = $options['column_widths'] ?? [];

        $columns = '';
        foreach ($columnWidths as $index => $width) {
            $column = $index + 1;
            $columns .= '<col min="'.$column.'" max="'.$column.'" width="'.min(max((float) $width, 5), 80).'" customWidth="1"/>';
        }
        if ($columns !== '') {
            $columns = '<cols>'.$columns.'</cols>';
        }

        $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            .'<sheetFormatPr defaultRowHeight="18"/>'
            .$columns.'<sheetData>';

        foreach (array_values($rows) as $rowIndex => $row) {
            $number = $rowIndex + 1;
            $sheet .= '<row r="'.$number.'"'.($rowIndex === 0 ? ' ht="24" customHeight="1"' : '').'>';
            foreach (array_values($row) as $columnIndex => $value) {
                $reference = $this->columnName($columnIndex + 1).$number;
                $sheet .= '<c r="'.$reference.'" t="inlineStr" s="'.($rowIndex === 0 ? 1 : 2).'"><is><t xml:space="preserve">'.$escape($value).'</t></is></c>';
            }
            $sheet .= '</row>';
        }
        $sheet .= '</sheetData><autoFilter ref="A1:'.$lastColumn.$lastRow.'"/></worksheet>';

        $files = [
            '[Content_Types].xml' => '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>',
            '_rels/.rels' => '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>',
            'xl/workbook.xml' => '<?xml version="1.0"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="'.$escape($sheetName).'" sheetId="1" r:id="rId1"/></sheets></workbook>',
            'xl/_rels/workbook.xml.rels' => '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>',
            'xl/styles.xml' => $this->stylesXml(),
            'xl/worksheets/sheet1.xml' => $sheet,
        ];

        foreach ($files as $name => $body) {
            $zip->addFromString($name, $body);
        }
        $zip->close();
    }

    private function stylesXml(): string
    {
        return '<?xml version="1.0"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><color rgb="FFFFFFFF"/><sz val="11"/><name val="Calibri"/></font></fonts>'
            .'<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF14532D"/><bgColor indexed="64"/></patternFill></fill></fills>'
            .'<borders count="2"><border/><border><left style="thin"><color rgb="FFD1D5DB"/></left><right style="thin"><color rgb="FFD1D5DB"/></right><top style="thin"><color rgb="FFD1D5DB"/></top><bottom style="thin"><color rgb="FFD1D5DB"/></bottom></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="3"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/><xf numFmtId="49" fontId="1" fillId="2" borderId="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf><xf numFmtId="49" fontId="0" fillId="0" borderId="1" applyNumberFormat="1" applyBorder="1" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf></cellXfs>'
            .'</styleSheet>';
    }

    private function workbookTarget(string $target): string
    {
        $target = str_replace('\\', '/', $target);
        if (str_starts_with($target, '/')) {
            return ltrim($target, '/');
        }

        $parts = [];
        foreach (explode('/', 'xl/'.$target) as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }
            if ($part === '..') {
                array_pop($parts);
                continue;
            }
            $parts[] = $part;
        }

        return implode('/', $parts);
    }

    private function columnNumber(string $letters): int
    {
        $number = 0;
        foreach (str_split($letters) as $letter) {
            $number = $number * 26 + ord($letter) - 64;
        }

        return $number;
    }

    private function columnName(int $number): string
    {
        $name = '';
        while ($number) {
            $number--;
            $name = chr(65 + $number % 26).$name;
            $number = intdiv($number, 26);
        }

        return $name;
    }
}
