<?php

declare(strict_types=1);

namespace App\Services\ParentPortal;

use App\Enums\GuardianRelationship;
use App\Events\GuardianRegistered;
use App\Models\GuardianProfile;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentGuardian;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RegisterGuardianService
{
    /** @param array<string, mixed> $data */
    public function register(array $data): GuardianProfile
    {
        $student = Student::query()
            ->where('nisn', $data['nisn'])
            ->whereDate('birth_date', $data['birth_date'])
            ->where('status', 'active')
            ->first();

        if (! $student) {
            throw ValidationException::withMessages([
                'nisn' => 'Data anak tidak ditemukan atau tidak aktif. Pastikan NISN dan tanggal lahir sesuai data madrasah.',
            ]);
        }

        $guardian = DB::transaction(function () use ($data, $student): GuardianProfile {
            $user = User::create([
                'name' => $data['name'],
                'username' => $this->uniqueUsername((string) $data['email']),
                'email' => $data['email'],
                'password' => $data['password'],
                'is_active' => true,
                'must_change_password' => false,
            ]);
            $user->assignRole(Role::findOrCreate('orang-tua', 'web'));

            $relationship = GuardianRelationship::from($data['relationship']);
            $profile = GuardianProfile::create([
                'user_id' => $user->id,
                'name' => $data['name'],
                'phone_number' => $data['phone_number'],
                'relationship' => $relationship->value,
                'is_active' => true,
            ]);
            StudentGuardian::create([
                'guardian_id' => $profile->id,
                'student_id' => $student->id,
                'relationship' => $relationship->value,
                'is_primary' => ! $student->guardians()->exists(),
                'can_view_academic' => true,
                'can_view_finance' => true,
                'can_request_leave' => true,
            ]);

            return $profile->setRelation('user', $user)->setRelation('students', collect([$student]));
        });

        GuardianRegistered::dispatch($guardian, $student);

        return $guardian;
    }

    private function uniqueUsername(string $email): string
    {
        $base = Str::of($email)->before('@')->lower()->replaceMatches('/[^a-z0-9._-]+/', '.')
            ->trim('.-_')->limit(42, '')->toString();
        $base = strlen($base) >= 4 ? $base : 'wali'.Str::lower(Str::random(6));
        $username = $base;
        $suffix = 2;
        while (User::query()->where('username', $username)->exists()) {
            $username = Str::limit($base, 46, '').$suffix++;
        }

        return $username;
    }
}
