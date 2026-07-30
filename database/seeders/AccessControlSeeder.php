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
            'super-admin' => ['Super Admin', 'Memiliki seluruh akses aplikasi dan terlindungi oleh sistem.', $permissions->keys()->all()],
            'kepala-madrasah' => ['Kepala Madrasah', 'Memantau data utama madrasah, periode akademik, personalia, pengguna, dan hak akses.', ['dashboard.view', 'school-profile.view', 'academic-periods.view', 'personnel.view', 'personnel.view-sensitive', 'personnel.export', 'students.view', 'students.view-sensitive', 'students.export', 'users.view', 'roles.view']],
            'operator' => ['Operator', 'Mengelola data operasional, akun, periode akademik, dan personalia madrasah.', ['dashboard.view', 'school-profile.view', 'school-profile.update', 'school-profile.update-logo', 'school-profile.update-leader', 'academic-periods.view', 'academic-periods.create', 'academic-periods.update', 'academic-periods.activate', 'personnel.view', 'personnel.create', 'personnel.update', 'personnel.activate', 'personnel.manage-account', 'personnel.view-sensitive', 'personnel.import', 'personnel.export', 'students.view', 'students.create', 'students.update', 'students.change-status', 'students.view-sensitive', 'students.import', 'students.export', 'users.view', 'users.create', 'users.update', 'users.activate', 'users.reset-password', 'users.assign-role', 'roles.view']],
            'guru' => ['Guru', 'Mengakses layanan dasar yang disediakan untuk guru.', ['dashboard.view', 'school-profile.view', 'academic-periods.view']],
        ];
        foreach ($roles as $slug => [$label, $description, $grants]) {
            $isNew = ! Role::query()->where('name', $slug)->where('guard_name', 'web')->exists();
            $role = Role::findOrCreate($slug, 'web');
            $role->update(['display_name' => $label, 'description' => $description, 'is_system' => true]);
            $slug === 'super-admin' ? $role->syncPermissions($grants) : $role->givePermissionTo($grants);
            if ($isNew && $slug === 'kepala-madrasah') activity('akses')->performedOn($role)->log('Membuat role sistem Kepala Madrasah.');
        }

        $password = (string) env('SEED_ADMIN_PASSWORD', '');
        if ($password === '' && app()->environment('production')) throw new RuntimeException('SEED_ADMIN_PASSWORD wajib diisi pada lingkungan production.');
        $user = User::query()->updateOrCreate(['email' => strtolower((string) env('SEED_ADMIN_EMAIL', 'admin@example.test'))], [
            'name' => (string) env('SEED_ADMIN_NAME', 'Administrator'),
            'username' => strtolower((string) env('SEED_ADMIN_USERNAME', 'administrator')),
            'password' => Hash::make($password ?: 'password'), 'is_active' => true, 'must_change_password' => false,
        ]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user->syncRoles([Role::query()->where('name', 'super-admin')->firstOrFail()]);
    }
}
