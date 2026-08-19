<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\BriIntegrationSetting;
use App\Models\User;
use App\Services\Finance\BriConfigurationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BriApplicationSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_tabs_are_visible_per_domain_permission(): void
    {
        $admin=$this->user([]);$admin->assignRole(Role::findOrCreate('super-admin'));
        $this->actingAs($admin)->get(route('application-settings.edit'))->assertOk()->assertSee('Keuangan &amp; BRI',false)->assertSee('HRD')->assertSee('Branding');
        $this->actingAs($this->user(['hrd-settings.view']))->get(route('application-settings.edit'))->assertOk()->assertSee('HRD')->assertDontSee('Keuangan &amp; BRI',false)->assertDontSee('Branding');
        $this->actingAs($this->user(['finance.bri.configure']))->get(route('application-settings.edit'))->assertOk()->assertSee('Keuangan &amp; BRI',false)->assertDontSee('Branding');
    }

    public function test_domain_authorization_is_enforced_by_backend(): void
    {
        $treasurer=$this->user(['finance.bri.configure']);
        $this->actingAs($treasurer)->put(route('application-settings.general.update'),[])->assertForbidden();
        $this->actingAs($this->user(['hrd-settings.view']))->put(route('application-settings.bri.update'),$this->payload())->assertForbidden();
    }

    public function test_secret_is_encrypted_hidden_and_preserved_and_key_is_private(): void
    {
        Storage::fake('bri_private');Storage::fake('public');$user=$this->user(['finance.bri.configure']);
        $payload=$this->payload(['client_secret'=>'rahasia-sangat-kuat','source_account'=>'1234567890','private_key'=>UploadedFile::fake()->createWithContent('private.pem',"-----BEGIN PRIVATE KEY-----\nabc\n-----END PRIVATE KEY-----")]);
        $this->actingAs($user)->put(route('application-settings.bri.update'),$payload)->assertSessionHasNoErrors();
        $setting=BriIntegrationSetting::firstOrFail();
        $raw=DB::table('bri_integration_settings')->value('client_secret');
        $this->assertNotSame('rahasia-sangat-kuat',$raw);$this->assertSame('rahasia-sangat-kuat',$setting->client_secret);
        Storage::disk('bri_private')->assertExists($setting->private_key_path);Storage::disk('public')->assertMissing($setting->private_key_path);
        $this->actingAs($user)->get(route('application-settings.edit'))->assertDontSee('rahasia-sangat-kuat');
        $this->actingAs($user)->put(route('application-settings.bri.update'),$this->payload(['client_secret'=>'']))->assertSessionHasNoErrors();
        $this->assertSame('rahasia-sangat-kuat',$setting->fresh()->client_secret);
    }

    public function test_save_synchronizes_env_and_preserves_unrelated_values(): void
    {
        $user=$this->user(['finance.bri.configure']);
        $this->actingAs($user)->put(route('application-settings.bri.update'),$this->payload(['client_secret'=>'nilai # dengan = tanda']))->assertSessionHasNoErrors();
        $env=file_get_contents($this->briEnvFile);
        $this->assertStringContainsString("UNRELATED=preserved",$env);
        $this->assertStringContainsString("BRI_BASE_URL=https://sandbox.example.test",$env);
        $this->assertStringContainsString('BRI_CLIENT_SECRET="nilai # dengan = tanda"',$env);
        $this->assertNotEmpty(glob(storage_path('app/backups/env/.env-*')));
    }

    public function test_account_is_masked_and_never_returned_to_browser(): void
    {
        $user=$this->user(['finance.bri.configure']);
        $this->actingAs($user)->put(route('application-settings.bri.update'),$this->payload(['registered_account_number'=>'123456789012']))->assertSessionHasNoErrors();
        $this->actingAs($user)->get(route('application-settings.edit'))->assertOk()->assertSee('********9012')->assertDontSee('123456789012');
    }

    public function test_disabled_fallback_is_safe_and_connection_test_is_non_transactional(): void
    {
        config(['bri.enabled'=>false]);$this->assertFalse(app(BriConfigurationService::class)->enabled());
        $user=$this->user(['finance.bri.configure']);Http::fake();
        $this->actingAs($user)->post(route('application-settings.bri.test'))->assertSessionHas('error','Integrasi BRI belum diaktifkan.');
        Http::assertNothingSent();
    }

    public function test_navigation_has_one_central_settings_item(): void
    {
        $items=collect(config('navigation'))->flatMap(fn(array $section)=>$section['items']);
        $this->assertCount(1,$items->where('route','application-settings.edit'));
        $this->assertFalse($items->contains('label','Pengaturan HRD'));
        $this->assertFalse($items->contains('label','Pengaturan Pembayaran'));
        $item=$items->firstWhere('route','application-settings.edit');
        $this->assertSame(['application-settings.view','hrd-settings.view','finance.bri.configure'],$item['permission_any']);
    }

    private function user(array $permissions):User{$user=User::factory()->create(['is_active'=>true,'must_change_password'=>false]);foreach($permissions as $permission)$user->givePermissionTo(Permission::findOrCreate($permission));return $user;}
    private function payload(array $extra=[]):array{return array_merge(['enabled'=>true,'environment'=>'sandbox','base_url'=>'https://sandbox.example.test','client_id'=>'client','partner_id'=>'partner','channel_id'=>'123','briva_enabled'=>false,'briva_mode'=>'per_student','payroll_enabled'=>false,'payroll_method'=>'internal_bri'],$extra);}
}
