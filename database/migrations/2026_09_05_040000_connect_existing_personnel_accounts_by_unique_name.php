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
            ->whereNotNull('full_name')
            ->orderBy('id')
            ->eachById(function (object $personnel): void {
                $name = mb_strtolower(trim((string) $personnel->full_name));

                if ($name === '') {
                    return;
                }

                $personnelCount = DB::table('personnel')
                    ->where('is_active', true)
                    ->whereRaw('LOWER(full_name) = ?', [$name])
                    ->count();
                $users = DB::table('users')
                    ->whereNull('deleted_at')
                    ->whereRaw('LOWER(name) = ?', [$name])
                    ->whereNotExists(fn ($query) => $query
                        ->selectRaw('1')
                        ->from('personnel as linked_personnel')
                        ->whereColumn('linked_personnel.user_id', 'users.id'))
                    ->pluck('id');

                if ($personnelCount === 1 && $users->count() === 1) {
                    DB::table('personnel')
                        ->where('id', $personnel->id)
                        ->whereNull('user_id')
                        ->update(['user_id' => $users->first()]);
                }
            });
    }

    public function down(): void
    {
        // Hubungan valid tidak dilepas karena tidak dapat dibedakan dari hubungan manual.
    }
};
