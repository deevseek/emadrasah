<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration {
    private const ROLE = 'petugas-email';

    private const PERMISSIONS = ['dashboard.view', 'email-service.view', 'email-service.send'];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $now = now();
        DB::table('roles')->insertOrIgnore([
            'name' => self::ROLE,
            'guard_name' => 'web',
            'display_name' => 'Petugas Email',
            'description' => 'Membuat, mengirim, dan memantau email pelayanan madrasah.',
            'is_system' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $roleId = DB::table('roles')->where('name', self::ROLE)->where('guard_name', 'web')->value('id');
        $permissionIds = DB::table('permissions')->whereIn('name', self::PERMISSIONS)->where('guard_name', 'web')->pluck('id');

        foreach ($permissionIds as $permissionId) {
            DB::table('role_has_permissions')->insertOrIgnore([
                'permission_id' => $permissionId,
                'role_id' => $roleId,
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $roleId = DB::table('roles')->where('name', self::ROLE)->where('guard_name', 'web')->value('id');

        if ($roleId) {
            DB::table('model_has_roles')->where('role_id', $roleId)->delete();
            DB::table('role_has_permissions')->where('role_id', $roleId)->delete();
            DB::table('roles')->where('id', $roleId)->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
