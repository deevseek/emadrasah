<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\SchoolProfile;
use App\Models\User;
use App\Services\Foundation\SchoolProfileService;
use Database\Seeders\SchoolProfileSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SchoolProfileTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $permissions): User
    {
        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        foreach ($permissions as $permission) $user->givePermissionTo(Permission::findOrCreate($permission));
        return $user;
    }

    public function test_view_permission_controls_page_and_navigation(): void
    {
        $viewer = $this->user(['school-profile.view']);
        $this->actingAs($viewer)->get(route('school-profile.show'))->assertOk()->assertSee('Profil Madrasah')->assertSee('Data Madrasah');
        $this->actingAs($this->user([]))->get(route('school-profile.show'))->assertForbidden();
    }

    public function test_operator_updates_trimmed_profile_and_cache_is_cleared(): void
    {
        $user = $this->user(['school-profile.update']);
        Cache::put(SchoolProfileService::CACHE_KEY, 'stale');
        $this->actingAs($user)->put(route('school-profile.update'), $this->profileData(['name' => '  MI Teladan  ', 'nsm' => '00123']))->assertSessionHas('status');
        $this->assertDatabaseHas('school_profiles', ['name' => 'MI Teladan', 'nsm' => '00123']);
        $this->assertFalse(Cache::has(SchoolProfileService::CACHE_KEY));
        $this->assertDatabaseHas('activity_log', ['description' => 'Memperbarui data profil madrasah.']);
    }

    public function test_viewer_cannot_update_profile(): void
    {
        $this->actingAs($this->user(['school-profile.view']))->put(route('school-profile.update'), $this->profileData())->assertForbidden();
    }

    /** @dataProvider invalidProfileProvider */
    public function test_profile_validation(string $field, mixed $value): void
    {
        $this->actingAs($this->user(['school-profile.update']))->from(route('school-profile.show'))->put(route('school-profile.update'), $this->profileData([$field => $value]))->assertSessionHasErrors($field);
    }

    public static function invalidProfileProvider(): array
    {
        return [['name', ''], ['nsm', '12A'], ['npsn', 'A12'], ['email', 'bukan-email'], ['website', 'bukan-url']];
    }

    public function test_leader_is_updated_and_nip_remains_a_string(): void
    {
        $user = $this->user(['school-profile.update-leader']);
        $this->actingAs($user)->put(route('school-profile.leader.update'), ['head_name' => 'Siti Aminah', 'head_nip' => '001234'])->assertSessionHas('status');
        $profile = SchoolProfile::firstOrFail();
        $this->assertSame('001234', $profile->head_nip);
        $this->assertDatabaseHas('activity_log', ['description' => 'Memperbarui data kepala madrasah.']);
    }

    public function test_logo_can_be_uploaded_replaced_and_deleted_without_logging_file_contents(): void
    {
        Storage::fake('public');
        $user = $this->user(['school-profile.update-logo']);
        $this->actingAs($user)->post(route('school-profile.logo.update'), ['logo' => UploadedFile::fake()->image('first.png')])->assertSessionHas('status');
        $old = SchoolProfile::firstOrFail()->logo_path;
        Storage::disk('public')->assertExists($old);
        $this->actingAs($user)->post(route('school-profile.logo.update'), ['logo' => UploadedFile::fake()->image('second.webp')]);
        Storage::disk('public')->assertMissing($old);
        $new = SchoolProfile::firstOrFail()->logo_path;
        Storage::disk('public')->assertExists($new);
        $this->assertStringNotContainsString('data:', ActivityLog::latest()->firstOrFail()->properties->toJson());
        $this->actingAs($user)->delete(route('school-profile.logo.destroy'))->assertSessionHas('status');
        Storage::disk('public')->assertMissing($new);
        $this->assertNull(SchoolProfile::firstOrFail()->logo_path);
    }

    public function test_invalid_and_oversized_logo_are_rejected(): void
    {
        Storage::fake('public');
        $user = $this->user(['school-profile.update-logo']);
        $this->actingAs($user)->post(route('school-profile.logo.update'), ['logo' => UploadedFile::fake()->create('file.txt', 10)])->assertSessionHasErrors('logo');
        $this->actingAs($user)->post(route('school-profile.logo.update'), ['logo' => UploadedFile::fake()->image('large.png')->size(2049)])->assertSessionHasErrors('logo');
    }

    public function test_layout_uses_database_name_logo_and_initial_fallback(): void
    {
        $profile = SchoolProfile::create(['name' => 'Madrasah Database', 'education_level' => 'MI', 'logo_path' => 'school-profile/logos/logo.png']);
        $user = $this->user(['dashboard.view']);
        $this->actingAs($user)->get(route('dashboard'))->assertSee('Madrasah Database')->assertSee('storage/school-profile/logos/logo.png');
        app(SchoolProfileService::class)->clearCache();
        $profile->update(['logo_path' => null]);
        $this->actingAs($user)->get(route('dashboard'))->assertSee('MD');
    }

    public function test_profile_initials_are_generated_as_an_uppercase_string(): void
    {
        $profile = new SchoolProfile(['name' => '  madrasah   ibtidaiyah demak  ']);

        $this->assertSame('MI', $profile->initials);
    }

    public function test_school_profile_seeder_is_idempotent(): void
    {
        $this->seed(SchoolProfileSeeder::class); $this->seed(SchoolProfileSeeder::class);
        $this->assertSame(1, SchoolProfile::count());
    }

    private function profileData(array $overrides = []): array
    {
        return array_merge(['name' => 'MI Teladan', 'short_name' => 'MIT', 'education_level' => 'Madrasah Ibtidaiyah', 'status' => 'Swasta', 'nsm' => '001', 'npsn' => '002', 'address' => 'Jalan Utama', 'district' => 'Demak', 'city' => 'Demak', 'province' => 'Jawa Tengah', 'email' => 'admin@example.test', 'website' => 'https://example.test'], $overrides);
    }
}
