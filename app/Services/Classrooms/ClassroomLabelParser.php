<?php

declare(strict_types=1);

namespace App\Services\Classrooms;

final class ClassroomLabelParser
{
    private const ROMAN = ['I' => 1, 'II' => 2, 'III' => 3, 'IV' => 4, 'V' => 5, 'VI' => 6];

    public function parse(string $label): array
    {
        $original = trim($label);
        $value = preg_replace('/\s+/u', ' ', str_replace(['–', '—'], '-', $original)) ?? $original;
        $grade = null;
        $code = '';
        $name = null;

        if (preg_match('/\(([^()]*)\)\s*$/u', $value, $match)) {
            $name = trim($match[1]);
            $value = trim(substr($value, 0, -strlen($match[0])));
        }

        if (preg_match('/\bKelas\s*([1-6])\b/iu', $value, $match)) {
            $grade = (int) $match[1];
            $value = trim(preg_replace('/^.*?\bKelas\s*[1-6]\s*(?:-\s*)?/iu', '', $value, 1) ?? $value);
        }

        if (preg_match('/^(VI|IV|V|III|II|I)\b(?:\s+([A-Z]))?(?:\s+(.+))?$/iu', $value, $match)) {
            $roman = strtoupper($match[1]);
            $grade ??= self::ROMAN[$roman];
            $code = $roman.(trim($match[2] ?? '') !== '' ? ' '.strtoupper($match[2]) : '');
            if (! $name && trim($match[3] ?? '') !== '') {
                $name = trim($match[3]);
            }
        } elseif (preg_match('/^([1-6])\s*-?\s*([A-Z])?$/iu', $value, $match)) {
            $grade ??= (int) $match[1];
            $roman = array_search($grade, self::ROMAN, true);
            $code = $roman.(trim($match[2] ?? '') !== '' ? ' '.strtoupper($match[2]) : '');
        } elseif ($grade && preg_match('/^[A-Z]$/iu', $value)) {
            $roman = array_search($grade, self::ROMAN, true);
            $code = $roman.' '.strtoupper($value);
        } elseif ($grade && trim($value) !== '') {
            $code = trim($value, " -\t\n\r\0\x0B");
        }

        $confident = $grade !== null && $code !== '';

        return [
            'original_label' => $original,
            'grade_number' => $grade,
            'grade_name' => $grade ? "Kelas {$grade}" : null,
            'code' => $code,
            'name' => $name,
            'confidence' => $confident ? 'high' : 'low',
            'warnings' => $confident ? [] : ['Tingkat atau kode rombel belum dapat dikenali. Silakan periksa dan perbaiki.'],
        ];
    }
}
