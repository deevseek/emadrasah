<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $roleId = DB::table('roles')->where('name', 'kepala-madrasah')->where('guard_name', 'web')->value('id');
        $permissionIds = DB::table('permissions')->where('guard_name', 'web')->whereIn('name', ['users.update', 'users.assign-role'])->pluck('id');

        if (! $roleId) {
            return;
        }

        foreach ($permissionIds as $permissionId) {
            DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $permissionId, 'role_id' => $roleId]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $roleId = DB::table('roles')->where('name', 'kepala-madrasah')->where('guard_name', 'web')->value('id');
        $permissionIds = DB::table('permissions')->where('guard_name', 'web')->whereIn('name', ['users.update', 'users.assign-role'])->pluck('id');

        if (! $roleId) {
            return;
        }

        DB::table('role_has_permissions')->where('role_id', $roleId)->whereIn('permission_id', $permissionIds)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
