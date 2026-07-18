<?php

namespace Tests\Feature\Inventory;

use App\Models\User;
use Database\Seeders\InventoryMasterSeeder;
use Database\Seeders\InventoryPermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class InventoryNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(InventoryPermissionSeeder::class);
        $this->seed(InventoryMasterSeeder::class);
    }

    public function test_super_admin_sees_inventory_navigation_with_valid_links_and_breadcrumb(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $response = $this->actingAs($user)->get(route('inventory.dashboard'));

        $response->assertOk()
            ->assertSee('Inventaris')
            ->assertSee('Dashboard Inventaris')
            ->assertSee('Data Barang')
            ->assertSee('Transaksi Inventaris')
            ->assertSee('Stock Opname')
            ->assertSee('Laporan Inventaris')
            ->assertSee('Beranda / Inventaris / Dashboard Inventaris')
            ->assertSee('nav-link-active')
            ->assertDontSee('href="#"', false);

        foreach ([
            'inventory.dashboard',
            'inventory.items.index',
            'inventory.transactions.index',
            'inventory.stock-opnames.index',
            'inventory.reports.index',
        ] as $routeName) {
            $response->assertSee(route($routeName), false);
            $this->actingAs($user)->get(route($routeName))->assertOk();
        }
    }

    public function test_inventory_route_active_state_is_scoped_to_current_menu(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $content = $this->actingAs($user)->get(route('inventory.items.index'))->assertOk()->getContent();

        $this->assertSame(1, substr_count($content, 'nav-link-active'));
        $this->assertStringContainsString('Beranda / Inventaris / Data Barang', $content);
    }

    public function test_user_without_inventory_permission_cannot_see_menu_and_gets_forbidden(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('dashboard.view', 'web'));

        $this->actingAs($user)->get(route('dashboard'))->assertOk()
            ->assertDontSee('Inventaris')
            ->assertDontSee('Dashboard Inventaris');

        $this->actingAs($user)->get(route('inventory.dashboard'))->assertForbidden();
    }

    public function test_inventory_navigation_follows_role_permissions(): void
    {
        $tataUsaha = User::factory()->create();
        $tataUsaha->assignRole('tata-usaha');

        $this->actingAs($tataUsaha)->get(route('inventory.dashboard'))->assertOk()
            ->assertSee('Kategori Barang')
            ->assertSee('Lokasi &amp; Ruangan', false)
            ->assertSee('Kondisi Barang')
            ->assertSee('Stock Opname');

        $kepala = User::factory()->create();
        $kepala->assignRole('kepala-madrasah');

        $this->actingAs($kepala)->get(route('inventory.dashboard'))->assertOk()
            ->assertSee('Dashboard Inventaris')
            ->assertSee('Laporan Inventaris')
            ->assertDontSee('Kategori Barang')
            ->assertDontSee('Lokasi &amp; Ruangan', false)
            ->assertDontSee('Kondisi Barang');

        $bendahara = User::factory()->create();
        $bendahara->assignRole('bendahara');

        $this->actingAs($bendahara)->get(route('inventory.dashboard'))->assertOk()
            ->assertSee('Dashboard Inventaris')
            ->assertSee('Data Barang')
            ->assertSee('Transaksi Inventaris')
            ->assertSee('Laporan Inventaris')
            ->assertDontSee('Kategori Barang')
            ->assertDontSee('Stock Opname');
    }
}
