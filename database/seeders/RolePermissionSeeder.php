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
            'users.activate',
            'users.reset-password',
            'roles.view',
            'roles.manage',
            'academic-years.view',
            'academic-years.create',
            'academic-years.update',
            'academic-years.activate',
            'semesters.view',
            'semesters.create',
            'semesters.update',
            'semesters.activate',
            'grade-levels.view', 'grade-levels.manage', 'grade-levels.create', 'grade-levels.update', 'grade-levels.delete',
            'classrooms.view', 'classrooms.create', 'classrooms.update', 'classrooms.activate', 'classrooms.export', 'classrooms.delete',
            'homeroom-assignments.view', 'homeroom-assignments.manage',
            'subjects.view', 'subjects.create', 'subjects.update', 'subjects.activate', 'subjects.export',
            'employees.view', 'employees.view-own', 'employees.create', 'employees.update', 'employees.activate', 'employees.manage-documents', 'employees.link-account', 'employees.export',
            'teaching-assignments.view', 'teaching-assignments.create', 'teaching-assignments.update', 'teaching-assignments.change-teacher', 'teaching-assignments.activate', 'teaching-assignments.export', 'teaching-assignments.view-own',
            'schedules.view', 'schedules.view-own', 'schedules.create', 'schedules.update', 'schedules.activate', 'schedules.export', 'schedules.print',
            'students.view','students.create','students.update','students.delete','students.change-status','students.manage-documents','students.export','students.view-sensitive',
            'guardians.view','guardians.create','guardians.update','guardians.delete','guardians.link-student','guardians.unlink-student','guardians.view-sensitive','guardians.export',
            'student-guardians.view','student-guardians.create','student-guardians.update','student-guardians.delete',
            'student-enrollments.view','student-enrollments.create','student-enrollments.update','student-enrollments.delete','student-enrollments.transfer','student-enrollments.promote','student-enrollments.cancel','student-enrollments.override-capacity','student-enrollments.export',
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

        Role::findByName('super-admin')->syncPermissions(Permission::query()->where('guard_name', 'web')->pluck('name'));
        Role::findByName('admin-madrasah')->syncPermissions($permissions->except(['users.activate', 'users.reset-password', 'roles.manage'])->values());
        Role::findByName('kepala-madrasah')->syncPermissions($permissions->only(['dashboard.view', 'grade-levels.view', 'classrooms.view', 'subjects.view', 'employees.view', 'employees.export', 'teaching-assignments.view', 'schedules.view','students.view','students.export','students.view-sensitive','guardians.view','guardians.export','guardians.view-sensitive','student-guardians.view','student-enrollments.view','employee-attendances.view','employee-attendances.verify','employee-leaves.view','employee-leaves.approve','employee-leaves.reject','student-attendances.view','teaching-journals.view','teaching-journals.verify','teaching-journals.reject'])->values());
        Role::findByName('tata-usaha')->syncPermissions($permissions->only(['dashboard.view', 'grade-levels.view', 'classrooms.view', 'subjects.view', 'employees.view', 'employees.create', 'employees.update', 'employees.manage-documents', 'employees.export','students.view','students.create','students.update','students.change-status','students.manage-documents','students.export','students.view-sensitive','guardians.view','guardians.create','guardians.update','guardians.link-student','guardians.unlink-student','guardians.export','guardians.view-sensitive','student-guardians.view','student-guardians.create','student-guardians.update','student-enrollments.view','student-enrollments.create','student-enrollments.update','student-enrollments.transfer','employee-attendances.view','employee-attendances.update','employee-attendances.verify','employee-leaves.view','employee-leaves.approve','employee-leaves.reject','student-attendances.view','student-attendances.create','student-attendances.update','teaching-journals.view','teaching-journals.verify','teaching-journals.reject'])->values());
        Role::findByName('operator')->syncPermissions($permissions->only(['dashboard.view', 'grade-levels.view', 'grade-levels.create', 'grade-levels.update', 'classrooms.view', 'classrooms.create', 'classrooms.update', 'subjects.view', 'subjects.create', 'subjects.update', 'employees.view', 'employees.create', 'employees.update', 'employees.activate', 'employees.manage-documents', 'employees.link-account', 'employees.export', 'teaching-assignments.view', 'teaching-assignments.create', 'teaching-assignments.update', 'schedules.view', 'schedules.create', 'schedules.update','students.view','students.create','students.update','students.change-status','students.manage-documents','students.export','students.view-sensitive','guardians.view','guardians.create','guardians.update','guardians.link-student','guardians.unlink-student','guardians.export','guardians.view-sensitive','student-guardians.view','student-guardians.create','student-guardians.update','student-enrollments.view','student-enrollments.create','student-enrollments.update','student-enrollments.transfer','employee-attendances.view','employee-attendances.update','employee-attendances.verify','employee-leaves.view','employee-leaves.approve','employee-leaves.reject','student-attendances.view','student-attendances.create','student-attendances.update','teaching-journals.view','teaching-journals.verify','teaching-journals.reject'])->values());
        Role::findByName('guru-kelas')->syncPermissions($permissions->only(['dashboard.view', 'employees.view-own', 'classrooms.view', 'subjects.view', 'teaching-assignments.view', 'schedules.view','students.view','students.export','students.view-sensitive','guardians.view','guardians.export','guardians.view-sensitive','student-guardians.view','student-enrollments.view','employee-attendances.view','employee-attendances.verify','employee-leaves.view','employee-leaves.approve','employee-leaves.reject','student-attendances.view','teaching-journals.view','teaching-journals.verify','teaching-journals.reject'])->values());
        Role::findByName('guru-mata-pelajaran')->syncPermissions($permissions->only(['dashboard.view', 'employees.view-own', 'subjects.view', 'teaching-assignments.view', 'schedules.view','students.view','students.export','students.view-sensitive','guardians.view','guardians.export','guardians.view-sensitive','student-guardians.view','student-enrollments.view','employee-attendances.view','employee-attendances.verify','employee-leaves.view','employee-leaves.approve','employee-leaves.reject','student-attendances.view','teaching-journals.view','teaching-journals.verify','teaching-journals.reject'])->values());
        Role::findByName('wali-murid')->syncPermissions([$permissions['dashboard.view'], $permissions['employees.view-own']]);

        $foundationTechnical = ['dashboard.view','school-profile.view','school-profile.update','academic-years.view','academic-years.create','academic-years.update','academic-years.activate','semesters.view','semesters.create','semesters.update','semesters.activate','users.view','users.create','users.update','users.activate','users.reset-password','roles.view','roles.manage'];
        Role::findByName('super-admin')->givePermissionTo($foundationTechnical);
        Role::findByName('admin-madrasah')->givePermissionTo(['dashboard.view','school-profile.view','school-profile.update','academic-years.view','academic-years.create','academic-years.update','academic-years.activate','semesters.view','semesters.create','semesters.update','semesters.activate','users.view','users.create','users.update','users.reset-password','roles.view']);
        Role::findByName('operator')->givePermissionTo(['dashboard.view','school-profile.view','school-profile.update','academic-years.view','academic-years.create','academic-years.update','academic-years.activate','semesters.view','semesters.create','semesters.update','semesters.activate','users.view']);
        Role::findByName('kepala-madrasah')->givePermissionTo(['dashboard.view','school-profile.view','academic-years.view','semesters.view','users.view','roles.view']);

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

        $moduleFourPermissions = ['grade-levels.view','grade-levels.manage','classrooms.view','classrooms.create','classrooms.update','classrooms.activate','classrooms.export','homeroom-assignments.view','homeroom-assignments.manage','student-enrollments.view','student-enrollments.create','student-enrollments.transfer','student-enrollments.promote','student-enrollments.cancel','student-enrollments.export','student-enrollments.override-capacity'];
        Role::findByName('super-admin')->givePermissionTo($moduleFourPermissions);
        Role::findByName('admin-madrasah')->givePermissionTo($moduleFourPermissions);
        Role::findByName('operator')->givePermissionTo($moduleFourPermissions);
        Role::findByName('tata-usaha')->givePermissionTo(['grade-levels.view','classrooms.view','classrooms.create','classrooms.update','classrooms.export','student-enrollments.view','student-enrollments.create','student-enrollments.transfer','student-enrollments.export']);
        Role::findByName('kepala-madrasah')->givePermissionTo(['grade-levels.view','classrooms.view','classrooms.export','homeroom-assignments.view','student-enrollments.view','student-enrollments.export','student-enrollments.promote']);
        Role::findByName('guru-kelas')->givePermissionTo(['classrooms.view','homeroom-assignments.view','student-enrollments.view']);

        $moduleFivePermissions = ['subjects.view','subjects.create','subjects.update','subjects.activate','subjects.export','teaching-assignments.view','teaching-assignments.view-own','teaching-assignments.create','teaching-assignments.update','teaching-assignments.change-teacher','teaching-assignments.activate','teaching-assignments.export','schedules.view','schedules.view-own','schedules.create','schedules.update','schedules.activate','schedules.export','schedules.print'];
        Role::findByName('super-admin')->givePermissionTo($moduleFivePermissions);
        Role::findByName('admin-madrasah')->givePermissionTo($moduleFivePermissions);
        Role::findByName('operator')->givePermissionTo(['subjects.view','subjects.create','subjects.update','subjects.activate','subjects.export','teaching-assignments.view','teaching-assignments.create','teaching-assignments.update','teaching-assignments.change-teacher','teaching-assignments.activate','teaching-assignments.export','schedules.view','schedules.create','schedules.update','schedules.activate','schedules.export','schedules.print']);
        Role::findByName('tata-usaha')->givePermissionTo(['subjects.view','teaching-assignments.view','schedules.view','schedules.export','schedules.print']);
        Role::findByName('kepala-madrasah')->givePermissionTo(['subjects.view','teaching-assignments.view','schedules.view','teaching-assignments.export','schedules.export','schedules.print']);
        Role::findByName('guru-kelas')->givePermissionTo(['teaching-assignments.view-own','schedules.view-own','schedules.print']);
        Role::findByName('guru-mata-pelajaran')->givePermissionTo(['teaching-assignments.view-own','schedules.view-own','schedules.print']);
        Role::findByName('guru-btaq-murobi')->givePermissionTo(['teaching-assignments.view-own','schedules.view-own','schedules.print']);

        Role::findByName('super-admin')->syncPermissions(Permission::query()->where('guard_name', 'web')->pluck('name'));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
