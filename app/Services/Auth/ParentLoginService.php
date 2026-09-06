<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ParentLoginService
{
    public function findUser(string $nisn, string $password): ?User
    {
        $student = Student::query()
            ->where('nisn', $nisn)
            ->with(['guardians.user.roles'])
            ->first();

        if (! $student) {
            return null;
        }

        foreach ($student->guardians as $guardian) {
            $user = $guardian->user;

            if ($guardian->is_active && $user?->is_active && $user->hasRole('orang-tua') && Hash::check($password, $user->password)) {
                return $user;
            }
        }

        return null;
    }
}
