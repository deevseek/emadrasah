<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $now = now();
        DB::table('permissions')->insertOrIgnore([
            'name' => 'personnel-attendance.register-device',
            'guard_name' => 'web',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $registerId = DB::table('permissions')->where('name', 'personnel-attendance.register-device')->where('guard_name', 'web')->value('id');
        $manageId = DB::table('permissions')->where('name', 'personnel-attendance.manage-devices')->where('guard_name', 'web')->value('id');
        $roleIds = DB::table('roles')->where('guard_name', 'web')->whereIn('name', ['guru', 'operator'])->pluck('id', 'name');

        if ($registerId && isset($roleIds['guru'])) {
            DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $registerId, 'role_id' => $roleIds['guru']]);
        }
        if ($manageId && isset($roleIds['operator'])) {
            DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $manageId, 'role_id' => $roleIds['operator']]);
        }
        if ($manageId) {
            $hrdId = DB::table('roles')->where('name', 'hrd')->where('guard_name', 'web')->value('id');
            DB::table('role_has_permissions')->where(['permission_id' => $manageId, 'role_id' => $hrdId])->delete();
        }
    }

    public function down(): void
    {
        $registerId = DB::table('permissions')->where('name', 'personnel-attendance.register-device')->where('guard_name', 'web')->value('id');
        if ($registerId) {
            DB::table('role_has_permissions')->where('permission_id', $registerId)->delete();
            DB::table('permissions')->where('id', $registerId)->delete();
        }
    }
};
