<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Student;
use PHPUnit\Framework\TestCase;

class StudentTest extends TestCase
{
    public function test_initials_are_generated_from_the_first_two_name_parts(): void
    {
        $student = new Student(['full_name' => '  ahmad   fauzi ramadhan  ']);

        $this->assertSame('AF', $student->initials);
    }

    public function test_initials_ignore_non_letter_characters(): void
    {
        $student = new Student(['full_name' => "'aisyah nur-aini"]);

        $this->assertSame('AN', $student->initials);
    }
}
