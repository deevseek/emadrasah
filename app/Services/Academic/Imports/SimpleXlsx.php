<?php

declare(strict_types=1);

namespace App\Services\Academic\Imports;

use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

final class SimpleXlsx
{
    private const SPREADSHEET_NAMESPACE = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

    /** @return list<array<string, string>> */
    public function read(string $path): array
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new RuntimeException('Berkas XLSX rusak atau tidak dapat dibaca.');
        }

        $shared = [];
        if (($sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
            $doc = $this->loadXml($sharedStringsXml);
            $doc->registerXPathNamespace('x', self::SPREADSHEET_NAMESPACE);
            foreach ($doc->xpath('//x:si') ?: [] as $item) {
                $item->registerXPathNamespace('x', self::SPREADSHEET_NAMESPACE);
                $texts = array_map(static fn (SimpleXMLElement $node): string => (string) $node, $item->xpath('.//x:t') ?: []);
                $shared[] = trim(implode('', $texts));
            }
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        if ($sheetXml === false) {
            throw new RuntimeException('Workbook tidak memiliki lembar pertama.');
        }

        $xml = $this->loadXml($sheetXml);
        $xml->registerXPathNamespace('x', self::SPREADSHEET_NAMESPACE);
        $matrix = [];
        foreach ($xml->xpath('//x:sheetData/x:row') ?: [] as $row) {
            $row->registerXPathNamespace('x', self::SPREADSHEET_NAMESPACE);
            $values = [];
            foreach ($row->xpath('./x:c') ?: [] as $cell) {
                $cell->registerXPathNamespace('x', self::SPREADSHEET_NAMESPACE);
                preg_match('/^([A-Z]+)/i', (string) $cell['r'], $matches);
                $column = $this->columnIndex(mb_strtoupper($matches[1] ?? 'A'));
                $type = (string) $cell['t'];
                $valueNodes = $cell->xpath('./x:v') ?: [];
                $value = (string) ($valueNodes[0] ?? '');

                if ($type === 's') {
                    $value = $shared[(int) $value] ?? '';
                } elseif ($type === 'inlineStr') {
                    $texts = array_map(static fn (SimpleXMLElement $node): string => (string) $node, $cell->xpath('./x:is//x:t') ?: []);
                    $value = implode('', $texts);
                }

                $values[$column] = trim($value);
            }
            if ($values !== []) {
                ksort($values);
                $matrix[] = $values;
            }
        }

        if ($matrix === []) {
            return [];
        }

        $headerCells = array_shift($matrix);
        $headers = [];
        foreach ($headerCells as $columnIndex => $value) {
            $header = mb_strtolower(trim((string) $value));
            if ($header !== '') {
                $headers[$columnIndex] = $header;
            }
        }

        $rows = [];
        foreach ($matrix as $values) {
            $data = [];
            foreach ($headers as $columnIndex => $header) {
                $data[$header] = (string) ($values[$columnIndex] ?? '');
            }
            if (implode('', $data) !== '') {
                $rows[] = $data;
            }
        }

        return $rows;
    }

    private function loadXml(string $xml): SimpleXMLElement
    {
        $document = simplexml_load_string($xml);
        if ($document === false) {
            throw new RuntimeException('XML di dalam workbook tidak valid.');
        }

        return $document;
    }

    public function download(array $headers, array $rows, string $filename): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows): void {
            $tmp = tempnam(sys_get_temp_dir(), 'xlsx'); $zip = new ZipArchive; $zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE);
            $zip->addFromString('[Content_Types].xml','<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
            $zip->addFromString('_rels/.rels','<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
            $zip->addFromString('xl/workbook.xml','<?xml version="1.0"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Data Impor" sheetId="1" r:id="rId1"/></sheets></workbook>');
            $zip->addFromString('xl/_rels/workbook.xml.rels','<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
            $all=array_merge([$headers],$rows); $xml='<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';
            foreach($all as $ri=>$row){$xml.='<row r="'.($ri+1).'">';foreach(array_values($row) as $ci=>$v){$ref=$this->columnName($ci).($ri+1);$xml.='<c r="'.$ref.'" t="inlineStr"><is><t>'.htmlspecialchars((string)$v,ENT_XML1).'</t></is></c>';}$xml.='</row>';}$xml.='</sheetData></worksheet>';
            $zip->addFromString('xl/worksheets/sheet1.xml',$xml);$zip->close();readfile($tmp);unlink($tmp);
        }, $filename, ['Content-Type'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }
    private function columnIndex(string $letters): int { $n=0;foreach(str_split($letters) as $c)$n=$n*26+ord($c)-64;return $n-1; }
    private function columnName(int $index): string { $s='';for($n=$index+1;$n>0;$n=intdiv($n-1,26))$s=chr(65+(($n-1)%26)).$s;return $s; }
}
