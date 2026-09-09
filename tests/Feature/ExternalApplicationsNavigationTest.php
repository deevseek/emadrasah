<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExternalApplicationsNavigationTest extends TestCase
{
    use RefreshDatabase;

    private const EXTERNAL_ROLES = ['operator', 'kepala-madrasah', 'super-admin'];

    private const OTHER_ROLES = ['guru', 'hrd', 'bendahara'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AccessControlSeeder::class);
    }

    public function test_external_applications_are_visible_to_the_required_roles(): void
    {
        foreach (self::EXTERNAL_ROLES as $role) {
            $response = $this->actingAs($this->userWithRole($role))->get(route('dashboard'));

            $response->assertOk()
                ->assertSee('Aplikasi Eksternal')
                ->assertSee('EMIS 4.0')
                ->assertSee('EMISGTK')
                ->assertSee('SPMB ADMIN')
                ->assertSee('SPMB AKUN')
                ->assertSee('href="https://emis.kemenag.go.id/login" target="_blank" rel="noopener noreferrer"', false)
                ->assertSee('href="https://spmb.demakkab.go.id/" target="_blank" rel="noopener noreferrer"', false);
            $this->assertSame(21, substr_count($response->getContent(), 'target="_blank" rel="noopener noreferrer"'));
        }
    }

    public function test_external_applications_are_hidden_from_other_roles(): void
    {
        foreach (self::OTHER_ROLES as $role) {
            $response = $this->actingAs($this->userWithRole($role))->get(route('dashboard'));

            $response->assertOk()
                ->assertDontSee('Aplikasi Eksternal')
                ->assertDontSee('EMIS 4.0')
                ->assertDontSee('SPMB ADMIN');
        }
    }

    public function test_all_external_links_are_configured_securely_and_internal_navigation_is_unchanged(): void
    {
        $groups = collect(config('navigation'));
        $externalGroup = $groups->firstWhere('label', 'Aplikasi Eksternal');

        $this->assertNotNull($externalGroup);
        $this->assertSame(self::EXTERNAL_ROLES, $externalGroup['roles']);
        $this->assertCount(21, $externalGroup['items']);
        $this->assertSame([
            'EMIS 4.0' => 'https://emis.kemenag.go.id/login',
            'EMISGTK' => 'https://emisgtk.kemenag.go.id/login',
            'SDM DATA' => 'https://sdm.data.kemendikdasmen.go.id/',
            'PD DATA' => 'https://pd.data.kemendikdasmen.go.id/',
            'VERVALPD' => 'https://vervalpd.data.kemendikdasmen.go.id/',
            'VERVALPTK' => 'https://vervalptk.data.kemendikdasmen.go.id/',
            'VERVALSP' => 'https://vervalsp.data.kemendikdasmen.go.id/',
            'PENCARIAN NISN' => 'https://nisn.data.kemendikdasmen.go.id/',
            'PDUM' => 'https://pdum.kemenag.go.id/',
            'PORTAL DATA INDUK IJAZAH' => 'https://ijazah.pendidikan.go.id/',
            'MANAJEMEN IJAZAH' => 'https://ijazah.data.kemendikdasmen.go.id/manajemen/#/sign-in?redirectURL=%2Fberanda',
            'EDM' => 'https://edm-fe.erkam-v2.kemenag.go.id/login',
            'ERKAM' => 'https://frontend.erkam-v2.kemenag.go.id/login',
            'BIO AN' => 'https://bioportal.kemendikdasmen.go.id/',
            'AN' => 'https://anbk.kemendikdasmen.go.id/',
            'TKA' => 'https://tka.kemendikdasmen.go.id/',
            'PIP MADRASAH' => 'https://pipmadrasah.kemenag.go.id/',
            'PIP KEMENDIKDASMEN' => 'https://pip.kemendikdasmen.go.id/home_v1',
            'SEKOLAH KITA' => 'https://sekolah.data.kemendikdasmen.go.id/',
            'SPMB ADMIN' => 'https://adminspmb.demakkab.go.id/login',
            'SPMB AKUN' => 'https://spmb.demakkab.go.id/',
        ], collect($externalGroup['items'])->pluck('url', 'label')->all());
        $this->assertTrue(collect($externalGroup['items'])->every(
            fn (array $item): bool => $item['external'] === true
                && str_starts_with($item['url'], 'https://')
                && isset($item['label'], $item['icon'])
                && ! isset($item['route'], $item['active'], $item['permission'], $item['permission_any'])
        ));

        $this->assertSame(
            ['Beranda', 'Data Madrasah', 'HRD / Kepegawaian', 'Kesiswaan', 'Akun & Akses', 'Akademik', 'Portal Orang Tua', 'Keuangan', 'Pelayanan', 'Website', 'Aplikasi Eksternal', 'Pengaturan'],
            $groups->pluck('label')->all()
        );

        $internalItems = $groups->where('label', '!=', 'Aplikasi Eksternal')->pluck('items')->flatten(1);
        $this->assertTrue($internalItems->every(fn (array $item): bool => isset($item['route'], $item['active'])
            && (isset($item['permission']) || isset($item['permission_any']))));
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->syncRoles([$role]);
        $user->givePermissionTo('dashboard.view');

        return $user;
    }
}
