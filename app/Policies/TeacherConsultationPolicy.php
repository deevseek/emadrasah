<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\{TeacherConsultation, User};

class TeacherConsultationPolicy
{
    public function view(User $user, TeacherConsultation $consultation): bool
    {
        return $consultation->teacher_user_id === $user->id
            || $consultation->guardian()->where('user_id', $user->id)->exists();
    }

    public function reply(User $user, TeacherConsultation $consultation): bool
    {
        return $this->view($user, $consultation);
    }
}
