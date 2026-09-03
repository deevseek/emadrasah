<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $now = now();
        DB::table('permissions')->insertOrIgnore([
            'name' => 'personnel-attendance.manage-devices',
            'guard_name' => 'web',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $permissionId = DB::table('permissions')->where('name', 'personnel-attendance.manage-devices')->where('guard_name', 'web')->value('id');
        $roleId = DB::table('roles')->where('name', 'hrd')->where('guard_name', 'web')->value('id');

        if ($permissionId && $roleId) {
            DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $permissionId, 'role_id' => $roleId]);
        }
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')->where('name', 'personnel-attendance.manage-devices')->where('guard_name', 'web')->value('id');
        $roleId = DB::table('roles')->where('name', 'hrd')->where('guard_name', 'web')->value('id');

        if ($permissionId && $roleId) {
            DB::table('role_has_permissions')->where(['permission_id' => $permissionId, 'role_id' => $roleId])->delete();
        }
    }
};
