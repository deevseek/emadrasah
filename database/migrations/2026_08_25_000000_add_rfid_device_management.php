<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('rfid_devices', function (Blueprint $table): void {
            $table->string('device_type', 20)->default('reader')->after('name')->index();
        });
        DB::table('rfid_devices')->whereIn('mode', ['writer', 'writing'])->update(['device_type' => 'writer']);
        DB::table('permissions')->insertOrIgnore(['name' => 'rfid-device.manage', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]);
        $permissionId = DB::table('permissions')->where('name', 'rfid-device.manage')->where('guard_name', 'web')->value('id');
        $roleIds = DB::table('roles')->where('guard_name', 'web')->whereIn('name', ['super-admin', 'operator'])->pluck('id');
        foreach ($roleIds as $roleId) DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $permissionId, 'role_id' => $roleId]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $permissionIds = DB::table('permissions')->where('name', 'rfid-device.manage')->where('guard_name', 'web')->pluck('id');
        DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
        Schema::table('rfid_devices', fn (Blueprint $table) => $table->dropColumn('device_type'));
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
