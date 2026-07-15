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
            'grade-levels.view', 'grade-levels.create', 'grade-levels.update', 'grade-levels.delete',
            'classrooms.view', 'classrooms.create', 'classrooms.update', 'classrooms.delete',
            'subjects.view', 'subjects.create', 'subjects.update', 'subjects.delete',
            'employees.view', 'employees.create', 'employees.update', 'employees.delete',
            'teaching-assignments.view', 'teaching-assignments.create', 'teaching-assignments.update', 'teaching-assignments.delete',
            'schedules.view', 'schedules.create', 'schedules.update', 'schedules.delete',
            'students.view','students.create','students.update','students.delete','students.change-status','students.manage-documents',
            'guardians.view','guardians.create','guardians.update','guardians.delete',
            'student-guardians.view','student-guardians.create','student-guardians.update','student-guardians.delete',
            'student-enrollments.view','student-enrollments.create','student-enrollments.update','student-enrollments.delete','student-enrollments.transfer',
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
        Role::findByName('kepala-madrasah')->syncPermissions($permissions->only(['dashboard.view', 'grade-levels.view', 'classrooms.view', 'subjects.view', 'employees.view', 'teaching-assignments.view', 'schedules.view','students.view','guardians.view','student-guardians.view','student-enrollments.view'])->values());
        Role::findByName('tata-usaha')->syncPermissions($permissions->only(['dashboard.view', 'grade-levels.view', 'classrooms.view', 'subjects.view', 'employees.view', 'employees.create', 'employees.update','students.view','students.create','students.update','students.change-status','students.manage-documents','guardians.view','guardians.create','guardians.update','student-guardians.view','student-guardians.create','student-guardians.update','student-enrollments.view','student-enrollments.create','student-enrollments.update','student-enrollments.transfer'])->values());
        Role::findByName('operator')->syncPermissions($permissions->only(['dashboard.view', 'grade-levels.view', 'grade-levels.create', 'grade-levels.update', 'classrooms.view', 'classrooms.create', 'classrooms.update', 'subjects.view', 'subjects.create', 'subjects.update', 'employees.view', 'teaching-assignments.view', 'teaching-assignments.create', 'teaching-assignments.update', 'schedules.view', 'schedules.create', 'schedules.update','students.view','students.create','students.update','guardians.view','guardians.create','guardians.update','student-guardians.view','student-guardians.create','student-guardians.update','student-enrollments.view','student-enrollments.create','student-enrollments.update','student-enrollments.transfer'])->values());
        Role::findByName('guru-kelas')->syncPermissions($permissions->only(['dashboard.view', 'classrooms.view', 'subjects.view', 'teaching-assignments.view', 'schedules.view','students.view','guardians.view','student-guardians.view','student-enrollments.view'])->values());
        Role::findByName('guru-mata-pelajaran')->syncPermissions($permissions->only(['dashboard.view', 'subjects.view', 'teaching-assignments.view', 'schedules.view','students.view','guardians.view','student-guardians.view','student-enrollments.view'])->values());
        Role::findByName('wali-murid')->syncPermissions([$permissions['dashboard.view']]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
