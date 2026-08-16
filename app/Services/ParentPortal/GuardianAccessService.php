<?php

declare(strict_types=1);

namespace App\Services\ParentPortal;

use App\Models\GuardianProfile;
use App\Models\Student;
use App\Models\StudentGuardian;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class GuardianAccessService
{
    public function guardianOrNull(User $user): ?GuardianProfile
    {
        return GuardianProfile::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();
    }

    public function guardian(User $user): GuardianProfile
    {
        $guardian = $this->guardianOrNull($user);

        if ($guardian === null) {
            throw new AuthorizationException('Akun ini belum terhubung dengan profil orang tua/wali murid.');
        }

        return $guardian;
    }

    public function student(User $user, int $studentId, string $capability = 'can_view_academic'): Student
    {
        $guardian = $this->guardian($user);
        $link = StudentGuardian::query()
            ->where('guardian_id', $guardian->id)
            ->where('student_id', $studentId)
            ->where($capability, true)
            ->first();

        if ($link === null) {
            throw new AuthorizationException('Anda tidak berhak mengakses data siswa ini.');
        }

        return Student::query()->findOrFail($studentId);
    }
}
