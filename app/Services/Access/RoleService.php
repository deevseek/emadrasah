<?php

declare(strict_types=1);

namespace App\Services\Access;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RoleService
{
    public function normalizePermissions(array $permissions): array
    {
        $allowed = collect(config('permissions'))->flatMap(fn (array $module) => array_keys($module['permissions']));
        $selected = collect($permissions)->intersect($allowed)->unique()->values();

        $selected->each(function (string $permission) use ($selected): void {
            [$module, $action] = explode('.', $permission, 2);
            if ($action !== 'view' && $action !== 'view-own' && $allowedView = $this->viewDependency($module)) {
                $selected->push($allowedView);
            }
        });

        return $selected->unique()->values()->all();
    }

    private function viewDependency(string $module): ?string
    {
        $view = "$module.view";

        return collect(config('permissions'))->flatMap(fn (array $item) => array_keys($item['permissions']))->contains($view) ? $view : null;
    }

    public function create(User $actor, array $data): Role
    {
        return DB::transaction(function () use ($actor, $data): Role {
            $base = Str::slug($data['display_name']); $slug = $base; $suffix = 2;
            while (Role::where('name', $slug)->exists()) $slug = $base.'-'.$suffix++;
            $role = Role::create(['name'=>$slug, 'guard_name'=>'web', 'display_name'=>$data['display_name'], 'description'=>$data['description'] ?? null, 'is_system'=>false]);
            $role->syncPermissions($this->normalizePermissions($data['permissions'] ?? []));
            activity('akses')->causedBy($actor)->performedOn($role)->log("Menambahkan role {$role->display_name}.");
            return $role;
        });
    }

    public function update(User $actor, Role $role, array $data): Role
    {
        abort_if($role->name === 'super-admin', 403, 'Hak akses Super Admin tidak dapat diubah.');
        return DB::transaction(function () use ($actor, $role, $data): Role {
            $before = $role->permissions->pluck('name')->all();
            $role->update(['display_name'=>$role->is_system ? $role->display_name : $data['display_name'], 'description'=>$data['description'] ?? null]);
            $role->syncPermissions($this->normalizePermissions($data['permissions'] ?? []));
            activity('akses')->causedBy($actor)->performedOn($role)->withProperties(['hak_akses_lama'=>$before, 'hak_akses_baru'=>$role->permissions()->pluck('name')->all()])->log("Mengubah hak akses role {$role->display_name}.");
            return $role;
        });
    }

    public function delete(User $actor, Role $role): void
    {
        abort_if($role->is_system, 403, 'Role sistem tidak dapat dihapus.');
        abort_if($role->users()->exists(), 422, 'Role ini masih digunakan oleh pengguna dan belum dapat dihapus.');
        DB::transaction(function () use ($actor, $role): void { $name=$role->display_name; $role->delete(); activity('akses')->causedBy($actor)->log("Menghapus role {$name}."); });
    }
}
