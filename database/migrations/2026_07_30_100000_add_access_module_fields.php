<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('username', 50)->nullable()->after('name');
            $table->boolean('must_change_password')->default(true)->after('is_active');
        });

        $used = [];
        DB::table('users')->orderBy('id')->get()->each(function (object $user) use (&$used): void {
            $base = Str::of((string) ($user->email ?: $user->name))->before('@')->lower()
                ->replaceMatches('/[^a-z0-9._-]+/', '.')->trim('.-_')->limit(42, '')->toString();
            $base = strlen($base) >= 4 ? $base : 'user'.($user->id ?? '');
            $candidate = $base;
            $suffix = 2;
            while (isset($used[$candidate]) || DB::table('users')->where('username', $candidate)->exists()) {
                $candidate = $base.$suffix++;
            }
            $used[$candidate] = true;
            DB::table('users')->where('id', $user->id)->update(['username' => $candidate]);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->string('username', 50)->nullable(false)->change();
            $table->unique('username');
        });
        Schema::table('roles', function (Blueprint $table): void {
            $table->text('description')->nullable()->after('display_name');
            $table->boolean('is_system')->default(false)->index()->after('description');
        });
        Schema::table('login_histories', function (Blueprint $table): void {
            $table->string('login_identifier')->nullable()->index()->after('email');
        });
        DB::table('login_histories')->whereNull('login_identifier')->update(['login_identifier' => DB::raw('email')]);
    }

    public function down(): void
    {
        Schema::table('login_histories', fn (Blueprint $table) => $table->dropColumn('login_identifier'));
        Schema::table('roles', fn (Blueprint $table) => $table->dropColumn(['description', 'is_system']));
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['username']);
            $table->dropColumn(['username', 'must_change_password']);
        });
    }
};
