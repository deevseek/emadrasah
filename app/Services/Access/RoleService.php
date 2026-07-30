<?php

declare(strict_types=1);
namespace App\Services\Access;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

class RoleService
{
    private function normalized(array $permissions): array { $p=collect($permissions)->unique(); if($p->contains(fn($x)=>str_starts_with($x,'users.')&&$x!=='users.view'))$p->push('users.view'); if($p->contains(fn($x)=>str_starts_with($x,'roles.')&&$x!=='roles.view'))$p->push('roles.view'); return $p->unique()->all(); }
    public function create(User $actor,array $data):Role{return DB::transaction(function()use($actor,$data){$base=Str::slug($data['display_name']);$slug=$base;$i=2;while(Role::where('name',$slug)->exists())$slug=$base.'-'.$i++;$role=Role::create(['name'=>$slug,'guard_name'=>'web','display_name'=>$data['display_name'],'description'=>$data['description']??null,'is_system'=>false]);$role->syncPermissions($this->normalized($data['permissions']??[]));activity('akses')->causedBy($actor)->performedOn($role)->log("Menambahkan role {$role->display_name}.");return $role;});}
    public function update(User $actor,Role $role,array $data):Role{abort_if($role->name==='super-admin',403,'Hak akses Super Admin tidak dapat diubah.');return DB::transaction(function()use($actor,$role,$data){$before=$role->permissions->pluck('name')->all();$role->update(['display_name'=>$data['display_name'],'description'=>$data['description']??null]);$role->syncPermissions($this->normalized($data['permissions']??[]));activity('akses')->causedBy($actor)->performedOn($role)->withProperties(['hak_akses_lama'=>$before,'hak_akses_baru'=>$role->permissions()->pluck('name')->all()])->log("Mengubah hak akses role {$role->display_name}.");return $role;});}
    public function delete(User $actor,Role $role):void{abort_if($role->is_system,403,'Role sistem tidak dapat dihapus.');abort_if($role->users()->exists(),422,'Role tidak dapat dihapus karena masih digunakan oleh pengguna.');DB::transaction(function()use($actor,$role){$name=$role->display_name;$role->delete();activity('akses')->causedBy($actor)->log("Menghapus role {$name}.");});}
}
