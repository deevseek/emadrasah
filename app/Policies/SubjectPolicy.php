<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\{Subject, User};

class SubjectPolicy
{
    public function viewAny(User $user): bool { return $user->can('subjects.view'); }
    public function create(User $user): bool { return $user->can('subjects.create'); }
    public function update(User $user, Subject $subject): bool { return $user->can('subjects.update'); }
}
