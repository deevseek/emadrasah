<?php

declare(strict_types=1);
namespace App\Services\Access;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function guard(User $actor, User $target, ?string $role = null, ?bool $active = null): void
    {
        abort_if($target->hasRole('super-admin') && ! $actor->hasRole('super-admin'), 403, 'Akun Super Admin tidak dapat diubah oleh Operator.');
        abort_if($role === 'super-admin' && ! $actor->hasRole('super-admin'), 403, 'Role yang dipilih tidak tersedia.');
        if ($role !== null && ! $actor->hasRole('super-admin')) {
            $selected = Role::query()->where('name', $role)->first();
            foreach (['roles.manage-permissions', 'roles.update', 'roles.delete'] as $permission) abort_if($selected?->hasPermissionTo($permission) && ! $actor->can($permission), 403, 'Role yang dipilih memiliki hak akses yang tidak dapat Anda berikan.');
        }
        abort_if($active === false && $actor->is($target), 403, 'Anda tidak dapat menonaktifkan akun sendiri.');
        if ($active === false && $target->hasRole('super-admin')) abort_if(User::role('super-admin')->where('is_active', true)->count() <= 1, 403, 'Minimal harus terdapat satu Super Admin aktif.');
        if ($actor->is($target) && $role !== null && $role !== $target->roles->first()?->name) abort(403, 'Anda tidak dapat menghapus role milik sendiri.');
    }
    public function assignRole(User $actor,User $user,Role $role):void{$this->guard($actor,$user,$role->name);$old=$user->roles->first()?->display_name??'Tanpa role';$user->syncRoles([$role]);activity('akses')->causedBy($actor)->performedOn($user)->withProperties(['role_lama'=>$old,'role_baru'=>$role->name])->log("Mengubah role akun {$user->name} menjadi {$role->display_name}.");}
    public function create(User $actor, array $data): User
    {
        return DB::transaction(function () use($actor,$data) { $role=Role::where('name',$data['role'])->firstOrFail(); $this->guard($actor,new User, $role->name); $user=User::create([...Arr::except($data,['role','password_confirmation']),'password'=>Hash::make($data['password']),'must_change_password'=>true]); $user->syncRoles([$role]); activity('akses')->causedBy($actor)->performedOn($user)->withProperties(['role'=>$role->name])->log("Menambahkan pengguna {$user->name}."); return $user; });
    }
    public function update(User $actor, User $user, array $data): User
    {
        return DB::transaction(function()use($actor,$user,$data){$old=$user->roles->first()?->name;$this->guard($actor,$user,$data['role'],(bool)$data['is_active']);$user->update(Arr::except($data,'role'));$user->syncRoles([Role::where('name',$data['role'])->firstOrFail()]);activity('akses')->causedBy($actor)->performedOn($user)->withProperties(['role_lama'=>$old,'role_baru'=>$data['role'],'status_aktif'=>(bool)$data['is_active']])->log("Memperbarui data pengguna {$user->name}.");return $user->refresh();});
    }
    public function status(User $actor, User $user, bool $active): void { DB::transaction(function()use($actor,$user,$active){$this->guard($actor,$user,null,$active);$user->update(['is_active'=>$active]);if(!$active && config('session.driver')==='database')DB::table(config('session.table','sessions'))->where('user_id',$user->id)->delete();activity('akses')->causedBy($actor)->performedOn($user)->log(($active?'Mengaktifkan':'Menonaktifkan')." akun {$user->name}.");}); }
    public function resetPassword(User $actor, User $user, string $password): void { abort_if($actor->is($user),403,'Gunakan halaman Ganti Password untuk akun sendiri.');$this->guard($actor,$user);DB::transaction(function()use($actor,$user,$password){$user->update(['password'=>Hash::make($password),'must_change_password'=>true,'remember_token'=>null]);if(config('session.driver')==='database')DB::table(config('session.table','sessions'))->where('user_id',$user->id)->delete();activity('akses')->causedBy($actor)->performedOn($user)->log("Mengatur ulang password {$user->name}.");}); }
}
