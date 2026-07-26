<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ImportBatch;
use App\Models\ImportPreview;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

final class AcademicImportViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_form_uses_available_error_bag_instead_of_missing_component(): void
    {
        $html = View::make('imports.form', [
            'kind' => 'teaching',
            'title' => 'Impor Penugasan Mengajar',
            'academicYears' => AcademicYear::with('semesters')->get(),
            'batches' => ImportBatch::with('importer')->get(),
            'errors' => new ViewErrorBag,
        ])->render();

        $this->assertStringContainsString('Unggah file Excel, periksa hasilnya, lalu simpan jadwal yang valid.', $html);
        $this->assertStringContainsString('File Jadwal', $html);
        $this->assertStringContainsString('Pilih File Excel', $html);
        $this->assertStringContainsString('Periksa Data', $html);
        $this->assertStringContainsString('Impor Penugasan Mengajar', $html);
        $this->assertStringNotContainsString('MengajarUnggah', $html);
        $this->assertStringNotContainsString('validation-errors', $html);
    }

    public function test_get_request_to_teaching_assignment_preview_returns_to_import_form(): void
    {
        Permission::findOrCreate('teaching-assignments.import');
        $user = User::factory()->create();
        $user->givePermissionTo('teaching-assignments.import');

        $this->actingAs($user)
            ->get('/academic/teaching-assignments/import/preview')
            ->assertRedirect(route('teaching-assignments.import'))
            ->assertSessionHasErrors(['preview' => 'Sesi preview telah berakhir. Silakan unggah kembali berkas.']);
    }

    public function test_get_request_to_schedule_preview_returns_to_import_form(): void
    {
        Permission::findOrCreate('schedules.import');
        $user = User::factory()->create();
        $user->givePermissionTo('schedules.import');

        $this->actingAs($user)
            ->get('/academic/schedules/import/preview')
            ->assertRedirect(route('schedules.import'))
            ->assertSessionHasErrors(['preview' => 'Sesi preview telah berakhir. Silakan unggah kembali berkas.']);
    }

    public function test_schedule_preview_uses_a_persistent_get_url_after_upload(): void
    {
        Storage::fake('local');
        Permission::findOrCreate('schedules.import');
        $user = User::factory()->create();
        $user->givePermissionTo('schedules.import');
        $year = AcademicYear::query()->create([
            'name' => '2026/2027',
            'starts_on' => '2026-07-01',
            'ends_on' => '2027-06-30',
        ]);
        $semester = Semester::query()->create([
            'academic_year_id' => $year->id,
            'name' => 'Ganjil',
            'term' => 1,
            'starts_on' => '2026-07-01',
            'ends_on' => '2026-12-31',
        ]);

        $template = $this->actingAs($user)
            ->get(route('schedules.import.template'))
            ->streamedContent();

        $response = $this->actingAs($user)->post(route('schedules.import.preview'), [
            'academic_year_id' => $year->id,
            'semester_id' => $semester->id,
            'file' => UploadedFile::fake()->createWithContent('jadwal.xlsx', $template),
        ]);

        $preview = ImportPreview::query()->sole();
        $response->assertRedirect(route('schedules.import.preview.show', $preview->token));

        $this->get(route('schedules.import.preview.show', $preview->token))
            ->assertOk()
            ->assertSee('Periksa Data Jadwal Pelajaran');
    }

    public function test_schedule_process_does_not_require_teaching_assignment_replace_confirmation(): void
    {
        Permission::findOrCreate('schedules.import');
        $user = User::factory()->create();
        $user->givePermissionTo('schedules.import');

        $response = $this->actingAs($user)->post(route('schedules.import.process'), [
            'preview_token' => 'da83787b-da3d-48e4-aacd-5b33e0b69239',
        ]);

        $response
            ->assertRedirect(route('schedules.import'))
            ->assertSessionDoesntHaveErrors('confirm_replace')
            ->assertSessionHasErrors('preview');
    }

    public function test_import_form_displays_preview_expiration_message(): void
    {
        $errors = (new ViewErrorBag)->put('default', new MessageBag(['preview' => 'Sesi preview telah berakhir.']));

        $html = View::make('imports.form', [
            'kind' => 'teaching',
            'title' => 'Impor Penugasan Mengajar',
            'academicYears' => AcademicYear::with('semesters')->get(),
            'batches' => ImportBatch::with('importer')->get(),
            'errors' => $errors,
        ])->render();

        $this->assertSame(1, substr_count($html, 'Sesi preview telah berakhir.'));
        $this->assertStringContainsString('border-amber-200', $html);
        $this->assertStringNotContainsString('Impor Jadwal PelajaranUnggah', $html);
    }

    public function test_status_is_rendered_once_as_success_alert_without_layout_duplication(): void
    {
        session()->flash('status', 'Impor selesai.');

        $html = View::make('imports.form', [
            'kind' => 'schedule',
            'title' => 'Impor Jadwal Pelajaran',
            'academicYears' => AcademicYear::with('semesters')->get(),
            'batches' => ImportBatch::with('importer')->get(),
            'errors' => new ViewErrorBag,
        ])->render();

        $this->assertSame(1, substr_count($html, 'Impor selesai.'));
        $this->assertStringContainsString('border-emerald-200 bg-emerald-50', $html);
    }

    public function test_schedule_import_page_has_separate_title_description_and_import_breadcrumb(): void
    {
        Permission::findOrCreate('schedules.import');
        $user = User::factory()->create();
        $user->givePermissionTo('schedules.import');

        $this->actingAs($user)->get(route('schedules.import'))
            ->assertOk()
            ->assertSee('Impor Jadwal Pelajaran')
            ->assertSee('Unggah file Excel, periksa hasilnya, lalu simpan jadwal yang valid.')
            ->assertDontSee('PelajaranUnggah')
            ->assertSee('Beranda / Akademik / Jadwal Pelajaran / Impor');
    }

    public function test_teaching_assignment_import_page_does_not_merge_title_and_description(): void
    {
        Permission::findOrCreate('teaching-assignments.import');
        $user = User::factory()->create();
        $user->givePermissionTo('teaching-assignments.import');

        $this->actingAs($user)->get(route('teaching-assignments.import'))
            ->assertOk()
            ->assertSee('Impor Penugasan Mengajar')
            ->assertDontSee('MengajarUnggah')
            ->assertSee('Beranda / Akademik / Penugasan Mengajar / Impor');
    }
}
