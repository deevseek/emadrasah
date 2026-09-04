<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\User;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use Spatie\Permission\Models\Role;

class UserTest extends TestCase
{
    public function test_initials_are_generated_from_the_first_two_name_parts(): void
    {
        $user = new User(['name' => '  Ahmad   Fauzi Ramadhan  ']);

        $this->assertSame('AF', $user->initials);
    }

    public function test_display_role_supports_roles_returned_by_spatie(): void
    {
        $role = new Role([
            'name' => 'kepala-madrasah',
            'display_name' => 'Kepala Madrasah',
        ]);
        $user = new User(['name' => 'Ahmad Fauzi']);
        $user->setRelation('roles', new Collection([$role]));

        $this->assertSame('Kepala Madrasah', $user->display_role);
    }
}
