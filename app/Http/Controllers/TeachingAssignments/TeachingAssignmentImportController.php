<?php

declare(strict_types=1);

namespace App\Http\Controllers\TeachingAssignments;

use App\Http\Controllers\Controller;
use App\Http\Requests\TeachingAssignments\UploadTeachingAssignmentRequest;
use App\Models\{AcademicYear, TeachingImportBatch};
use App\Services\TeachingAssignments\TeachingAssignmentImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeachingAssignmentImportController extends Controller
{
    public function form(): View
    {
        return view('teaching-assignments.import.form', ['academicYears' => AcademicYear::latest('starts_at')->get()]);
    }

    public function preview(UploadTeachingAssignmentRequest $request, TeachingAssignmentImportService $service): RedirectResponse
    {
        try { $batch = $service->preview($request->file('file'), (int) $request->validated('academic_year_id'), $request->user()); } catch (\RuntimeException $exception) { return back()->withInput()->withErrors(['file' => $exception->getMessage() ?: 'File XLSX tidak dapat dibaca. Periksa kembali file yang diunggah.']); }
        return redirect()->route('teaching-assignments.import.show', $batch)->with('success', 'Seluruh sheet berhasil dibaca. Silakan periksa hasil pencocokan.');
    }

    public function show(Request $request, TeachingImportBatch $batch): View
    {
        $this->authorizeBatch($request, $batch);
        $status = $request->string('status')->toString(); $base = fn (string $type) => $batch->rows()->with(['personnel', 'subject', 'classroom'])->where('row_type', $type)->when($status !== '', fn ($query) => $query->where('status', $status))->orderBy('row_number')->orderBy('source_sequence')->get(); return view('teaching-assignments.import.show', ['batch' => $batch->load('academicYear'), 'status' => $status, 'subjects' => $base('subject'), 'personnelRows' => $base('personnel'), 'classroomRows' => $base('classroom'), 'candidateRows' => $base('assignment_candidate'), 'dutyRows' => $base('additional_duty')]);
    }

    public function errors(Request $request, TeachingImportBatch $batch): View
    {
        $this->authorizeBatch($request, $batch);
        return view('teaching-assignments.import.errors', ['batch' => $batch, 'rows' => $batch->rows()->whereIn('status', ['unmatched', 'selection', 'review'])->orderBy('sheet_name')->orderBy('row_number')->paginate(100)]);
    }

    private function authorizeBatch(Request $request, TeachingImportBatch $batch): void
    {
        abort_unless($request->user()->can('teaching-assignments.import') && ($batch->user_id === $request->user()->id || $request->user()->hasRole('super-admin')), 403);
    }
}
