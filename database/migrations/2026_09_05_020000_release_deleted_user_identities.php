<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->whereNotNull('deleted_at')
            ->orderBy('id')
            ->each(function (object $user): void {
                $deletedIdentity = 'deleted-user-'.$user->id.'-'.Str::lower(Str::random(16));

                DB::table('password_reset_tokens')->where('email', $user->email)->delete();
                DB::table('users')->where('id', $user->id)->update([
                    'username' => $deletedIdentity,
                    'email' => $deletedIdentity.'@deleted.invalid',
                    'remember_token' => null,
                    'is_active' => false,
                ]);
            });
    }

    public function down(): void
    {
        // Identitas login asli tidak disimpan pada tabel pengguna demi keamanan,
        // sehingga perubahan ini tidak dapat dibalik secara aman.
    }
};
