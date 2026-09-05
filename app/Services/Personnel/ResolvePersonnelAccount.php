<?php

declare(strict_types=1);

namespace App\Services\Personnel;

use App\Models\{Personnel, User};
use Illuminate\Support\Facades\DB;

class ResolvePersonnelAccount
{
    public function handle(User $user, ?User $actor = null): ?Personnel
    {
        // Jangan mengandalkan cache relasi pada instance pengguna. Instance ini dapat
        // sudah memuat relasi null sebelum Operator menghubungkan akun, terutama pada
        // proses login/session yang berumur panjang. user_id pada tabel personalia
        // merupakan sumber kebenaran untuk hubungan akun.
        $personnel = Personnel::query()->where('user_id', $user->id)->first();

        if ($personnel) {
            $user->setRelation('personnel', $personnel);

            return $personnel->is_active ? $personnel : null;
        }

        $email = strtolower(trim((string) $user->email));
        if ($email === '') {
            return null;
        }

        return DB::transaction(function () use ($user, $actor, $email): ?Personnel {
            $personnel = Personnel::query()
                ->whereNull('user_id')
                ->where('is_active', true)
                ->whereRaw('LOWER(email) = ?', [$email])
                ->lockForUpdate()
                ->first();

            if (! $personnel) {
                return null;
            }

            $personnel->update([
                'user_id' => $user->id,
                'updated_by' => ($actor ?? $user)->id,
            ]);
            $user->setRelation('personnel', $personnel);
            activity('personnel')->causedBy($actor ?? $user)->performedOn($personnel)
                ->log('Menghubungkan akun dengan data personalia berdasarkan alamat email yang sama.');

            return $personnel;
        }, 3);
    }
}
