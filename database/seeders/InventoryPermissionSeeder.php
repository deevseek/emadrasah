<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class InventoryPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'inventory.view',
            'inventory.create',
            'inventory.update',
            'inventory.delete',
            'inventory.manage-master',
            'inventory.adjust',
            'inventory.transfer',
            'inventory.change-condition',
            'inventory.stock-opname',
            'inventory.verify',
            'inventory.reverse',
            'inventory.report',
            'inventory.print',
            'inventory.export',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $rolePermissions = [
            'super-admin' => $permissions,
            'admin-madrasah' => $permissions,
            'kepala-madrasah' => ['inventory.view', 'inventory.report', 'inventory.print', 'inventory.export', 'inventory.verify'],
            'tata-usaha' => ['inventory.view', 'inventory.create', 'inventory.update', 'inventory.manage-master', 'inventory.adjust', 'inventory.transfer', 'inventory.change-condition', 'inventory.stock-opname', 'inventory.report', 'inventory.print', 'inventory.export'],
            'operator' => ['inventory.view', 'inventory.create', 'inventory.update', 'inventory.manage-master', 'inventory.adjust', 'inventory.transfer', 'inventory.change-condition', 'inventory.stock-opname', 'inventory.report'],
            'bendahara' => ['inventory.view', 'inventory.report', 'inventory.print', 'inventory.export'],
        ];

        foreach ($rolePermissions as $roleName => $assignedPermissions) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();

            if ($role === null) {
                continue;
            }

            $role->givePermissionTo($assignedPermissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
