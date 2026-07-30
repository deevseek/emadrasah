<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Classrooms\ClassroomLabelParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ClassroomLabelParserTest extends TestCase
{
    #[DataProvider('knownLabels')]
    public function test_it_parses_known_classroom_labels(string $label, int $grade, string $code, ?string $name): void
    {
        $result = (new ClassroomLabelParser)->parse($label);
        self::assertSame($grade, $result['grade_number']);
        self::assertSame($code, $result['code']);
        self::assertSame($name, $result['name']);
        self::assertSame('high', $result['confidence']);
    }

    public static function knownLabels(): array
    {
        return [
            ['Kelas 2 - II A (AL-MU\'MIN)', 2, 'II A', "AL-MU'MIN"],
            ['Kelas 3 - III B (AL-LATHIF)', 3, 'III B', 'AL-LATHIF'],
            ["Kelas 5 - V A (AL-'ALIM)", 5, 'V A', "AL-'ALIM"],
            ['Kelas 6 - VI (AL-MAJID)', 6, 'VI', 'AL-MAJID'],
            ['VI (AL-MAJID)', 6, 'VI', 'AL-MAJID'],
            ['2A', 2, 'II A', null],
        ];
    }

    public function test_unknown_label_requires_review(): void
    {
        $result = (new ClassroomLabelParser)->parse('Kelompok Matahari');
        self::assertSame('low', $result['confidence']);
        self::assertNotEmpty($result['warnings']);
    }
}
