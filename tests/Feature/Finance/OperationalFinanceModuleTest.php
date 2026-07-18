<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OperationalFinanceModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_operational_finance_dashboard_requires_permission(): void
    {
        $role = Role::firstOrCreate(['name' => 'bendahara', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'operational-finance-dashboard.view', 'guard_name' => 'web']);
        $role->givePermissionTo('operational-finance-dashboard.view');
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)->get(route('operational-finance.dashboard'))->assertOk();
    }

    public function test_teacher_without_permission_is_forbidden(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('operational-finance.dashboard'))->assertForbidden();
    }
}
