<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ImportBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
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

        $this->assertStringContainsString('Unggah XLSX', $html);
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
            ->assertSessionHas('status', 'Sesi preview telah berakhir. Silakan unggah kembali berkas untuk membuat preview baru.');
    }

    public function test_get_request_to_schedule_preview_returns_to_import_form(): void
    {
        Permission::findOrCreate('schedules.import');
        $user = User::factory()->create();
        $user->givePermissionTo('schedules.import');

        $this->actingAs($user)
            ->get('/academic/schedules/import/preview')
            ->assertRedirect(route('schedules.import'))
            ->assertSessionHas('status', 'Sesi preview telah berakhir. Silakan unggah kembali berkas untuk membuat preview baru.');
    }

    public function test_import_form_displays_preview_expiration_message(): void
    {
        session()->flash('status', 'Sesi preview telah berakhir.');

        $html = View::make('imports.form', [
            'kind' => 'teaching',
            'title' => 'Impor Penugasan Mengajar',
            'academicYears' => AcademicYear::with('semesters')->get(),
            'batches' => ImportBatch::with('importer')->get(),
            'errors' => new ViewErrorBag,
        ])->render();

        $this->assertStringContainsString('Sesi preview telah berakhir.', $html);
    }
}
