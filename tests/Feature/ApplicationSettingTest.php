<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ApplicationSetting;
use App\Models\User;
use App\Services\Settings\ApplicationSettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApplicationSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_permission_controls_page_and_menu(): void
    {
        $this->actingAs($this->user([]))->get(route('application-settings.edit'))->assertForbidden();
        $this->actingAs($this->user(['application-settings.view']))->get(route('application-settings.edit'))->assertOk()->assertSee('Pengaturan Aplikasi')->assertSee('Branding');
    }

    public function test_settings_are_validated_persisted_and_cache_is_refreshed(): void
    {
        $user = $this->user(['application-settings.update']);
        Cache::put(ApplicationSettingService::CACHE_KEY, ['app_name' => 'Lama']);
        $this->actingAs($user)->put(route('application-settings.update'), $this->data(['app_name' => ' SIM Madrasah ']))->assertSessionHas('status');
        $this->assertDatabaseHas('application_settings', ['key' => 'app_name', 'value' => 'SIM Madrasah']);
        $this->assertFalse(Cache::has(ApplicationSettingService::CACHE_KEY));
        $this->assertSame('SIM Madrasah', app(ApplicationSettingService::class)->get('app_name'));
        $this->actingAs($user)->from(route('application-settings.edit'))->put(route('application-settings.update'), $this->data(['primary_color' => 'hijau', 'pagination_size' => 17, 'timezone' => 'Invalid/Zone']))->assertSessionHasErrors(['primary_color', 'pagination_size', 'timezone']);
    }

    public function test_logo_upload_works_and_invalid_file_is_rejected(): void
    {
        Storage::fake('public'); $user = $this->user(['application-settings.update']);
        $this->actingAs($user)->put(route('application-settings.update'), $this->data(['primary_logo' => UploadedFile::fake()->image('logo.png')]))->assertSessionHasNoErrors();
        $path = ApplicationSetting::where('key', 'primary_logo')->value('value'); Storage::disk('public')->assertExists($path);
        $this->actingAs($user)->put(route('application-settings.update'), $this->data(['primary_logo' => UploadedFile::fake()->create('virus.php', 10)]))->assertSessionHasErrors('primary_logo');
    }

    public function test_oversized_branding_files_show_clear_indonesian_errors(): void
    {
        $user = $this->user(['application-settings.update']);

        $response = $this->actingAs($user)
            ->from(route('application-settings.edit'))
            ->put(route('application-settings.update'), $this->data([
                'primary_logo' => UploadedFile::fake()->image('logo.png')->size(2049),
                'favicon' => UploadedFile::fake()->create('favicon.ico', 513, 'image/x-icon'),
            ]));

        $response->assertRedirect(route('application-settings.edit'))
            ->assertSessionHasErrors([
                'primary_logo' => 'Ukuran Logo Utama maksimal 2 MB.',
                'favicon' => 'Ukuran Favicon maksimal 512 KB.',
            ]);

        $this->get(route('application-settings.edit'))
            ->assertOk()
            ->assertSee('data-initial-tab="branding"', false)
            ->assertSee('Ukuran Logo Utama maksimal 2 MB.')
            ->assertDontSee('validation.max.file');
    }

    public function test_defaults_remain_available_when_rows_are_incomplete(): void
    {
        $this->assertSame('e-Madrasah', app(ApplicationSettingService::class)->get('app_name'));
        $this->assertSame(20, app(ApplicationSettingService::class)->get('pagination_size'));
    }

    public function test_maintenance_blocks_regular_user_but_not_super_admin(): void
    {
        ApplicationSetting::create(['key' => 'maintenance_mode', 'value' => '1', 'type' => 'boolean', 'group' => 'system']); app(ApplicationSettingService::class)->clearCache();
        $this->actingAs($this->user(['dashboard.view']))->get(route('dashboard'))->assertStatus(503)->assertSee('Sistem dalam pemeliharaan');
        $admin = $this->user([]); $role = Role::findOrCreate('super-admin'); $admin->assignRole($role);
        $this->actingAs($admin)->get(route('dashboard'))->assertOk();
    }

    private function user(array $permissions): User
    {
        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        foreach ($permissions as $permission) $user->givePermissionTo(Permission::findOrCreate($permission));
        return $user;
    }

    private function data(array $overrides = []): array
    {
        return array_merge(['app_name' => 'e-Madrasah', 'app_short_name' => 'eMadrasah', 'app_description' => 'Sistem Informasi', 'institution_name' => 'MI Teladan', 'app_email' => 'admin@example.test', 'app_phone' => '', 'app_website' => 'https://example.test', 'primary_color' => '#047857', 'default_theme' => 'light', 'sidebar_mode' => 'expanded', 'default_language' => 'id', 'timezone' => 'Asia/Jakarta', 'date_format' => 'DD/MM/YYYY', 'time_format' => '24', 'first_day_of_week' => 'monday', 'maintenance_mode' => false, 'maintenance_message' => 'Sistem sedang dipelihara.', 'pagination_size' => 20], $overrides);
    }
}
