<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $permission = Permission::findOrCreate('dashboard.view', 'web');
        $role = Role::findOrCreate('super-admin', 'web');
        $role->syncPermissions([$permission]);

        $password = (string) env('SEED_ADMIN_PASSWORD', '');
        if ($password === '') {
            if (app()->environment('production')) {
                throw new RuntimeException('SEED_ADMIN_PASSWORD wajib diisi pada lingkungan production.');
            }
            $password = 'password';
        }

        $user = User::query()->updateOrCreate(
            ['email' => (string) env('SEED_ADMIN_EMAIL', 'admin@example.test')],
            [
                'name' => (string) env('SEED_ADMIN_NAME', 'Administrator'),
                'password' => Hash::make($password),
                'is_active' => true,
            ],
        );
        $user->syncRoles([$role]);
    }
}
