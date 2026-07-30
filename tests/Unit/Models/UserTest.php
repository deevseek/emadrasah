<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function test_initials_are_generated_from_the_first_two_name_parts(): void
    {
        $user = new User(['name' => '  Ahmad   Fauzi Ramadhan  ']);

        $this->assertSame('AF', $user->initials);
    }
}
