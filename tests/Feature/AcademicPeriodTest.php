<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\SemesterType;
use App\Models\AcademicYear;
use App\Models\Semester;
use App\Models\User;
use App\Services\Foundation\AcademicPeriodService;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AcademicPeriodTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AccessControlSeeder::class);
    }

    public function test_view_permission_controls_page_and_navigation(): void
    {
        $viewer = User::factory()->create(['must_change_password' => false]);
        $viewer->givePermissionTo('academic-periods.view');
        $this->actingAs($viewer)->get(route('academic-periods.index'))->assertOk()->assertSee('Tahun Ajaran &amp; Semester', false)->assertSee('Belum ada tahun ajaran.');
        $user = User::factory()->create(['must_change_password' => false]);
        $this->actingAs($user)->get(route('academic-periods.index'))->assertForbidden();
    }

    public function test_operator_creates_exactly_two_semesters_and_activity(): void
    {
        $operator = User::factory()->create(['must_change_password' => false]); $operator->assignRole('operator');
        $this->actingAs($operator)->post(route('academic-periods.store'), $this->payload())->assertRedirect(route('academic-periods.index'));
        $year = AcademicYear::firstOrFail();
        $this->assertSame(2, $year->semesters()->count());
        $this->assertDatabaseHas('semesters', ['type' => 'ganjil', 'name' => 'Semester Ganjil']);
        $this->assertDatabaseHas('semesters', ['type' => 'genap', 'name' => 'Semester Genap']);
        $this->assertDatabaseHas('activity_log', ['description' => 'Menambahkan Tahun Ajaran 2026/2027.']);
    }

    public function test_academic_period_validation_is_applied(): void
    {
        $operator = User::factory()->create(['must_change_password' => false]); $operator->assignRole('operator');
        $this->actingAs($operator)->from(route('academic-periods.create'))->post(route('academic-periods.store'), array_merge($this->payload(), ['name' => '2026/2028', 'even_starts_at' => '2026-12-01']))->assertSessionHasErrors(['name', 'even_starts_at']);
        $this->assertDatabaseCount('academic_years', 0);
    }

    public function test_update_changes_existing_semesters_without_duplicates(): void
    {
        $operator = User::factory()->create(['must_change_password' => false]); $operator->assignRole('operator');
        $year = app(AcademicPeriodService::class)->create($this->payload(), $operator);
        $payload = array_merge($this->payload(), ['odd_ends_at' => '2026-12-20', 'even_starts_at' => '2027-01-05']);
        $this->actingAs($operator)->put(route('academic-periods.update', $year), $payload)->assertRedirect();
        $this->assertSame(2, $year->semesters()->count());
        $this->assertDatabaseHas('semesters', ['academic_year_id' => $year->id, 'type' => 'ganjil', 'ends_at' => '2026-12-20 00:00:00']);
    }

    public function test_activation_keeps_only_one_active_period_and_is_idempotent(): void
    {
        $operator = User::factory()->create(['must_change_password' => false]); $operator->assignRole('operator');
        $first = app(AcademicPeriodService::class)->create($this->payload(), $operator);
        $second = app(AcademicPeriodService::class)->create(array_merge($this->payload(), ['name' => '2027/2028', 'starts_at' => '2027-07-01', 'ends_at' => '2028-06-30', 'odd_starts_at' => '2027-07-01', 'odd_ends_at' => '2027-12-31', 'even_starts_at' => '2028-01-01', 'even_ends_at' => '2028-06-30']), $operator);
        $service = app(AcademicPeriodService::class);
        $service->activate($first->semesters()->where('type', SemesterType::Ganjil->value)->firstOrFail(), $operator);
        Cache::put(AcademicPeriodService::CACHE_KEY, 'stale');
        $semester = $second->semesters()->where('type', SemesterType::Genap->value)->firstOrFail();
        $service->activate($semester, $operator); $service->activate($semester, $operator);
        $this->assertSame(1, AcademicYear::where('is_active', true)->count());
        $this->assertSame(1, Semester::where('is_active', true)->count());
        $this->assertFalse(Cache::has(AcademicPeriodService::CACHE_KEY));
        $this->assertTrue($second->fresh()->is_active);
    }

    public function test_delete_rules_and_default_roles(): void
    {
        $operator = User::factory()->create(['must_change_password' => false]); $operator->assignRole('operator');
        $year = app(AcademicPeriodService::class)->create($this->payload(), $operator);
        $this->actingAs($operator)->delete(route('academic-periods.destroy', $year))->assertForbidden();
        $this->assertFalse($operator->can('academic-periods.delete'));
        $this->assertTrue($operator->can('academic-periods.activate'));
        $this->assertTrue(Permission::where('name', 'academic-periods.delete')->exists());
    }

    private function payload(): array
    {
        return ['name' => '2026/2027', 'starts_at' => '2026-07-01', 'ends_at' => '2027-06-30', 'notes' => 'Periode utama', 'odd_starts_at' => '2026-07-01', 'odd_ends_at' => '2026-12-31', 'even_starts_at' => '2027-01-01', 'even_ends_at' => '2027-06-30'];
    }
}
