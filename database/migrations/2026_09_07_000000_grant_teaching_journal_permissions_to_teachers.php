<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration {
    /**
     * Pastikan instalasi yang sudah berjalan menerima hak akses jurnal tanpa
     * harus menjalankan ulang seluruh seeder akses kontrol.
     */
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect([
            'teaching-journals.view',
            'teaching-journals.manage',
        ])->map(fn (string $name): Permission => Permission::findOrCreate($name, 'web'));

        Role::query()
            ->where('name', 'guru')
            ->where('guard_name', 'web')
            ->first()
            ?->givePermissionTo($permissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::query()
            ->where('name', 'guru')
            ->where('guard_name', 'web')
            ->first();

        if ($role) {
            $permissions = Permission::query()
                ->where('guard_name', 'web')
                ->whereIn('name', ['teaching-journals.view', 'teaching-journals.manage'])
                ->get();

            $role->revokePermissionTo($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
