<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = collect(['dashboard.view','school-profile.view','school-profile.update','academic-years.view','academic-years.create','academic-years.update','academic-years.activate','semesters.view','semesters.create','semesters.update','semesters.activate','settings.view','settings.update','users.view','users.create','users.update','users.deactivate','roles.view','roles.update','audit.view','backup.view','backup.run'])
            ->mapWithKeys(fn (string $name) => [$name => Permission::firstOrCreate(['name' => $name], ['description' => Str::headline(str_replace('.', ' ', $name))])]);
        $roles = ['super-admin'=>'Super Admin','admin-madrasah'=>'Admin Madrasah','kepala-madrasah'=>'Kepala Madrasah','bendahara'=>'Bendahara','tata-usaha'=>'Tata Usaha','operator'=>'Operator','guru-kelas'=>'Guru Kelas','guru-mata-pelajaran'=>'Guru Mata Pelajaran','guru-btaq-murobi'=>'Guru BTAQ/Murobi','guru-full-day'=>'Guru Full Day','wali-murid'=>'Wali Murid'];
        foreach ($roles as $name => $display) Role::firstOrCreate(['name'=>$name], ['display_name'=>$display]);
        Role::where('name','super-admin')->first()->permissions()->sync($permissions->pluck('id'));
        Role::where('name','admin-madrasah')->first()->permissions()->sync($permissions->except(['backup.run'])->pluck('id'));
        Role::where('name','wali-murid')->first()->permissions()->sync($permissions->only(['dashboard.view'])->pluck('id'));
    }
}
