<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect([
            'dashboard.view',
            'school-profile.view',
            'school-profile.update',
            'settings.view',
            'settings.update',
            'users.view',
            'users.create',
            'users.update',
            'users.deactivate',
            'grade-levels.view','grade-levels.create','grade-levels.update','grade-levels.delete',
            'classrooms.view','classrooms.create','classrooms.update','classrooms.delete',
            'subjects.view','subjects.create','subjects.update','subjects.delete',
            'employees.view','employees.create','employees.update','employees.delete',
            'teaching-assignments.view','teaching-assignments.create','teaching-assignments.update','teaching-assignments.delete',
            'schedules.view','schedules.create','schedules.update','schedules.delete',
        ])->mapWithKeys(fn (string $name): array => [
            $name => Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]),
        ]);

        $roles = [
            'super-admin' => 'Super Admin',
            'admin-madrasah' => 'Admin Madrasah',
            'kepala-madrasah' => 'Kepala Madrasah',
            'bendahara' => 'Bendahara',
            'tata-usaha' => 'Tata Usaha',
            'operator' => 'Operator',
            'guru-kelas' => 'Guru Kelas',
            'guru-mata-pelajaran' => 'Guru Mata Pelajaran',
            'guru-btaq-murobi' => 'Guru BTAQ/Murobi',
            'guru-full-day' => 'Guru Full Day',
            'wali-murid' => 'Wali Murid',
        ];

        foreach ($roles as $name => $displayName) {
            Role::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ])->forceFill(['display_name' => $displayName])->save();
        }

        Role::findByName('super-admin')->syncPermissions($permissions->values());
        Role::findByName('admin-madrasah')->syncPermissions($permissions->except(['users.deactivate'])->values());
        Role::findByName('kepala-madrasah')->syncPermissions($permissions->only(['dashboard.view','grade-levels.view','classrooms.view','subjects.view','employees.view','teaching-assignments.view','schedules.view'])->values());
        Role::findByName('tata-usaha')->syncPermissions($permissions->only(['dashboard.view','grade-levels.view','classrooms.view','subjects.view','employees.view','employees.create','employees.update'])->values());
        Role::findByName('operator')->syncPermissions($permissions->only(['dashboard.view','grade-levels.view','grade-levels.create','grade-levels.update','classrooms.view','classrooms.create','classrooms.update','subjects.view','subjects.create','subjects.update','employees.view','teaching-assignments.view','teaching-assignments.create','teaching-assignments.update','schedules.view','schedules.create','schedules.update'])->values());
        Role::findByName('guru-kelas')->syncPermissions($permissions->only(['dashboard.view','classrooms.view','subjects.view','teaching-assignments.view','schedules.view'])->values());
        Role::findByName('guru-mata-pelajaran')->syncPermissions($permissions->only(['dashboard.view','subjects.view','teaching-assignments.view','schedules.view'])->values());
        Role::findByName('wali-murid')->syncPermissions([$permissions['dashboard.view']]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
