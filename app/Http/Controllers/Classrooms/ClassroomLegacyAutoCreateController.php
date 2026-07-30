<?php

declare(strict_types=1);

namespace App\Http\Controllers\Classrooms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Classrooms\{LegacyAutoCreateConfirmRequest, LegacyAutoCreatePreviewRequest};
use App\Models\{AcademicYear, Classroom, GradeLevel};
use App\Services\Classrooms\ClassroomLegacyAutoCreateService;
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ClassroomLegacyAutoCreateController extends Controller
{
    public function form(Request $request, ClassroomLegacyAutoCreateService $service): View
    {
        abort_unless($request->user()->can('classrooms.map-legacy'), 403);
        $year = (int) ($request->integer('academic_year') ?: AcademicYear::where('is_active', true)->value('id'));

        return view('classrooms.legacy-auto-create', ['years' => AcademicYear::latest('starts_at')->get(), 'year' => $year, 'grades' => GradeLevel::where('is_active', true)->orderBy('sort_order')->get(), 'rows' => $year ? $service->labels($year) : collect()]);
    }

    public function preview(LegacyAutoCreatePreviewRequest $request): View
    {
        $data = $request->validated();
        $this->guardDuplicates($data['rows'], $request->boolean('merge_duplicates'));
        $year = AcademicYear::findOrFail($data['academic_year_id']);
        $rows = collect($data['rows'])->map(function (array $row) use ($year): array {
            $existing = Classroom::where('academic_year_id', $year->id)->where('grade_level_id', $row['grade_level_id'])->where('code', trim($row['code']))->first();
            return [...$row, 'existing' => $existing];
        });

        return view('classrooms.legacy-auto-confirm', compact('year', 'rows'));
    }

    public function confirm(LegacyAutoCreateConfirmRequest $request, ClassroomLegacyAutoCreateService $service): RedirectResponse
    {
        $data = $request->validated();
        $this->guardDuplicates($data['rows'], true);
        $result = $service->process($request->user(), (int) $data['academic_year_id'], $data['rows']);
        $batch = (string) Str::uuid();
        session()->put("classroom_legacy_result.{$batch}", $result);

        return redirect()->route('classrooms.legacy.auto-create.result', $batch);
    }

    public function result(Request $request, string $batch): View
    {
        abort_unless($request->user()->can('classrooms.map-legacy'), 403);
        abort_unless(session()->has("classroom_legacy_result.{$batch}"), 404);

        return view('classrooms.legacy-auto-result', ['result' => session("classroom_legacy_result.{$batch}")]);
    }

    private function guardDuplicates(array $rows, bool $approved): void
    {
        $keys = collect($rows)->map(fn (array $row): string => $row['grade_level_id'].'|'.mb_strtolower(trim($row['code'])));
        if ($keys->duplicates()->isNotEmpty() && ! $approved) {
            throw ValidationException::withMessages(['merge_duplicates' => 'Dua data kelas lama mengarah ke rombel yang sama. Centang persetujuan untuk menggabungkannya.']);
        }
    }
}
