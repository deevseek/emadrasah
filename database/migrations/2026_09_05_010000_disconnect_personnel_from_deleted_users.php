<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('personnel')
            ->whereIn('user_id', DB::table('users')->whereNotNull('deleted_at')->select('id'))
            ->update(['user_id' => null]);
    }

    public function down(): void
    {
        // Hubungan ke akun yang telah dihapus tidak boleh dipulihkan.
    }
};
