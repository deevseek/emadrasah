<?php

declare(strict_types=1);
namespace App\Services\Access;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserService
{
    public function guard(User $actor, User $target, array $roles = [], ?bool $active = null): void
    {
        abort_if($target->hasRole('super-admin') && ! $actor->hasRole('super-admin'), 403, 'Akun Super Admin tidak dapat diubah oleh Operator.');
        abort_if(in_array('super-admin', $roles, true) && ! $actor->hasRole('super-admin'), 403, 'Role yang dipilih tidak tersedia.');
        if ($roles !== []) {
            abort_unless($actor->hasAnyRole(['operator', 'super-admin', 'kepala-madrasah']), 403, 'Anda tidak berwenang menentukan role pengguna.');
            if (! $actor->hasRole('super-admin')) {
                foreach (Role::query()->whereIn('name', $roles)->get() as $selected) {
                    foreach (['roles.manage-permissions', 'roles.update', 'roles.delete'] as $permission) abort_if($selected->hasPermissionTo($permission) && ! $actor->can($permission), 403, 'Role yang dipilih memiliki hak akses yang tidak dapat Anda berikan.');
                }
            }
        }
        abort_if($active === false && $actor->is($target), 403, 'Anda tidak dapat menonaktifkan akun sendiri.');
        if ($active === false && $target->hasRole('super-admin')) abort_if(User::role('super-admin')->where('is_active', true)->count() <= 1, 403, 'Minimal harus terdapat satu Super Admin aktif.');
        if ($actor->is($target) && $roles !== [] && collect($roles)->sort()->values()->all() !== $target->roles->pluck('name')->sort()->values()->all()) abort(403, 'Anda tidak dapat mengubah role milik sendiri.');
    }
    public function assignRole(User $actor,User $user,Role $role):void{$this->guard($actor,$user,[$role->name]);$old=$user->roles->pluck('name')->all();$user->syncRoles([$role]);activity('akses')->causedBy($actor)->performedOn($user)->withProperties(['role_lama'=>$old,'role_baru'=>[$role->name]])->log("Mengubah role akun {$user->name} menjadi {$role->display_name}.");}
    public function create(User $actor, array $data): User
    {
        return DB::transaction(function () use($actor,$data) { $roles=Role::whereIn('name',$data['roles'])->get(); $this->guard($actor,new User, $roles->pluck('name')->all()); $user=User::create([...Arr::except($data,['roles','password_confirmation']),'password'=>Hash::make($data['password']),'must_change_password'=>true]); $user->syncRoles($roles); activity('akses')->causedBy($actor)->performedOn($user)->withProperties(['roles'=>$roles->pluck('name')->all()])->log("Menambahkan pengguna {$user->name}."); return $user; });
    }
    public function update(User $actor, User $user, array $data): User
    {
        return DB::transaction(function()use($actor,$user,$data){$old=$user->roles->pluck('name')->all();$this->guard($actor,$user,$data['roles'],(bool)$data['is_active']);$user->update(Arr::except($data,'roles'));$user->syncRoles(Role::whereIn('name',$data['roles'])->get());activity('akses')->causedBy($actor)->performedOn($user)->withProperties(['role_lama'=>$old,'role_baru'=>$data['roles'],'status_aktif'=>(bool)$data['is_active']])->log("Memperbarui data pengguna {$user->name}.");return $user->refresh();});
    }
    public function status(User $actor, User $user, bool $active): void { DB::transaction(function()use($actor,$user,$active){$this->guard($actor,$user,[],$active);$user->update(['is_active'=>$active]);if(!$active && config('session.driver')==='database')DB::table(config('session.table','sessions'))->where('user_id',$user->id)->delete();activity('akses')->causedBy($actor)->performedOn($user)->log(($active?'Mengaktifkan':'Menonaktifkan')." akun {$user->name}.");}); }
    public function resetPassword(User $actor, User $user, string $password): void { abort_if($actor->is($user),403,'Gunakan halaman Ganti Password untuk akun sendiri.');$this->guard($actor,$user);DB::transaction(function()use($actor,$user,$password){$user->update(['password'=>Hash::make($password),'must_change_password'=>true,'remember_token'=>null]);if(config('session.driver')==='database')DB::table(config('session.table','sessions'))->where('user_id',$user->id)->delete();activity('akses')->causedBy($actor)->performedOn($user)->log("Mengatur ulang password {$user->name}.");}); }

    public function delete(User $actor, User $user): void
    {
        abort_if($actor->is($user), 403, 'Anda tidak dapat menghapus akun sendiri.');
        $this->guard($actor, $user);
        abort_if($user->hasRole('super-admin') && User::role('super-admin')->count() <= 1, 403, 'Minimal harus terdapat satu akun Super Admin.');

        DB::transaction(function () use ($actor, $user): void {
            $user->personnel()->update([
                'user_id' => null,
                'updated_by' => $actor->id,
            ]);

            activity('akses')->causedBy($actor)->performedOn($user)
                ->withProperties(['nama' => $user->name, 'email' => $user->email])
                ->log("Menghapus akun {$user->name}.");
            if (config('session.driver') === 'database') {
                DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
            }
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();

            // Lepaskan identitas login agar username dan email dapat dipakai kembali,
            // sedangkan baris pengguna tetap dipertahankan untuk relasi dan audit.
            $deletedIdentity = 'deleted-user-'.$user->id.'-'.Str::lower(Str::random(16));
            $user->forceFill([
                'username' => $deletedIdentity,
                'email' => $deletedIdentity.'@deleted.invalid',
                'remember_token' => null,
                'is_active' => false,
            ])->save();

            $user->delete();
        });
    }
}
