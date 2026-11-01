<?php

declare(strict_types=1);

namespace Tests\Feature\Pmbm;

use App\Enums\Pmbm\ApplicantStatus;
use App\Models\AcademicYear;
use App\Models\Pmbm\PmbmApplicant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PmbmReportTest extends TestCase
{
    use RefreshDatabase;

    private function userWith(array $permissions): User
    {
        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        foreach ($permissions as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission));
        }

        return $user;
    }

    private function applicant(array $attributes = []): PmbmApplicant
    {
        $year = AcademicYear::firstOrCreate(['name' => '2027/2028'], ['starts_at' => '2027-07-01', 'ends_at' => '2028-06-30', 'is_active' => false]);

        return PmbmApplicant::create($attributes + ['academic_year_id' => $year->id, 'registration_number' => 'REPORT-'.PmbmApplicant::count(), 'registration_source' => 'online', 'status' => ApplicantStatus::Submitted, 'full_name' => 'Anak Laporan', 'gender' => 'male', 'birth_date' => '2020-01-01', 'data' => [], 'public_token' => bin2hex(random_bytes(32)), 'submitted_at' => '2026-11-02 12:00:00']);
    }

    public function test_report_actions_require_their_own_permissions(): void
    {
        $this->get(route('pmbm.reports.index'))->assertRedirect(route('login'));
        $this->actingAs($this->userWith([]))->get(route('pmbm.reports.index'))->assertForbidden();
        $this->actingAs($this->userWith(['pmbm.report.view']))->get(route('pmbm.reports.export'))->assertForbidden();
        $this->get(route('pmbm.reports.print'))->assertForbidden();
    }

    public function test_table_summary_print_and_export_use_identical_filters(): void
    {
        $match = $this->applicant(['full_name' => 'Sesuai Filter', 'registration_source' => 'offline', 'gender' => 'female']);
        $this->applicant(['full_name' => 'Di Luar Filter']);
        $this->actingAs($this->userWith(['pmbm.report.view', 'pmbm.report.export']));
        $query = ['academic_year_id' => $match->academic_year_id, 'source' => 'offline', 'gender' => 'female', 'status' => 'submitted', 'date_from' => '2026-11-02', 'date_to' => '2026-11-02'];
        $this->get(route('pmbm.reports.index', $query))->assertOk()->assertSee('Sesuai Filter')->assertDontSee('Di Luar Filter')->assertViewHas('summary', fn ($summary) => $summary['total'] === 1)->assertSee('Jenis Kelamin');
        $this->get(route('pmbm.reports.print', $query))->assertOk()->assertSee('Sesuai Filter')->assertDontSee('Di Luar Filter')->assertSee('Kepala Madrasah');
        $response = $this->get(route('pmbm.reports.export', $query))->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('Sesuai Filter', $csv);
        $this->assertStringNotContainsString('Di Luar Filter', $csv);
        $this->assertStringContainsString('Perempuan', $csv);
    }

    public function test_invalid_filters_are_rejected_for_every_output(): void
    {
        $this->actingAs($this->userWith(['pmbm.report.view', 'pmbm.report.export']));
        foreach (['index', 'export', 'print'] as $action) {
            $this->getJson(route('pmbm.reports.'.$action, ['date_from' => '2026-11-10', 'date_to' => '2026-11-01', 'source' => 'invalid', 'gender' => 'invalid', 'status' => 'invalid', 'academic_year_id' => 99999]))->assertUnprocessable()->assertJsonValidationErrors(['date_to', 'source', 'gender', 'status', 'academic_year_id']);
        }
    }

    public function test_summary_counts_all_pages_and_csv_neutralizes_formulas(): void
    {
        for ($i = 0; $i < 31; $i++) {
            $this->applicant(['full_name' => $i === 0 ? '=1+1' : 'Pendaftar '.$i]);
        }
        $this->actingAs($this->userWith(['pmbm.report.view', 'pmbm.report.export']));
        $this->get(route('pmbm.reports.index'))->assertOk()->assertViewHas('summary', fn ($summary) => $summary['total'] === 31)->assertViewHas('applicants', fn ($rows) => $rows->count() === 30);
        $csv = $this->get(route('pmbm.reports.export'))->assertOk()->streamedContent();
        $this->assertStringContainsString("'=1+1", $csv);
    }

    public function test_empty_report_and_end_date_without_start_date_are_supported(): void
    {
        $this->actingAs($this->userWith(['pmbm.report.view', 'pmbm.report.export']));
        $this->get(route('pmbm.reports.index', ['date_to' => '2026-11-02']))->assertOk()->assertSee('Tidak ada pendaftar sesuai filter');
        $this->get(route('pmbm.reports.print'))->assertOk()->assertSee('Tidak ada pendaftar sesuai filter');
    }
}
