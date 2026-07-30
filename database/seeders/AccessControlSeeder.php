<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class AccessControlSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $permissions = collect(config('permissions'))->flatMap(fn (array $group) => array_keys($group['permissions']))
            ->mapWithKeys(fn (string $name) => [$name => Permission::findOrCreate($name, 'web')]);

        $roles = [
            'super-admin' => ['Super Admin', 'Akses penuh dan terlindungi.', $permissions->keys()->all()],
            'operator' => ['Operator', 'Mengelola akun pengguna sekolah.', ['dashboard.view', 'users.view', 'users.create', 'users.update', 'users.activate', 'users.reset-password', 'users.assign-role', 'roles.view', 'school-profile.view', 'school-profile.update', 'school-profile.update-logo', 'school-profile.update-leader']],
            'guru' => ['Guru', 'Mengakses layanan untuk guru.', ['dashboard.view', 'school-profile.view']],
        ];
        foreach ($roles as $slug => [$label, $description, $grants]) {
            $role = Role::query()->firstOrCreate(['name' => $slug, 'guard_name' => 'web']);
            $role->update(['display_name' => $label, 'description' => $description, 'is_system' => true]);
            $role->syncPermissions($grants);
        }

        $password = (string) env('SEED_ADMIN_PASSWORD', '');
        if ($password === '' && app()->environment('production')) throw new RuntimeException('SEED_ADMIN_PASSWORD wajib diisi pada lingkungan production.');
        $user = User::query()->updateOrCreate(['email' => strtolower((string) env('SEED_ADMIN_EMAIL', 'admin@example.test'))], [
            'name' => (string) env('SEED_ADMIN_NAME', 'Administrator'),
            'username' => strtolower((string) env('SEED_ADMIN_USERNAME', 'administrator')),
            'password' => Hash::make($password ?: 'password'), 'is_active' => true, 'must_change_password' => false,
        ]);
        $user->syncRoles([Role::query()->where('name', 'super-admin')->firstOrFail()]);
    }
}
