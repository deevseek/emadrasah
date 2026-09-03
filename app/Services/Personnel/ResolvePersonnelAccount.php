<?php

declare(strict_types=1);

namespace App\Services\Personnel;

use App\Models\{Personnel, User};
use Illuminate\Support\Facades\DB;

class ResolvePersonnelAccount
{
    public function handle(User $user): ?Personnel
    {
        $personnel = $user->personnel;

        if ($personnel) {
            return $personnel->is_active ? $personnel : null;
        }

        $email = strtolower(trim((string) $user->email));
        if ($email === '') {
            return null;
        }

        return DB::transaction(function () use ($user, $email): ?Personnel {
            $personnel = Personnel::query()
                ->whereNull('user_id')
                ->where('is_active', true)
                ->whereRaw('LOWER(email) = ?', [$email])
                ->lockForUpdate()
                ->first();

            if (! $personnel) {
                return null;
            }

            $personnel->update(['user_id' => $user->id, 'updated_by' => $user->id]);
            $user->setRelation('personnel', $personnel);
            activity('personnel')->causedBy($user)->performedOn($personnel)
                ->log('Menghubungkan akun dengan data personalia berdasarkan alamat email yang sama.');

            return $personnel;
        }, 3);
    }
}
