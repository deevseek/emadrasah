<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SchoolProfile;
use App\Models\SchoolSetting;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = collect(['dashboard.view','school-profile.view','school-profile.update','academic-years.view','academic-years.create','academic-years.update','academic-years.activate','semesters.view','semesters.create','semesters.update','semesters.activate','settings.view','settings.update','users.view','users.create','users.update','users.deactivate','roles.view','roles.update','audit.view','backup.view','backup.run'])
            ->mapWithKeys(fn (string $name) => [$name => Permission::firstOrCreate(['name' => $name], ['description' => Str::headline(str_replace('.', ' ', $name))])]);

        $roles = ['super-admin'=>'Super Admin','admin-madrasah'=>'Admin Madrasah','kepala-madrasah'=>'Kepala Madrasah','bendahara'=>'Bendahara','tata-usaha'=>'Tata Usaha','operator'=>'Operator','guru-kelas'=>'Guru Kelas','guru-mata-pelajaran'=>'Guru Mata Pelajaran','guru-btaq-murobi'=>'Guru BTAQ atau Murobi','guru-full-day'=>'Guru Full Day','wali-murid'=>'Wali Murid'];
        foreach ($roles as $name => $display) { Role::firstOrCreate(['name'=>$name], ['display_name'=>$display]); }
        Role::where('name','super-admin')->first()->permissions()->sync($permissions->pluck('id'));
        Role::where('name','admin-madrasah')->first()->permissions()->sync($permissions->except(['backup.run'])->pluck('id'));
        Role::where('name','wali-murid')->first()->permissions()->sync($permissions->only(['dashboard.view'])->pluck('id'));

        $password = env('SEED_ADMIN_PASSWORD') ?: 'password';
        $admin = User::firstOrCreate(['email' => env('SEED_ADMIN_EMAIL', 'admin@example.test')], ['name' => env('SEED_ADMIN_NAME', 'Administrator'), 'password' => Hash::make($password), 'is_active' => true]);
        $admin->roles()->syncWithoutDetaching([Role::where('name','super-admin')->value('id')]);

        SchoolProfile::firstOrCreate(['id'=>1], ['school_name'=>'MI Muslimat NU','foundation_name'=>'Yayasan Muslimat NU','timezone'=>'Asia/Jakarta']);
        $year = AcademicYear::firstOrCreate(['name'=>'2026/2027'], ['starts_on'=>'2026-07-01','ends_on'=>'2027-06-30','is_active'=>true]);
        Semester::firstOrCreate(['academic_year_id'=>$year->id,'term'=>1], ['name'=>'Ganjil','starts_on'=>'2026-07-01','ends_on'=>'2026-12-31','is_active'=>true]);
        Semester::firstOrCreate(['academic_year_id'=>$year->id,'term'=>2], ['name'=>'Genap','starts_on'=>'2027-01-01','ends_on'=>'2027-06-30','is_active'=>false]);
        foreach ([['attendance','school_latitude','0','decimal'],['attendance','school_longitude','0','decimal'],['attendance','radius_meters','100','integer'],['attendance','late_tolerance_minutes','10','integer'],['attendance','regular_checkout_time','13:30','time'],['attendance','btaq_checkout_time','10:30','time'],['attendance','full_day_checkout_time','15:00','time'],['features','face_recognition_enabled','false','boolean'],['whatsapp','enabled','false','boolean'],['backup','retention_days','14','integer']] as $s) {
            SchoolSetting::firstOrCreate(['group'=>$s[0], 'key'=>$s[1]], ['value'=>$s[2], 'type'=>$s[3], 'is_public'=>false]);
        }
    }
}
