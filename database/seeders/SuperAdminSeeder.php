<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SuperAdminSeeder extends Seeder { public function run(): void { $password=env('SEED_ADMIN_PASSWORD') ?: (app()->environment(['local','testing']) ? 'password' : Str::password(32)); $admin=User::firstOrCreate(['email'=>env('SEED_ADMIN_EMAIL','admin@example.test')], ['name'=>env('SEED_ADMIN_NAME','Administrator'),'password'=>Hash::make($password),'is_active'=>true]); $admin->roles()->syncWithoutDetaching([Role::where('name','super-admin')->value('id')]); } }
