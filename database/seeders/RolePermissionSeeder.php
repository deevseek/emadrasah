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
            'employee-attendances.view-own','employee-attendances.check-in','employee-attendances.check-out','employee-attendances.view','employee-attendances.update','employee-attendances.verify','employee-attendances.export',
            'employee-leaves.view-own','employee-leaves.create','employee-leaves.cancel','employee-leaves.view','employee-leaves.approve','employee-leaves.reject',
            'student-attendances.view','student-attendances.create','student-attendances.update','student-attendances.export',
            'teaching-journals.view-own','teaching-journals.create','teaching-journals.update','teaching-journals.submit','teaching-journals.view','teaching-journals.verify','teaching-journals.reject','teaching-journals.export',
            'btaq-levels.view',
            'btaq-levels.manage',
            'btaq-materials.view',
            'btaq-materials.manage',
            'btaq-groups.view',
            'btaq-groups.manage',
            'btaq-journals.view-own',
            'btaq-journals.create',
            'btaq-journals.update',
            'btaq-journals.submit',
            'btaq-journals.view',
            'btaq-journals.verify',
            'btaq-journals.reject',
            'btaq-reports.view',
            'assessments.view-own',
            'assessments.create',
            'assessments.update',
            'assessments.publish',
            'assessments.unlock',
            'assessments.view',
            'assessment-reports.view',
            'predicate-ranges.manage',
            'report-cards.view-class',
            'report-cards.generate',
            'report-cards.update',
            'report-cards.submit',
            'report-cards.view',
            'report-cards.approve',
            'report-cards.lock',
            'report-cards.reopen',
            'report-cards.print',

            'fee-types.view', 'fee-types.manage',
            'student-invoices.view', 'student-invoices.create', 'student-invoices.generate', 'student-invoices.update', 'student-invoices.cancel', 'student-invoices.export',
            'student-payments.view', 'student-payments.create', 'student-payments.cancel', 'student-payments.print', 'student-payments.export',
            'student-discounts.view', 'student-discounts.manage', 'student-discounts.approve',
            'finance-accounts.view', 'finance-accounts.manage',
            'finance-transactions.view', 'finance-transactions.create', 'finance-transactions.update', 'finance-transactions.post', 'finance-transactions.cancel', 'finance-transactions.approve',
            'finance-reports.view', 'finance-reports.export',
            'salary-components.view', 'salary-components.manage',
            'employee-salaries.view', 'employee-salaries.manage',
            'payroll-periods.view', 'payroll-periods.create',
            'payrolls.calculate', 'payrolls.review', 'payrolls.approve', 'payrolls.mark-paid', 'payrolls.close', 'payrolls.reopen', 'payrolls.view', 'payrolls.print', 'payrolls.export',
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
        Role::findByName('kepala-madrasah')->syncPermissions($permissions->only(['dashboard.view', 'grade-levels.view', 'classrooms.view', 'subjects.view', 'employees.view', 'teaching-assignments.view', 'schedules.view','students.view','guardians.view','student-guardians.view','student-enrollments.view','employee-attendances.view','employee-attendances.verify','employee-leaves.view','employee-leaves.approve','employee-leaves.reject','student-attendances.view','teaching-journals.view','teaching-journals.verify','teaching-journals.reject'])->values());
        Role::findByName('tata-usaha')->syncPermissions($permissions->only(['dashboard.view', 'grade-levels.view', 'classrooms.view', 'subjects.view', 'employees.view', 'employees.create', 'employees.update','students.view','students.create','students.update','students.change-status','students.manage-documents','guardians.view','guardians.create','guardians.update','student-guardians.view','student-guardians.create','student-guardians.update','student-enrollments.view','student-enrollments.create','student-enrollments.update','student-enrollments.transfer','employee-attendances.view','employee-attendances.update','employee-attendances.verify','employee-leaves.view','employee-leaves.approve','employee-leaves.reject','student-attendances.view','student-attendances.create','student-attendances.update','teaching-journals.view','teaching-journals.verify','teaching-journals.reject'])->values());
        Role::findByName('operator')->syncPermissions($permissions->only(['dashboard.view', 'grade-levels.view', 'grade-levels.create', 'grade-levels.update', 'classrooms.view', 'classrooms.create', 'classrooms.update', 'subjects.view', 'subjects.create', 'subjects.update', 'employees.view', 'teaching-assignments.view', 'teaching-assignments.create', 'teaching-assignments.update', 'schedules.view', 'schedules.create', 'schedules.update','students.view','students.create','students.update','guardians.view','guardians.create','guardians.update','student-guardians.view','student-guardians.create','student-guardians.update','student-enrollments.view','student-enrollments.create','student-enrollments.update','student-enrollments.transfer','employee-attendances.view','employee-attendances.update','employee-attendances.verify','employee-leaves.view','employee-leaves.approve','employee-leaves.reject','student-attendances.view','student-attendances.create','student-attendances.update','teaching-journals.view','teaching-journals.verify','teaching-journals.reject'])->values());
        Role::findByName('guru-kelas')->syncPermissions($permissions->only(['dashboard.view', 'classrooms.view', 'subjects.view', 'teaching-assignments.view', 'schedules.view','students.view','guardians.view','student-guardians.view','student-enrollments.view','employee-attendances.view','employee-attendances.verify','employee-leaves.view','employee-leaves.approve','employee-leaves.reject','student-attendances.view','teaching-journals.view','teaching-journals.verify','teaching-journals.reject'])->values());
        Role::findByName('guru-mata-pelajaran')->syncPermissions($permissions->only(['dashboard.view', 'subjects.view', 'teaching-assignments.view', 'schedules.view','students.view','guardians.view','student-guardians.view','student-enrollments.view','employee-attendances.view','employee-attendances.verify','employee-leaves.view','employee-leaves.approve','employee-leaves.reject','student-attendances.view','teaching-journals.view','teaching-journals.verify','teaching-journals.reject'])->values());
        Role::findByName('wali-murid')->syncPermissions([$permissions['dashboard.view']]);

        $financePermissions = $permissions->only([
            'fee-types.view', 'fee-types.manage', 'student-invoices.view', 'student-invoices.create', 'student-invoices.generate', 'student-invoices.update', 'student-invoices.cancel', 'student-invoices.export', 'student-payments.view', 'student-payments.create', 'student-payments.cancel', 'student-payments.print', 'student-payments.export', 'student-discounts.view', 'student-discounts.manage', 'student-discounts.approve', 'finance-accounts.view', 'finance-accounts.manage', 'finance-transactions.view', 'finance-transactions.create', 'finance-transactions.update', 'finance-transactions.post', 'finance-transactions.cancel', 'finance-transactions.approve', 'finance-reports.view', 'finance-reports.export', 'salary-components.view', 'salary-components.manage', 'employee-salaries.view', 'employee-salaries.manage', 'payroll-periods.view', 'payroll-periods.create', 'payrolls.calculate', 'payrolls.review', 'payrolls.approve', 'payrolls.mark-paid', 'payrolls.close', 'payrolls.reopen', 'payrolls.view', 'payrolls.print', 'payrolls.export',
        ])->values();
        Role::findByName('bendahara')->syncPermissions($financePermissions->merge([$permissions['dashboard.view']]));
        Role::findByName('kepala-madrasah')->givePermissionTo(['student-invoices.view', 'student-payments.view', 'finance-reports.view', 'payrolls.view', 'payrolls.approve']);
        Role::findByName('tata-usaha')->givePermissionTo(['fee-types.view', 'student-invoices.view', 'student-payments.view']);


        Role::findByName('kepala-madrasah')->givePermissionTo(['btaq-journals.view','btaq-journals.verify','btaq-journals.reject','btaq-reports.view','assessment-reports.view','report-cards.view','report-cards.approve','report-cards.lock','report-cards.reopen','report-cards.print']);
        Role::findByName('guru-kelas')->givePermissionTo(['assessments.view-own','assessments.create','assessments.update','assessments.publish','assessment-reports.view','report-cards.view-class','report-cards.generate','report-cards.update','report-cards.submit','report-cards.view','report-cards.print']);
        Role::findByName('guru-mata-pelajaran')->givePermissionTo(['assessments.view-own','assessments.create','assessments.update','assessments.publish','assessment-reports.view']);
        Role::findByName('guru-btaq-murobi')->givePermissionTo(['btaq-groups.view','btaq-journals.view-own','btaq-journals.create','btaq-journals.update','btaq-journals.submit','btaq-reports.view']);
        Role::findByName('operator')->givePermissionTo(['btaq-levels.view','btaq-levels.manage','btaq-materials.view','btaq-materials.manage','btaq-groups.view','btaq-groups.manage','predicate-ranges.manage','report-cards.view']);
        Role::findByName('tata-usaha')->givePermissionTo(['report-cards.view','report-cards.print','btaq-reports.view','assessment-reports.view']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
