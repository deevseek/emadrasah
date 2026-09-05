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
        $name = mb_strtolower(trim((string) $user->name));
        if ($email === '' && $name === '') {
            return null;
        }

        return DB::transaction(function () use ($user, $actor, $email, $name): ?Personnel {
            $candidates = $email === ''
                ? collect()
                : Personnel::query()
                    ->whereNull('user_id')
                    ->where('is_active', true)
                    ->whereRaw('LOWER(email) = ?', [$email])
                    ->lockForUpdate()
                    ->get();

            $personnel = $candidates->count() === 1 ? $candidates->first() : null;
            $matchedBy = 'alamat email';

            // Data personalia lama sering tidak mempunyai email atau memakai email
            // yang berbeda. Nama hanya boleh menjadi fallback jika tepat satu akun
            // dan satu personalia aktif mempunyai nama yang sama sehingga
            // hubungan tidak dibuat secara ambigu.
            if (! $personnel && $name !== '' && User::query()->whereRaw('LOWER(name) = ?', [$name])->count() === 1) {
                $nameCandidates = Personnel::query()
                    ->whereNull('user_id')
                    ->where('is_active', true)
                    ->whereRaw('LOWER(full_name) = ?', [$name])
                    ->lockForUpdate()
                    ->get();

                if ($nameCandidates->count() === 1) {
                    $personnel = $nameCandidates->first();
                    $matchedBy = 'nama lengkap yang sama';
                }
            }

            if (! $personnel) {
                return null;
            }

            $personnel->update([
                'user_id' => $user->id,
                'updated_by' => ($actor ?? $user)->id,
            ]);
            $user->setRelation('personnel', $personnel);
            activity('personnel')->causedBy($actor ?? $user)->performedOn($personnel)
                ->log("Menghubungkan akun dengan data personalia berdasarkan {$matchedBy}.");

            return $personnel;
        }, 3);
    }
}
