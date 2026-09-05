<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('personnel')
            ->whereNull('user_id')
            ->where('is_active', true)
            ->whereNotNull('email')
            ->orderBy('id')
            ->eachById(function (object $personnel): void {
                $email = strtolower(trim((string) $personnel->email));

                if ($email === '') {
                    return;
                }

                $userId = DB::table('users')
                    ->whereNull('deleted_at')
                    ->whereRaw('LOWER(email) = ?', [$email])
                    ->whereNotExists(fn ($query) => $query
                        ->selectRaw('1')
                        ->from('personnel as linked_personnel')
                        ->whereColumn('linked_personnel.user_id', 'users.id'))
                    ->value('id');

                if ($userId !== null) {
                    DB::table('personnel')
                        ->where('id', $personnel->id)
                        ->whereNull('user_id')
                        ->update(['user_id' => $userId]);
                }
            });
    }

    public function down(): void
    {
        // Hubungan yang valid tidak dilepas karena tidak dapat dibedakan dari hubungan manual.
    }
};
