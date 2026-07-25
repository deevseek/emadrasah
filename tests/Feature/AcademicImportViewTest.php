<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ImportBatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
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
}
