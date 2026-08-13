<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class AccessControlSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $roles = [
            'super-admin' => ['Super Admin', 'Memiliki seluruh akses aplikasi dan terlindungi oleh sistem.', []],
            'kepala-madrasah' => ['Kepala Madrasah', 'Memantau data utama madrasah, periode akademik, personalia, pengguna, dan hak akses.', ['academic-subjects.view', 'teaching-journals.view', 'teaching-journals.view-all', 'classroom-journals.view', 'classroom-journals.view-all', 'academic-attendance.view', 'academic-grades.view', 'academic-reports.view', 'academic-reports.export', 'dashboard.view', 'school-profile.view', 'academic-periods.view', 'personnel.view', 'personnel.view-sensitive', 'personnel.export', 'students.view', 'students.view-sensitive', 'students.export', 'classrooms.view', 'users.view', 'roles.view']],
            'operator' => ['Operator', 'Mengelola data operasional, akun, periode akademik, dan personalia madrasah.', ['rfid-card.view', 'rfid-card.issue', 'rfid-card.replace', 'rfid-card.disable', 'rfid-writer.use', 'academic-subjects.view', 'academic-subjects.manage', 'teaching-journals.view', 'teaching-journals.manage', 'teaching-journals.view-all', 'classroom-journals.view', 'classroom-journals.manage', 'classroom-journals.view-all', 'academic-attendance.view', 'academic-attendance.manage', 'academic-grades.view', 'academic-grades.manage', 'academic-reports.view', 'academic-reports.export', 'dashboard.view', 'school-profile.view', 'school-profile.update', 'school-profile.update-logo', 'school-profile.update-leader', 'academic-periods.view', 'academic-periods.create', 'academic-periods.update', 'academic-periods.activate', 'personnel.view', 'personnel.create', 'personnel.update', 'personnel.activate', 'personnel.manage-account', 'personnel.view-sensitive', 'personnel.import', 'personnel.export', 'students.view', 'students.create', 'students.update', 'students.change-status', 'students.view-sensitive', 'students.import', 'students.export', 'classrooms.view', 'classrooms.create', 'classrooms.update', 'classrooms.activate', 'classrooms.assign-homeroom', 'classrooms.manage-students', 'classrooms.map-legacy', 'classrooms.copy-structure', 'classrooms.promote', 'users.view', 'users.create', 'users.update', 'users.activate', 'users.reset-password', 'users.assign-role', 'roles.view']],
            'guru' => ['Guru', 'Mengakses layanan dasar yang disediakan untuk guru.', ['academic-subjects.view', 'teaching-journals.view', 'teaching-journals.manage', 'classroom-journals.view', 'classroom-journals.manage', 'academic-attendance.view', 'academic-attendance.manage', 'academic-grades.view', 'academic-grades.manage', 'academic-reports.view', 'dashboard.view', 'school-profile.view', 'academic-periods.view', 'classrooms.view-own']],
        ];

        $configuredPermissions = collect(config('permissions'))
            ->flatMap(fn (array $group) => array_keys($group['permissions']));
        $defaultRolePermissions = collect($roles)
            ->flatMap(fn (array $role) => $role[2]);
        $permissions = $configuredPermissions
            ->merge($defaultRolePermissions)
            ->unique()
            ->mapWithKeys(fn (string $name) => [$name => Permission::findOrCreate($name, 'web')]);
        $roles['super-admin'][2] = $permissions->keys()->all();

        foreach ($roles as $slug => [$label, $description, $grants]) {
            $isNew = ! Role::query()->where('name', $slug)->where('guard_name', 'web')->exists();
            $role = Role::findOrCreate($slug, 'web');
            $role->update(['display_name' => $label, 'description' => $description, 'is_system' => true]);
            $slug === 'super-admin' ? $role->syncPermissions($grants) : ($isNew ? $role->syncPermissions($grants) : null);
            if ($isNew && $slug === 'kepala-madrasah') activity('akses')->performedOn($role)->log('Membuat role sistem Kepala Madrasah.');
        }

        $password = (string) env('SEED_ADMIN_PASSWORD', '');
        $email = strtolower((string) env('SEED_ADMIN_EMAIL', 'admin@example.test'));
        $attributes = [
            'name' => (string) env('SEED_ADMIN_NAME', 'Administrator'),
            'username' => strtolower((string) env('SEED_ADMIN_USERNAME', 'administrator')),
            'is_active' => true,
            'must_change_password' => false,
        ];

        if ($password === '' && app()->environment('production')) {
            $user = User::query()->where('email', $email)->first();

            if ($user === null) {
                $this->command?->warn('Akun super admin tidak dibuat karena SEED_ADMIN_PASSWORD belum diisi.');
                app(PermissionRegistrar::class)->forgetCachedPermissions();

                return;
            }

            $user->update($attributes);
        } else {
            $user = User::query()->updateOrCreate(['email' => $email], [
                ...$attributes,
                'password' => Hash::make($password ?: 'password'),
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user->syncRoles([Role::query()->where('name', 'super-admin')->firstOrFail()]);
    }
}
