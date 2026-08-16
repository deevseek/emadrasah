<?php

declare(strict_types=1);
namespace App\Services\Finance;
/** Dependency-free fallback PDF renderer. It renders the dedicated Blade first and creates an A4 PDF without a browser process. */
class SimplePdfRenderer
{
    public function render(string $view, array $data): string
    {
        $html = view($view, $data)->render();
        if (class_exists(\Dompdf\Dompdf::class)) {
            $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => false, 'isHtml5ParserEnabled' => true]);
            $dompdf->setPaper('a4', 'portrait');
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->render();
            return $dompdf->output();
        }
        $text = html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />', '</tr>', '</p>', '</div>', '</h1>', '</h2>'], "\n", $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $lines = array_slice(array_values(array_filter(array_map(fn ($v) => trim(preg_replace('/\s+/u', ' ', $v) ?? ''), preg_split('/\R/u', $text) ?: []))), 0, 62);
        $stream = "BT /F1 9 Tf 48 790 Td 0 -12 Td ";
        foreach ($lines as $line) {
            $safe = str_replace(['\\','(',')',"\r","\n"], ['\\\\','\\(','\\)','',''], iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $line) ?: $line);
            $stream .= '(' . $safe . ") Tj 0 -12 Td ";
        }
        $stream .= 'ET';
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595.28 841.89] /Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >>',
            '<< /Length '.strlen($stream)." >>\nstream\n$stream\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];
        $pdf = "%PDF-1.4\n"; $offsets = [0];
        foreach ($objects as $i => $object) { $offsets[] = strlen($pdf); $pdf .= ($i + 1)." 0 obj\n$object\nendobj\n"; }
        $xref = strlen($pdf); $pdf .= "xref\n0 6\n0000000000 65535 f \n";
        for ($i=1;$i<=5;$i++) $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        return $pdf."trailer << /Size 6 /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";
    }
}
