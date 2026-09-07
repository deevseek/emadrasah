<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration {
    private const PERMISSIONS = ['email-service.view', 'email-service.send'];

    private const ROLES = ['super-admin', 'kepala-madrasah', 'operator'];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $now = now();
        foreach (self::PERMISSIONS as $permission) {
            DB::table('permissions')->insertOrIgnore([
                'name' => $permission,
                'guard_name' => 'web',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $permissionIds = DB::table('permissions')->whereIn('name', self::PERMISSIONS)->where('guard_name', 'web')->pluck('id');
        $roleIds = DB::table('roles')->whereIn('name', self::ROLES)->where('guard_name', 'web')->pluck('id');
        foreach ($roleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $permissionId, 'role_id' => $roleId]);
            }
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $permissionIds = DB::table('permissions')->whereIn('name', self::PERMISSIONS)->where('guard_name', 'web')->pluck('id');
        $roleIds = DB::table('roles')->whereIn('name', self::ROLES)->where('guard_name', 'web')->pluck('id');
        DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->whereIn('role_id', $roleIds)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
