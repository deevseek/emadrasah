<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Academic\Imports\ImportMatcher;
use PHPUnit\Framework\TestCase;

final class AcademicImportMatcherTest extends TestCase
{
    public function test_all_twelve_official_classroom_names_are_declared(): void
    {
        $this->assertSame(12, count(ImportMatcher::OFFICIAL_CLASSROOMS));
        $this->assertContains('III Al-Lathif', ImportMatcher::OFFICIAL_CLASSROOMS);
        $this->assertContains('V Al-Hakim', ImportMatcher::OFFICIAL_CLASSROOMS);
        $this->assertNotContains('II Al-Lathif', ImportMatcher::OFFICIAL_CLASSROOMS);
        $this->assertNotContains('III Al-Majid', ImportMatcher::OFFICIAL_CLASSROOMS);
    }

    /** @dataProvider aliases */
    public function test_safe_alias_normalization(string $left, string $right): void
    {
        $matcher = new ImportMatcher;
        $this->assertSame($matcher->normalize($left), $matcher->normalize($right));
    }

    public static function aliases(): array
    {
        return [
            ["Al-Mu'min", 'Al Mumin'],
            ['Al-Mu’min', 'Al Mumin'],
            ["Al-'Alim", 'Al Alim'],
            ['As-Salam', 'As Salam'],
            ['Fullday', 'Full Day'],
            ['Kelas I Ar-Rahman', 'I Ar-Rahman'],
            ['I As-Salam (Fullday)', 'I As-Salam'],
            ['Al Qur`an Hadis', 'Al-Qur’an Hadits'],
        ];
    }
}
